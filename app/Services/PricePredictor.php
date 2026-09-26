<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\Stock;
use App\Support\Libelles;
use App\Support\Urgence;

class PricePredictor
{
    public function estimate(Product $product, array $input): array
    {
        $daysLeft = max(0, (int) $input['days_left']);
        $freshness = (int) $input['freshness'];
        $quantity = max(0.1, (float) $input['quantity']);
        $month = (int) ($input['month'] ?? now()->month);
        $urgency = Urgence::classify($daysLeft, $freshness);
        [$demand, $supply] = $this->marche($product);

        $history = PriceHistory::query()
            ->where('product_id', $product->id)
            ->get();

        $samples = $history->map(fn (PriceHistory $row) => [
            'days_left' => (int) $row->days_left,
            'freshness' => (int) $row->freshness,
            'urgency' => Urgence::poids($row->urgency),
            'gap' => (float) $row->demand - (float) $row->supply,
            'month' => (int) $row->month,
            'quantity' => (float) $row->quantity,
            'price' => (float) $row->price,
        ])->all();

        $current = [
            'days_left' => $daysLeft,
            'freshness' => $freshness,
            'urgency' => Urgence::poids($urgency),
            'gap' => $demand - $supply,
            'month' => $month,
            'quantity' => $quantity,
        ];

        $trained = count($samples) >= 12 ? $this->predict($samples, $current) : null;
        $recommended = $trained['price'] ?? round($history->avg('price') ?: 500);
        $recommended = $this->arrondir(max(50, $recommended));
        $half = $this->arrondir(max($trained['spread'] ?? 0, $recommended * 0.07));
        $half = max(25, $half);

        $factors = $trained['factors'] ?? [];

        return [
            'recommended' => $recommended,
            'min' => max(25, $recommended - $half),
            'max' => $recommended + $half,
            'season' => Libelles::saison($month),
            'demand' => round($demand, 3),
            'supply' => round($supply, 3),
            'sample_count' => count($samples),
            'days_left' => $daysLeft,
            'urgency' => $urgency,
            'factors' => $factors,
        ];
    }

    /**
     * @param  array<int, array<string, float|int>>  $samples
     * @param  array<string, float|int>  $current
     * @return array{price: float, spread: float, factors: array<int, array{label: string, impact: float}>}|null
     */
    private function predict(array $samples, array $current): ?array
    {
        $raw = array_map(fn (array $sample) => $this->rawFeatures($sample), $samples);
        $stats = $this->stats($raw);
        $rows = [];
        $targets = [];

        foreach ($samples as $index => $sample) {
            $rows[] = $this->design($raw[$index], $stats);
            $targets[] = (float) $sample['price'];
        }

        $beta = $this->ridge($rows, $targets, 1.2);

        if ($beta === null) {
            return null;
        }

        $point = $this->design($this->rawFeatures($current), $stats);
        $price = $this->dot($beta, $point);
        $errors = [];

        foreach ($rows as $index => $row) {
            $errors[] = $targets[$index] - $this->dot($beta, $row);
        }

        $labels = [
            1 => 'Durée de conservation restante',
            2 => 'Fraîcheur',
            3 => 'Niveau d\'urgence',
            4 => 'Écart demande / offre',
            5 => 'Saisonnalité',
            7 => 'Quantité disponible',
        ];

        $impacts = [];
        foreach ($labels as $index => $label) {
            $value = ($beta[$index] ?? 0) * ($point[$index] ?? 0);
            if ($index === 5 && isset($beta[6], $point[6])) {
                $value += $beta[6] * $point[6];
            }
            $impacts[] = ['label' => $label, 'impact' => round($value, 1)];
        }

        usort($impacts, fn ($a, $b) => abs($b['impact']) <=> abs($a['impact']));

        return [
            'price' => $price,
            'spread' => $this->stddev($errors),
            'factors' => array_slice($impacts, 0, 4),
        ];
    }

    /**
     * @param  array<string, float|int>  $sample
     * @return array<int, float>
     */
    private function rawFeatures(array $sample): array
    {
        $month = (int) $sample['month'];

        return [
            (float) $sample['days_left'],
            (float) $sample['freshness'],
            (float) $sample['urgency'],
            (float) $sample['gap'],
            sin(2 * M_PI * $month / 12),
            cos(2 * M_PI * $month / 12),
            log(1 + (float) $sample['quantity']),
        ];
    }

