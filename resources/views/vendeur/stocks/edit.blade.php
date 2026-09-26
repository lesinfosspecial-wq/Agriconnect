@extends('layouts.producteur')

@section('title', 'Modifier '.$stock->product->name.' — Agriconnect')
@section('canvas-class', 'app-space')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/agriconnect.css') }}">
@endpush

@section('main')
    <div class="inner-head">
        <div>
            <h1>Modifier {{ $stock->product->name }}</h1>
            <p>La photo actuelle sert à estimer la pourriture, puis le prix est comparé à la régression.</p>
        </div>
    </div>
    <form class="panel form-panel" method="POST" action="{{ route('vendeur.stocks.update', $stock) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="form-grid">
            <label class="field">
                <span>Produit</span>
                <select name="product_id" required>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected(old('product_id', $stock->product_id) == $product->id)>{{ $product->name }} ({{ $product->unit }})</option>
                    @endforeach
                </select>
            </label>
            <label class="field">
                <span>Quantité</span>
                <input type="number" name="quantity" min="1" step="0.5" value="{{ old('quantity', $stock->quantity) }}" required>
            </label>
            <label class="field">
                <span>Quartier du stock</span>
                <select name="quartier" required>
                    @foreach ($quartiers as $quartier)
                        <option value="{{ $quartier }}" @selected(old('quartier', $stock->quartier) === $quartier)>{{ $quartier }}</option>
                    @endforeach
                </select>
            </label>
            <label class="field">
                <span>Date de récolte</span>
                <input type="date" name="harvested_on" max="{{ now()->toDateString() }}" value="{{ old('harvested_on', $stock->harvested_on->toDateString()) }}" required>
            </label>
            <label class="field">
                <span>Prix (FCFA / unité)</span>
                <input type="number" name="seller_price" min="10" step="5" value="{{ old('seller_price', $stock->seller_price) }}" required>
            </label>
            <label class="field">
                <span>Prix minimum accepté</span>
                <input type="number" name="min_price" min="0" step="5" value="{{ old('min_price', $stock->min_price) }}" required>
            </label>
            <label class="field">
                <span>Mode de retrait</span>
                <select name="pickup_mode" required>
                    <option value="sur_place" @selected(old('pickup_mode', $stock->pickup_mode) === 'sur_place')>Retrait sur place</option>
                    <option value="point_rendez_vous" @selected(old('pickup_mode', $stock->pickup_mode) === 'point_rendez_vous')>Point de rendez-vous</option>
                </select>
            </label>
            <label class="field">
                <span>Photo actuelle du produit</span>
                <input type="file" name="photo" accept="image/*" required>
                @error('photo')<small>{{ $message }}</small>@enderror
            </label>
        </div>
        <p class="hint">Enregistrez pour lancer l’analyse : la photo est lue, la fraîcheur est estimée, puis votre prix est comparé à la fourchette de la régression linéaire.</p>
        <button class="btn" type="submit">Analyser la photo et le prix</button>
    </form>
@endsection
