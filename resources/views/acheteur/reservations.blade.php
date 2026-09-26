@extends('layouts.acheteur')

@section('title', 'Mes commandes — Agriconnect')

@section('main')
    <div class="hello">
        <h1>Mes commandes</h1>
        <p>Vos réservations et le moment du retrait.</p>
    </div>
    @if ($reservations->isEmpty())
        <p class="empty">Vous n’avez pas encore réservé. Les offres proches sont sur l’accueil.</p>
    @else
    <div style="overflow-x:auto">
            <table class="cmd">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Quantité</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Rendez-vous</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reservations as $reservation)
                        @php
                            $qte = (float) $reservation->quantity;
                            $qteAffichee = fmod($qte, 1.0) === 0.0 ? number_format($qte, 0, ',', ' ') : number_format($qte, 1, ',', ' ');
                            $ton = match ($reservation->status) {
                                'terminee', 'collectee' => 'ok',
                                'en_collecte' => 'ship',
                                'annulee', 'expiree' => 'bad',
                                default => 'wait',
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="who">
                                    <img src="{{ $reservation->stock->product->visuel() }}" alt="">
                                    <div>
                                        <strong>{{ $reservation->stock->product->name }}</strong>
                                        <small>{{ $reservation->stock->seller->name }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $qteAffichee }} {{ $reservation->stock->unit }}</td>
                            <td>{{ fcfa($reservation->quantity * $reservation->unit_price) }}</td>
                            <td><span class="badge badge-{{ $ton }}">{{ \App\Support\Libelles::commande($reservation->status) }}</span></td>
                            <td>{{ $reservation->pickup_at?->translatedFormat('d/m/Y H:i') }}</td>
                            <td><a class="step-btn" href="{{ route('acheteur.reservations.show', $reservation) }}">Détail</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
