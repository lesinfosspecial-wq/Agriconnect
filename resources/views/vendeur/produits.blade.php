@extends('layouts.producteur')

@section('title', 'Mes produits — Agriconnect')

@section('main')
    @php
        $onglets = [
            'tous' => 'Tous',
            'disponibles' => 'Disponibles',
            'reserves' => 'Réservés',
            'epuises' => 'Épuisés',
        ];
    @endphp
    <div class="catalog-head">
        <div>
            <h1>Mes produits</h1>
            <p>Gérez votre offre agricole</p>
        </div>
        <a class="add-product" href="{{ route('vendeur.stocks.create') }}"><i class="bi bi-plus-lg"></i> Ajouter un produit</a>
    </div>

    <div class="sheet">
        <div class="filters">
            @foreach ($onglets as $cle => $libelle)
                <a href="{{ route('vendeur.produits', array_filter(['filtre' => $cle === 'tous' ? null : $cle, 'q' => $q ?: null])) }}" @class(['is-on' => $filtre === $cle])>
                    {{ $libelle }} ({{ $compteurs[$cle] }})
                </a>
            @endforeach
        </div>
        <div class="sheet-scroll">
            <table class="catalog">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Quantité</th>
                        <th>Prix</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($liste as $stock)
                        @php
                            $famille = match (true) {
                                in_array($stock->status, ['reserve', 'partiellement_reserve'], true) => 'reserve',
                                $stock->availableQuantity() <= 0 || in_array($stock->status, ['vendu', 'expire', 'annule', 'suspendu'], true) => 'epuise',
                                default => 'dispo',
                            };
                            $statut = match ($famille) {
                                'reserve' => 'Réservé',
                                'epuise' => $stock->status === 'suspendu' ? 'Suspendu' : 'Épuisé',
                                default => 'Disponible',
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="who">
                                    <img src="{{ $stock->photo_path ? asset('storage/'.$stock->photo_path) : $stock->product->visuel() }}" alt="">
                                    <strong>{{ $stock->product->name }}</strong>
                                </div>
                            </td>
                            <td>{{ number_format($stock->quantity, 0, ',', ' ') }} {{ $stock->unit }}</td>
                            <td>{{ number_format($stock->seller_price, 0, ',', ' ') }} FCFA/{{ $stock->unit }}</td>
                            <td><span class="pill pill-{{ $famille }}">{{ $statut }}</span></td>
                            <td>
                                <div class="icon-acts">
                                    <a href="{{ route('vendeur.stocks.show', $stock) }}" aria-label="Voir {{ $stock->product->name }}"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('vendeur.stocks.edit', $stock) }}" aria-label="Modifier le prix"><i class="bi bi-pencil"></i></a>
                                    <form method="POST" action="{{ route('vendeur.stocks.retirer', $stock) }}" onsubmit="return confirm('Retirer ce produit de la vente ?')">
                                        @csrf
                                        <button type="submit" aria-label="Retirer"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-note">Aucun produit dans ce filtre.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
