@extends('layouts.producteur')

@section('title', 'Commandes — Agriconnect')

@section('main')
    @php
        $onglets = [
            'toutes' => 'Toutes',
            'nouvelles' => 'Nouvelles',
            'preparation' => 'En préparation',
            'livraison' => 'À retirer',
            'retirees' => 'Retirées',
        ];
        $tons = [
            'reservee' => 'reserve',
            'en_preparation' => 'wait',
            'en_collecte' => 'ship',
            'collectee' => 'dispo',
            'terminee' => 'done',
            'annulee' => 'epuise',
            'expiree' => 'epuise',
        ];
    @endphp
    <div class="catalog-head">
        <div>
            <h1>Commandes</h1>
            <p>Réservations reçues</p>
        </div>
    </div>

    <div class="sheet">
        <div class="filters">
            @foreach ($onglets as $cle => $libelle)
                <a href="{{ route('vendeur.commandes', $cle === 'toutes' ? [] : ['filtre' => $cle]) }}" @class(['is-on' => $filtre === $cle])>
                    {{ $libelle }} ({{ $compteurs[$cle] }})
                </a>
            @endforeach
        </div>
        <div class="sheet-scroll">
            <table class="catalog">
                <thead>
                    <tr>
                        <th>Commande</th>
                        <th>Quantité</th>
                        <th>Montant</th>
                        <th>Rendez-vous</th>
                        <th>Statut</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($liste as $commande)
                        <tr>
                            <td>
                                <div class="who">
                                    <img src="{{ $commande->stock->product->visuel() }}" alt="">
                                    <div>
                                        <strong>{{ $commande->stock->product->name }}</strong>
                                        <small>{{ $commande->buyer->name }} · #AG-{{ str_pad((string) $commande->id, 4, '0', STR_PAD_LEFT) }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ number_format($commande->quantity, 0, ',', ' ') }} {{ $commande->stock->unit }}</td>
                            <td class="num">{{ fcfa($commande->quantity * $commande->unit_price) }}</td>
                            <td>
                                {{ $commande->meetup_point }}
                                <small class="when">{{ $commande->pickup_at?->translatedFormat('d/m/Y H:i') }}</small>
                            </td>
                            <td><span class="pill pill-{{ $tons[$commande->status] ?? 'done' }}">{{ \App\Support\Libelles::commande($commande->status) }}</span></td>
                            <td>
                                <a class="detail-link" href="{{ route('vendeur.commandes.show', $commande) }}">Détail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-note">Aucune commande dans ce filtre.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
