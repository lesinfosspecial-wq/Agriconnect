@extends('layouts.producteur')

@section('title', 'Livraisons — Agriconnect')

@section('main')
    <div class="catalog-head">
        <div>
            <h1>Livraisons</h1>
            <p>Produits en cours de livraison</p>
        </div>
    </div>

    <div class="sheet">
        <div class="sheet-scroll">
            <table class="catalog">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Quantité</th>
                        <th>Client</th>
                        <th>Rendez-vous</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($livraisons as $livraison)
                        <tr>
                            <td>
                                <div class="who">
                                    <img src="{{ $livraison->stock->product->visuel() }}" alt="">
                                    <div>
                                        <strong>{{ $livraison->stock->product->name }}</strong>
                                        <small>#AG-{{ str_pad((string) $livraison->id, 4, '0', STR_PAD_LEFT) }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ number_format($livraison->quantity, 0, ',', ' ') }} {{ $livraison->stock->unit }}</td>
                            <td>{{ $livraison->client }}</td>
                            <td>
                                {{ $livraison->meetup_point }}
                                <small class="when">{{ $livraison->pickup_at?->translatedFormat('d/m/Y H:i') }}</small>
                            </td>
                            <td><a class="detail-link" href="{{ route('vendeur.commandes.show', $livraison) }}">Détail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-note">Aucun produit en cours de livraison.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
