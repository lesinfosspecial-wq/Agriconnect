@extends('layouts.admin')

@php
    $couleurs = ['#1f9d4e', '#f4a261', '#2a9d8f', '#e76f51', '#264653'];
    $prenom = strtok((string) auth()->user()->nom, ' ') ?: 'Awa';
@endphp

@section('title', 'Tableau de bord — Agriconnect')

@section('main')
    <section class="hero-row">
        <article class="hero-card hero-plain">
            <div class="hero-copy">
                <h1>Bonjour {{ $prenom }}</h1>
                <p>Vue d’ensemble de la plateforme Agriconnect</p>
                <span class="since">Administrateur</span>
            </div>
        </article>
        <a class="focus-card" href="{{ route('admin.identites') }}">
            <span class="kpi-ico ico-violet"><i class="bi bi-shield-check"></i></span>
            <small>À traiter</small>
            <strong>{{ $enAttente }}</strong>
            <span>compte(s) à vérifier</span>
        </a>
    </section>

    <section class="kpi-row">
        <article>
            <span class="kpi-ico ico-green"><i class="bi bi-basket"></i></span>
            <div>
                <p>Stocks en cours</p>
                <strong>{{ $stocksActifs }}</strong>
                <small>Annonces encore disponibles</small>
            </div>
        </article>
        <article>
            <span class="kpi-ico ico-blue"><i class="bi bi-cart"></i></span>
            <div>
                <p>Ventes réalisées</p>
                <strong>{{ $ventes }}</strong>
                <small>Collectées ou terminées</small>
            </div>
        </article>
        <article>
            <span class="kpi-ico ico-amber"><i class="bi bi-people"></i></span>
            <div>
                <p>Acheteurs actifs</p>
                <strong>{{ $acheteurs }}</strong>
                <small>{{ $vendeurs }} vendeur(s) inscrit(s)</small>
            </div>
        </article>
        <article>
            <span class="kpi-ico ico-violet"><i class="bi bi-shield-check"></i></span>
            <div>
                <p>Comptes à vérifier</p>
                <strong>{{ $enAttente }}</strong>
                <small>Badge d’identité en attente</small>
            </div>
        </article>
    </section>

    <section class="board board-charts">
        <article class="panel-card">
            <header>
                <h2>Évolution des ventes</h2>
                <span>7 derniers jours · FCFA</span>
            </header>
            <canvas id="chartVentes" height="150"></canvas>
        </article>
        <article class="panel-card">
            <header>
                <h2>Stocks par catégorie</h2>
            </header>
            <canvas id="chartCategories" height="150"></canvas>
        </article>
        <article class="panel-card">
            <header>
                <h2><i class="bi bi-bell"></i> Alertes</h2>
            </header>
            <ul class="todo">
                <li><i class="dot dot-violet"></i><a href="{{ route('admin.identites') }}">{{ $enAttente }} compte(s) à vérifier</a></li>
                <li><i class="dot dot-orange"></i><a href="{{ route('admin.annonces.index') }}">{{ $urgents->count() }} stock(s) urgent(s)</a></li>
                <li><i class="dot dot-green"></i><a href="{{ route('admin.transactions') }}">{{ $ventes }} vente(s) conclue(s)</a></li>
            </ul>
        </article>
    </section>

    <section class="board board-split">
        <article class="panel-card">
            <header>
                <h2>Derniers stocks ajoutés</h2>
                <a href="{{ route('admin.annonces.index') }}">Voir tout</a>
            </header>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Produit</th><th>Vendeur</th><th>Quantité</th><th>Lieu</th><th>Prix</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($recents as $stock)
                            <tr>
                                <td class="fw-semibold">{{ $stock->product->name }}</td>
                                <td>{{ $stock->seller->name }}</td>
                                <td>{{ number_format($stock->quantity, 0, ',', ' ') }} {{ $stock->unit }}</td>
                                <td>{{ $stock->quartier }}</td>
                                <td>{{ fcfa($stock->seller_price) }}</td>
                                <td class="text-end"><a class="btn btn-sm btn-outline-success" href="{{ route('admin.annonces.show', $stock) }}">Détail</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>
        <article class="panel-card">
            <header>
                <h2>Stocks en urgence</h2>
                <a href="{{ route('admin.annonces.index') }}">Voir tout</a>
            </header>
            <ul class="rows">
                @forelse ($urgents->take(4) as $stock)
                    <li>
                        <a href="{{ route('admin.annonces.show', $stock) }}">
                            <strong>{{ $stock->product->name }}</strong>
                            <small>{{ number_format($stock->availableQuantity(), 0, ',', ' ') }} {{ $stock->unit }} · {{ $stock->quartier }} · {{ $stock->joursRestants() }} j</small>
                        </a>
                    </li>
                @empty
                    <li><span class="muted">Aucun stock classé urgent.</span></li>
                @endforelse
            </ul>
        </article>
    </section>

    <section class="board">
        @foreach (['Produits les plus vendus' => $topProduits, 'Vendeurs les plus actifs' => $topVendeurs, 'Acheteurs les plus actifs' => $topAcheteurs] as $titre => $lignes)
            <article class="panel-card">
                <header><h2>{{ $titre }}</h2></header>
                <ul class="rows ranks">
                    @forelse ($lignes as $ligne)
                        <li>
                            <span>{{ $ligne['nom'] }}</span>
                            <b>{{ $ligne['qte'] ?? $ligne['n'] }}{{ isset($ligne['qte']) ? ' kg' : '' }}</b>
                        </li>
                    @empty
                        <li><span class="muted">Aucune donnée.</span></li>
                    @endforelse
                </ul>
            </article>
        @endforeach
    </section>
@endsection

@push('scripts')
<script>
    const ventes = @json($jours);
    new Chart(document.getElementById('chartVentes'), {
        type: 'bar',
        data: {
            labels: ventes.map(j => j.label),
            datasets: [{ data: ventes.map(j => j.montant), backgroundColor: '#1f9d4e', borderRadius: 8, maxBarThickness: 28 }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#eef3f0' } }, x: { grid: { display: false } } } }
    });
    new Chart(document.getElementById('chartCategories'), {
        type: 'doughnut',
        data: {
            labels: @json($categories->pluck('nom')),
            datasets: [{ data: @json($categories->pluck('qte')), backgroundColor: @json($couleurs), borderWidth: 0 }]
        },
        options: { cutout: '68%', plugins: { legend: { position: 'right', labels: { boxWidth: 10, font: { family: 'Plus Jakarta Sans' } } } } }
    });
</script>
@endpush
