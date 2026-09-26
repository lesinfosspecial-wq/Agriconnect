@extends('layouts.acheteur')

@section('title', $stock->product->name.' — Agriconnect')

@section('main')
    @php
        $rayon = (float) (auth()->user()->rayon_km ?: 25);
        $libre = $stock->availableQuantity() > 0 && in_array($stock->status, ['publie', 'partiellement_reserve'], true);
        $qte = (float) $stock->availableQuantity();
        $qteAffichee = fmod($qte, 1.0) === 0.0 ? number_format($qte, 0, ',', ' ') : number_format($qte, 1, ',', ' ');
        $depart = (float) old('quantity', min(10, $qte));
        $confiance = $stock->seller->niveauConfiance();
    @endphp
    <a class="back" href="{{ route('acheteur.dashboard') }}">Retour aux offres</a>
    <section class="detail">
        <div class="detail-photo">
            <img src="{{ $stock->photo_path ? asset('storage/'.$stock->photo_path) : $stock->product->visuel() }}" alt="{{ $stock->product->name }}">
            <span class="badge badge-{{ $stock->urgency }}">{{ \App\Support\Libelles::urgence($stock->urgency) }}</span>
        </div>
        <div class="detail-info">
            <h1>{{ $stock->product->name }}</h1>
            <p class="big-price">{{ fcfa($stock->seller_price) }} <small>/ {{ $stock->unit }}</small></p>
            <form method="POST" action="{{ route('acheteur.favoris.toggle', $stock) }}">
                @csrf
                <button class="step-btn ghost" type="submit">{{ $favori ? 'Retirer des favoris' : 'Ajouter aux favoris' }}</button>
            </form>
            <div class="tiles">
                <div><span>Disponible</span><strong>{{ $qteAffichee }} {{ $stock->unit }}</strong></div>
                <div><span>Distance</span><strong>{{ km($distance) }}</strong></div>
                <div><span>Confiance</span><strong>{{ $confiance['score'] }}/100 · {{ $confiance['libelle'] }}</strong></div>
                <div><span>Producteur</span><strong>{{ $stock->seller->name }}</strong></div>
                <div><span>Téléphone</span><strong>{{ $stock->seller->phone }}</strong></div>
                <div><span>Identité</span><strong>{{ \App\Support\Libelles::verification($stock->seller->verification) }}</strong></div>
                <div><span>Fraîcheur</span><strong>{{ \App\Support\Libelles::fraicheur($stock->freshness) }}</strong></div>
                <div><span>Retrait</span><strong>{{ $stock->quartier }} · {{ \App\Support\Libelles::collecte($stock->pickup_mode) }}</strong></div>
                <div><span>À retirer avant</span><strong>{{ $stock->expires_on->translatedFormat('d F Y') }}</strong></div>
            </div>
            @if ($maps)
                <a class="step-btn ghost" href="{{ $maps }}" target="_blank" rel="noopener">Voir l’itinéraire</a>
            @endif

            <div class="reserve-box">
                <h2>Réserver</h2>
                @if ($distance !== null && $distance > $rayon)
                    <p class="empty">Cette offre est hors de votre rayon de {{ number_format($rayon, 0, ',', ' ') }} km.</p>
                @elseif (! $libre)
                    <p class="empty">Cette offre n’a plus de quantité libre.</p>
                @else
                    <p class="empty">La quantité choisie est bloquée pour vous jusqu’au rendez-vous.</p>
                    <form method="POST" action="{{ route('acheteur.reservations.store', $stock) }}">
                        @csrf
                        <label class="field">
                            <span>Quantité ({{ $stock->unit }})</span>
                            <input type="number" name="quantity" min="0.5" max="{{ $qte }}" step="0.5" value="{{ $depart }}" required>
                            @error('quantity')<small>{{ $message }}</small>@enderror
                        </label>
                        <p class="total-line"><strong id="montant-reservation" data-prix="{{ $stock->seller_price }}">{{ fcfa($stock->seller_price * $depart) }}</strong> <span>estimés</span></p>
                        <button class="step-btn full" type="submit">Réserver</button>
                    </form>
                    <script>
                        const quantite = document.querySelector('input[name="quantity"]');
                        const montant = document.querySelector("#montant-reservation");
                        const prix = Number(montant?.dataset.prix || 0);
                        const formater = (valeur) => new Intl.NumberFormat("fr-FR").format(Math.round(valeur)) + " FCFA";
                        quantite?.addEventListener("input", () => {
                            const qteSaisie = Number(quantite.value);
                            montant.textContent = Number.isFinite(qteSaisie) ? formater(qteSaisie * prix) : "—";
                        });
                    </script>
                @endif
            </div>
        </div>
    </section>
@endsection
