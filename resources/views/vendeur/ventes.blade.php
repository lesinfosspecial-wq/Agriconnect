@extends('layouts.producteur')

@section('title', 'Ventes — Agriconnect')

@section('main')
    <div class="catalog-head">
        <div>
            <h1>Ventes</h1>
            <p>Commandes déjà terminées</p>
        </div>
    </div>

    <section class="kpi-row ventes-kpi">
        <article>
            <span class="kpi-ico ico-green"><i class="bi bi-wallet2"></i></span>
            <div>
                <p>Encaissé</p>
                <strong>{{ fcfa($total) }}</strong>
            </div>
        </article>
        <article>
            <span class="kpi-ico ico-blue"><i class="bi bi-bag-check"></i></span>
            <div>
                <p>Ventes</p>
                <strong>{{ $nombre }}</strong>
            </div>
        </article>
        <article>
            <span class="kpi-ico ico-amber"><i class="bi bi-trophy"></i></span>
            <div>
                <p>Produit le plus vendu</p>
                <strong>{{ $top['nom'] ?? '—' }}</strong>
                @if ($top)
                    <small>{{ number_format($top['qte'], 0, ',', ' ') }} kg</small>
                @endif
            </div>
        </article>
    </section>

    <details class="saisie" @if ($errors->any()) open @endif>
        <summary>Enregistrer une vente sur place</summary>
        <form method="POST" action="{{ route('vendeur.ventes.store') }}">
            @csrf
            <p>Pour un acheteur venu à la ferme, sans réservation dans l’appli.</p>
            <label>
                <span>Stock</span>
                <select name="stock_id" required>
                    <option value="">Choisir</option>
                    @foreach ($stocks as $stock)
                        <option value="{{ $stock->id }}" @selected(old('stock_id') == $stock->id)>
                            {{ $stock->product->name }} · {{ number_format($stock->availableQuantity(), 0, ',', ' ') }} {{ $stock->unit }} libres · {{ fcfa($stock->seller_price) }}/{{ $stock->unit }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Quantité</span>
                <input type="number" name="quantite" min="0.5" step="0.5" value="{{ old('quantite') }}" required>
            </label>
            <label>
                <span>Nom du client</span>
                <input type="text" name="nom_client" maxlength="120" value="{{ old('nom_client') }}" placeholder="Nom de la personne" required>
            </label>
            <button class="step-btn" type="submit">Enregistrer</button>
        </form>
    </details>

    <div class="sheet">
        <div class="sheet-scroll">
            <table class="catalog">
                <thead>
                    <tr>
                        <th>Vente</th>
                        <th>Quantité</th>
                        <th>Montant</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ventes as $vente)
                        <tr>
                            <td>
                                <div class="who">
                                    <img src="{{ $vente->stock->product->visuel() }}" alt="">
                                    <div>
                                        <strong>{{ $vente->stock->product->name }}</strong>
                                        <small>{{ $vente->client }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ number_format($vente->quantity, 0, ',', ' ') }} {{ $vente->stock->unit }}</td>
                            <td class="num">{{ fcfa($vente->quantity * $vente->unit_price) }}</td>
                            <td>{{ $vente->pickup_at?->translatedFormat('d/m/Y') }}</td>
                            <td class="acts-vente">
                                @if ($vente->canal === 'sur_place')
                                    <span class="pill pill-wait">Sur place</span>
                                @else
                                    <span class="pill pill-dispo">Appli</span>
                                @endif
                                <a class="detail-link" href="{{ route('vendeur.ventes.show', $vente) }}">Détail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-note">Aucune vente terminée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
