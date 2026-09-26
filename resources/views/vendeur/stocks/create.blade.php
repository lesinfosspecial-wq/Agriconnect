@extends('layouts.producteur')

@section('title', 'Déclarer un stock — Agriconnect')
@section('canvas-class', 'app-space')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/agriconnect.css') }}">
@endpush

@section('main')
    <div class="inner-head">
        <div>
            <h1>Déclarer un stock</h1>
            <p>Nouvelle offre. Vous pouvez aussi le faire dans l’<a href="{{ route('vendeur.assistant', ['intention' => 'ajouter']) }}">assistant</a>, en français ou en éwé.</p>
        </div>
    </div>
    <form class="panel form-panel" method="POST" action="{{ route('vendeur.stocks.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="form-grid">
            <label class="field">
                <span>Produit</span>
                <select name="product_id" required>
                    <option value="">Choisir</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>{{ $product->name }} ({{ $product->unit }})</option>
                    @endforeach
                </select>
                @error('product_id')<small>{{ $message }}</small>@enderror
            </label>
            <label class="field">
                <span>Quantité</span>
                <input type="number" name="quantity" min="1" step="0.5" value="{{ old('quantity') }}" required>
                @error('quantity')<small>{{ $message }}</small>@enderror
            </label>
            <label class="field">
                <span>Quartier du stock</span>
                <select name="quartier" required>
                    <option value="">Choisir</option>
                    @foreach ($quartiers as $quartier)
                        <option value="{{ $quartier }}" @selected(old('quartier', auth()->user()->quartier) === $quartier)>{{ $quartier }}</option>
                    @endforeach
                </select>
                @error('quartier')<small>{{ $message }}</small>@enderror
            </label>
            <label class="field">
                <span>Date de récolte</span>
                <input type="date" name="harvested_on" max="{{ now()->toDateString() }}" value="{{ old('harvested_on', now()->toDateString()) }}" required>
                @error('harvested_on')<small>{{ $message }}</small>@enderror
            </label>
            <label class="field">
                <span>Prix souhaité (FCFA / unité)</span>
                <input type="number" name="seller_price" min="10" step="5" value="{{ old('seller_price') }}" required>
                @error('seller_price')<small>{{ $message }}</small>@enderror
            </label>
            <label class="field">
                <span>Prix minimum accepté</span>
                <input type="number" name="min_price" min="0" step="5" value="{{ old('min_price') }}" required>
                @error('min_price')<small>{{ $message }}</small>@enderror
            </label>
            <label class="field">
                <span>Mode de collecte</span>
                <select name="pickup_mode" required>
                    <option value="sur_place" @selected(old('pickup_mode', 'sur_place') === 'sur_place')>Retrait sur place</option>
                    <option value="point_rendez_vous" @selected(old('pickup_mode') === 'point_rendez_vous')>Point de rendez-vous</option>
                </select>
            </label>
            <label class="field">
                <span>Photo (facultative)</span>
                <input type="file" name="photo" accept="image/*">
                @error('photo')<small>{{ $message }}</small>@enderror
            </label>
        </div>
        <label class="field">
            <span>Précisions</span>
            <textarea name="notes" rows="3" maxlength="500">{{ old('notes') }}</textarea>
            @error('notes')<small>{{ $message }}</small>@enderror
        </label>
        <p class="hint">La date de récolte sert seulement à calculer combien de temps le produit se conserve, selon le type choisi. Le prix que vous indiquez est publié tel quel. L’analyse de la photo et du prix se lance quand vous modifiez ensuite le produit.</p>
        <button class="btn" type="submit">Publier le stock</button>
    </form>
@endsection
