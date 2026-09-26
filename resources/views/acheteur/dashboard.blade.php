@extends('layouts.acheteur')

@section('title', 'Offres proches — Agriconnect')

@section('main')
    <section class="vitrine">
        <div class="vitrine-main" style="background-image:url('{{ asset('images/home/produit-legumes.jpg') }}')">
            <div class="vitrine-copy">
                <span>Fraîcheur garantie</span>
                <h1>Des produits frais, près de chez vous !</h1>
                <p>Trouvez rapidement des denrées agricoles à prix réduits grâce à nos vendeurs de proximité.</p>
                <a href="#offres">Voir les offres <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </section>

    <div class="cats" id="cats">
        @foreach ([
            ['', 'bi-grid', 'Tout'],
            ['Légume', 'bi-flower1', 'Légumes'],
            ['Fruit', 'bi-apple', 'Fruits'],
            ['Céréale', 'bi-asterisk', 'Céréales'],
            ['Tubercule', 'bi-circle', 'Tubercules'],
            ['__autres', 'bi-three-dots', 'Autres'],
        ] as [$cat, $icone, $libelle])
            <a href="#offres" data-cat="{{ $cat }}" @class(['is-on' => $categorie === $cat])>
                <i class="bi {{ $icone }}"></i>{{ $libelle }}
            </a>
        @endforeach
    </div>

    <div class="market" id="offres">
        <div>
            <h2 class="section-title">{{ $titre }}</h2>
            @if ($offres->isEmpty())
                <p class="empty">Aucune offre dans votre rayon pour le moment.</p>
            @else
                <div class="offer-grid" id="offer-grid">
                    @foreach ($offres as $offre)
                        <article class="offer"
                            data-cat="{{ $offre->product->categorie }}"
                            data-search="{{ mb_strtolower($offre->product->name.' '.$offre->quartier.' '.$offre->ville.' '.$offre->seller->name) }}">
                            <form method="POST" action="{{ route('acheteur.favoris.toggle', $offre) }}" class="heart-form">
                                @csrf
                                <button type="submit" aria-label="Favori">
                                    <i class="bi {{ $favoris->contains($offre->id) ? 'bi-heart-fill' : 'bi-heart' }}"></i>
                                </button>
                            </form>
                            <a href="{{ route('acheteur.offres.show', $offre) }}">
                            <figure>
                                <img src="{{ $offre->photo_path ? asset('storage/'.$offre->photo_path) : $offre->product->visuel() }}" alt="">
                                <span class="badge badge-{{ $offre->urgency }}">{{ \App\Support\Libelles::urgence($offre->urgency) }}</span>
                            </figure>
                            <div>
                                <strong>{{ $offre->product->name }}</strong>
                                <small>{{ number_format($offre->availableQuantity(), 0, ',', ' ') }} {{ $offre->unit }} · {{ fcfa($offre->seller_price) }}/{{ $offre->unit }}</small>
                                <small class="loc"><i class="bi bi-geo-alt"></i> {{ $offre->ville ?: 'Lomé' }} ({{ km($offre->distance_km) }})</small>
                                <small>{{ $offre->seller->name }} · {{ \App\Support\Libelles::verification($offre->seller->verification) }}</small>
                                <span class="cta">Voir l'offre</span>
                            </div>
                            </a>
                        </article>
                    @endforeach
                </div>
                <p class="empty" id="aucun" hidden>Aucune offre ne correspond.</p>
            @endif
            <a class="farmer" href="#offres">
                <img src="{{ asset('images/home/hero-farmer.jpg') }}" alt="">
                <div>
                    <strong>Soutenons ensemble nos agriculteurs !</strong>
                    <small>Moins de pertes, plus de revenus.</small>
                </div>
            </a>
        </div>
    </div>
    <script>
        const cartes = [...document.querySelectorAll(".offer")];
        const aucun = document.querySelector("#aucun");
        const champ = document.querySelector('.buy-search input[name="q"]');
        const connues = ["Légume", "Fruit", "Céréale", "Tubercule"];
        let categorie = "";
        let terme = (champ?.value || "").trim().toLocaleLowerCase();

        const filtrer = () => {
            let visibles = 0;
            cartes.forEach((carte) => {
                const cat = carte.dataset.cat || "";
                const okCat = categorie === ""
                    || (categorie === "__autres" ? !connues.includes(cat) : cat === categorie);
                const okTexte = terme === "" || (carte.dataset.search || "").includes(terme);
                const visible = okCat && okTexte;
                carte.hidden = !visible;
                if (visible) visibles += 1;
            });
            if (aucun) aucun.hidden = visibles !== 0;
        };

        document.querySelectorAll("#cats a").forEach((lienCat) => {
            lienCat.addEventListener("click", (event) => {
                event.preventDefault();
                categorie = lienCat.dataset.cat || "";
                document.querySelectorAll("#cats a").forEach((a) => a.classList.toggle("is-on", a === lienCat));
                filtrer();
            });
        });

        champ?.addEventListener("input", () => {
            terme = champ.value.trim().toLocaleLowerCase();
            filtrer();
        });
    </script>
@endsection
