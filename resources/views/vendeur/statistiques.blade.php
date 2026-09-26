@extends('layouts.producteur')

@section('title', 'Rapport — Agriconnect')

@section('main')
    <div class="report-bar no-print">
        <div class="filters">
            @foreach ($periodes as $cle => $nom)
                <a href="{{ route('vendeur.statistiques', ['periode' => $cle]) }}" @class(['is-on' => $periode === $cle])>{{ $nom }}</a>
            @endforeach
        </div>
        <form class="range-form" method="GET" action="{{ route('vendeur.statistiques') }}">
            <label>Du
                <input type="date" name="du" value="{{ $periode === 'intervalle' ? $du : '' }}" max="{{ now()->toDateString() }}" required>
            </label>
            <label>Au
                <input type="date" name="au" value="{{ $periode === 'intervalle' ? $au : '' }}" max="{{ now()->toDateString() }}" required>
            </label>
            <button type="submit">Appliquer</button>
        </form>
        <button type="button" class="print-btn" onclick="window.print()">Imprimer</button>
    </div>

    <article class="report">
        <header class="report-top">
            <div>
                <p>Agriconnect</p>
                <h1>Rapport d’activité</h1>
            </div>
            <div class="report-meta">
                <span>{{ strtok(auth()->user()->nom, ' ') }} Agriculture</span>
                <span>Édité le {{ now()->translatedFormat('d F Y') }}</span>
                <span>Période : {{ $libelle }}</span>
            </div>
        </header>

        <section>
            <h2>1. Synthèse</h2>
            <div class="report-figures">
                <div><span>Encaissé</span><strong>{{ fcfa($total) }}</strong></div>
                <div><span>Ventes closes</span><strong>{{ $ventes->count() }}</strong></div>
                <div><span>Quantité vendue</span><strong>{{ number_format($kg, 0, ',', ' ') }} kg</strong></div>
                <div><span>Commandes ouvertes aujourd’hui</span><strong>{{ $enCours }}</strong></div>
            </div>
            <p class="report-note">{{ $appli }} vente(s) via l’appli · {{ $surPlace }} vente(s) sur place, à la ferme.</p>
        </section>

        <section>
            <h2>2. Encaissements — {{ $libelle }}</h2>
            <ul class="report-bars">
                @foreach ($jours as $jour)
                    <li>
                        <span>{{ $jour['label'] }}</span>
                        <i><b style="width: {{ $maxJour > 0 ? round($jour['montant'] / $maxJour * 100) : 0 }}%"></b></i>
                        <strong>{{ $jour['montant'] > 0 ? fcfa($jour['montant']) : '—' }}</strong>
                    </li>
                @endforeach
            </ul>
        </section>

        <section>
            <h2>3. Ventes par produit</h2>
            <div class="sheet-scroll">
                <table class="report-table">
                    <thead>
                        <tr><th>Produit</th><th>Ventes</th><th>Quantité</th><th>Montant</th><th>Part</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($parProduit as $ligne)
                            <tr>
                                <td>{{ $ligne['nom'] }}</td>
                                <td>{{ $ligne['n'] }}</td>
                                <td>{{ number_format($ligne['qte'], 0, ',', ' ') }} kg</td>
                                <td>{{ fcfa($ligne['montant']) }}</td>
                                <td>{{ $total > 0 ? number_format($ligne['montant'] / $total * 100, 0, ',', ' ') : 0 }} %</td>
                            </tr>
                        @empty
                            <tr><td colspan="5">Aucune vente terminée.</td></tr>
                        @endforelse
                    </tbody>
                    @if ($parProduit->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td>Total</td>
                                <td>{{ $ventes->count() }}</td>
                                <td>{{ number_format($kg, 0, ',', ' ') }} kg</td>
                                <td>{{ fcfa($total) }}</td>
                                <td>100 %</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </section>

        <section>
            <h2>4. Détail des ventes de la période</h2>
            <div class="sheet-scroll">
                <table class="report-table">
                    <thead>
                        <tr><th>Réf.</th><th>Date</th><th>Produit</th><th>Client</th><th>Canal</th><th>Montant</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($recentes as $vente)
                            <tr>
                                <td><a href="{{ route('vendeur.ventes.show', $vente) }}">#AG-{{ str_pad((string) $vente->id, 4, '0', STR_PAD_LEFT) }}</a></td>
                                <td>{{ $vente->pickup_at?->translatedFormat('d/m/Y') }}</td>
                                <td>{{ $vente->stock->product->name }}</td>
                                <td>{{ $vente->client }}</td>
                                <td>{{ $vente->canal === 'sur_place' ? 'Sur place' : 'Appli' }}</td>
                                <td>{{ fcfa($vente->quantity * $vente->unit_price) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6">Aucune vente à détailler.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </article>
@endsection