    /**
     * @param  array<int, array<int, float>>  $rawRows
     * @return array{mean: array<int, float>, std: array<int, float>}
     */
    private function stats(array $rawRows): array
    {
        $count = count($rawRows);
        $width = count($rawRows[0]);
        $mean = array_fill(0, $width, 0.0);

        foreach ($rawRows as $row) {
            foreach ($row as $index => $value) {
                $mean[$index] += $value;
            }
        }

        foreach ($mean as $index => $total) {
            $mean[$index] = $total / $count;
        }

        $std = array_fill(0, $width, 0.0);

        foreach ($rawRows as $row) {
            foreach ($row as $index => $value) {
                $std[$index] += ($value - $mean[$index]) ** 2;
            }
        }

        foreach ($std as $index => $sum) {
            $std[$index] = sqrt($sum / max(1, $count - 1));
            if ($std[$index] < 1e-6) {
                $std[$index] = 1.0;
            }
        }

        return ['mean' => $mean, 'std' => $std];
    }

    /**
     * @param  array<int, float>  $raw
     * @param  array{mean: array<int, float>, std: array<int, float>}  $stats
     * @return array<int, float>
     */
    private function design(array $raw, array $stats): array
    {
        $row = [1.0];

        foreach ($raw as $index => $value) {
            $row[] = ($value - $stats['mean'][$index]) / $stats['std'][$index];
        }

        return $row;
    }

    /**
     * @param  array<int, array<int, float>>  $rows
     * @param  array<int, float>  $targets
     * @return array<int, float>|null
     */
    private function ridge(array $rows, array $targets, float $lambda): ?array
    {
        $width = count($rows[0]);
        $xtx = array_fill(0, $width, array_fill(0, $width, 0.0));
        $xty = array_fill(0, $width, 0.0);

        foreach ($rows as $rowIndex => $row) {
            for ($i = 0; $i < $width; $i++) {
                $xty[$i] += $row[$i] * $targets[$rowIndex];
                for ($j = 0; $j < $width; $j++) {
                    $xtx[$i][$j] += $row[$i] * $row[$j];
                }
            }
        }

        for ($i = 1; $i < $width; $i++) {
            $xtx[$i][$i] += $lambda;
        }

        return $this->solve($xtx, $xty);
    }

    /**
     * @param  array<int, array<int, float>>  $matrix
     * @param  array<int, float>  $vector
     * @return array<int, float>|null
     */
    private function solve(array $matrix, array $vector): ?array
    {
        $n = count($vector);

        for ($i = 0; $i < $n; $i++) {
            $matrix[$i][$n] = $vector[$i];
        }

        for ($col = 0; $col < $n; $col++) {
            $pivot = $col;
            for ($row = $col + 1; $row < $n; $row++) {
                if (abs($matrix[$row][$col]) > abs($matrix[$pivot][$col])) {
                    $pivot = $row;
                }
            }

            if (abs($matrix[$pivot][$col]) < 1e-9) {
                return null;
            }

            [$matrix[$col], $matrix[$pivot]] = [$matrix[$pivot], $matrix[$col]];
            $divisor = $matrix[$col][$col];

            for ($j = $col; $j <= $n; $j++) {
                $matrix[$col][$j] /= $divisor;
            }

            for ($row = 0; $row < $n; $row++) {
                if ($row === $col) {
                    continue;
                }

                $factor = $matrix[$row][$col];
                for ($j = $col; $j <= $n; $j++) {
                    $matrix[$row][$j] -= $factor * $matrix[$col][$j];
                }
            }
        }

        $solution = [];
        for ($i = 0; $i < $n; $i++) {
            $solution[] = $matrix[$i][$n];
        }

        return $solution;
    }

    /**
     * @param  array<int, float>  $left
     * @param  array<int, float>  $right
     */
    private function dot(array $left, array $right): float
    {
        $total = 0.0;
        foreach ($left as $index => $value) {
            $total += $value * $right[$index];
        }

        return $total;
    }

    private function stddev(array $values): float
    {
        $count = count($values);
        if ($count < 2) {
            return 0.0;
        }

        $mean = array_sum($values) / $count;
        $sum = 0.0;
        foreach ($values as $value) {
            $sum += ($value - $mean) ** 2;
        }

        return sqrt($sum / ($count - 1));
    }

    private function arrondir(float $value): float
    {
        return (float) (round($value / 5) * 5);
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function marche(Product $product): array
    {
        $supplyKg = (float) Stock::query()
            ->where('product_id', $product->id)
            ->where('statut', Stock::PUBLIE)
            ->sum('quantite');

        $demandKg = (float) Order::query()
            ->where('statut', '!=', 'annulee')
            ->where('created_at', '>=', now()->subDays(30))
            ->whereHas('stock', fn ($query) => $query->where('product_id', $product->id))
            ->sum('quantite');

        if ($supplyKg <= 0 && $demandKg <= 0) {
            return [0.55, 0.45];
        }

        return [min(1, $demandKg / 800), min(1, $supplyKg / 1500)];
    }
}
