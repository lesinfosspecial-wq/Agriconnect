@extends('layouts.accueil')

@section('title', 'Agriconnect — Des produits frais, au bon prix, au bon moment')

@section('main')
    <section class="hhero">
        <img class="hhero-photo" src="{{ asset('images/home/hero-farmer.jpg') }}" alt="Producteur souriant portant une caisse de tomates et de salades au marché">
        <div class="hhero-shade"></div>
        <div class="hhero-inner">
            <div class="hhero-copy">
                <p class="hpill">Plateforme intelligente de commercialisation des denrées agricoles périssables</p>
                <h1>Des produits frais,<br><em>au bon prix, au bon moment.</em></h1>
                <p class="hlead">Agriconnect met en relation les vendeurs de denrées fraîches avec des acheteurs proches, grâce à l’IA et aux données du marché.</p>
                <form class="hsearch" method="GET" action="{{ route('home') }}#produits">
                    <label class="hs-field">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M16 16.5 20 20.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        <input type="search" name="q" value="{{ $recherche }}" placeholder="Rechercher un produit, une localisation...">
                    </label>
                    <button class="hbtn hbtn-solid" type="submit">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M16 16.5 20 20.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        Rechercher
                    </button>
                </form>
                <ul class="hfeatures">
                    <li>
                        <span>
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M12 8v8M9.5 10.5c.6-1 1.5-1.4 2.5-1.4 1.4 0 2.4.8 2.4 2s-1 1.9-2.4 1.9-2.4.8-2.4 2 1 2 2.4 2c1 0 1.9-.4 2.5-1.3" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                        </span>
                        <strong>Prix intelligent</strong>
                        <small>Basé sur l’IA et le marché</small>
                    </li>
                    <li>
                        <span>
                            <svg viewBox="0 0 24 24"><path d="M12 21s6-5.2 6-10a6 6 0 1 0-12 0c0 4.8 6 10 6 10Z" fill="none" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="11" r="2" fill="none" stroke="currentColor" stroke-width="1.7"/></svg>
                        </span>
                        <strong>Proximité</strong>
                        <small>Acheteurs proches</small>
                    </li>
                    <li>
                        <span>
                            <svg viewBox="0 0 24 24"><path d="M12 3 5 6v6c0 4.2 2.8 7.2 7 9 4.2-1.8 7-4.8 7-9V6l-7-3Z" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="m9 12 2 2 4-4" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <strong>Transactions sécurisées</strong>
                        <small>Validation administrateur</small>
                    </li>
                    <li>
                        <span>
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M12 8v4l2.5 2" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                        </span>
                        <strong>Vente rapide</strong>
                        <small>Moins de pertes</small>
                    </li>
                </ul>
            </div>
            <p class="hscript">Soutenir<br>nos agriculteurs,<br>nourrir demain</p>
        </div>
    </section>

    <section class="hstats" aria-label="Chiffres de la plateforme">
        <div class="hstat">
            <span class="hstat-ico" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M8 11.5c1.6 0 2.6-1.6 3-3.5.4 1.9 1.4 3.5 3 3.5 1.4 1.8 2 3.4 2 4.6 0 2.6-2.2 4.4-5 4.4s-5-1.8-5-4.4c0-1.2.6-2.8 2-4.6Z" fill="none" stroke="currentColor" stroke-width="1.6"/></svg></span>
            <div>
                <strong>+ {{ number_format($disponibilites, 0, ',', ' ') }}</strong>
                <small>kg encore disponibles</small>
            </div>
        </div>
        <div class="hstat">
            <span class="hstat-ico" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M6.5 19c.8-2.8 2.8-4 5.5-4s4.7 1.2 5.5 4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></span>
            <div>
                <strong>+ {{ number_format($vendeurs, 0, ',', ' ') }}</strong>
                <small>vendeurs actifs</small>
            </div>
        </div>
        <div class="hstat">
            <span class="hstat-ico" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 7h15l-1.4 8.2a1 1 0 0 1-1 .8H8.2a1 1 0 0 1-1-.8L5 7Z" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M8 7 9 4h6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="9" cy="19" r="1.2" fill="currentColor"/><circle cx="17" cy="19" r="1.2" fill="currentColor"/></svg></span>
            <div>
                <strong>+ {{ number_format($acheteurs, 0, ',', ' ') }}</strong>
                <small>acheteurs inscrits</small>
            </div>
        </div>
        <div class="hstat">
            <span class="hstat-ico" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M4 12h16M12 4c2.2 2.4 3.3 5 3.3 8s-1.1 5.6-3.3 8c-2.2-2.4-3.3-5-3.3-8s1.1-5.6 3.3-8Z" fill="none" stroke="currentColor" stroke-width="1.6"/></svg></span>
            <div>
                <strong>Des marchés locaux</strong>
                <small>à Lomé et alentours</small>
            </div>
        </div>
        <div class="hstat hstat-green">
            <span class="hstat-ico hstat-ico-light" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M8 11.5c1.6 0 2.6-1.6 3-3.5.4 1.9 1.4 3.5 3 3.5 1.4 1.8 2 3.4 2 4.6 0 2.6-2.2 4.4-5 4.4s-5-1.8-5-4.4c0-1.2.6-2.8 2-4.6Z" fill="none" stroke="currentColor" stroke-width="1.6"/></svg></span>
            <p>Ensemble pour une agriculture plus rentable et durable.</p>
        </div>
    </section>

    <section class="hhow" id="parcours">
        <div class="hhow-intro">
            <p class="hkicker">Comment ça marche ?</p>
            <h2>Une solution simple et efficace</h2>
            <p>Agriconnect vous accompagne à chaque étape, de la déclaration du stock jusqu’à la vente finale.</p>
            <a class="hbtn hbtn-solid" href="#produits">Découvrir la plateforme <span aria-hidden="true">→</span></a>
        </div>
        <ol class="hsteps">
            <li>
                <span>
                    <svg viewBox="0 0 24 24"><path d="M7 3.5h7l4 4V20a1.5 1.5 0 0 1-1.5 1.5h-9.5A1.5 1.5 0 0 1 5.5 20V5A1.5 1.5 0 0 1 7 3.5Z" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M14 3.5V8h4.5M8 12h8M8 16h5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                </span>
                <strong>1. Déclarer un stock</strong>
                <p>Le vendeur renseigne les détails de son produit (quantité, fraîcheur, localisation, etc.).</p>
            </li>
            <li class="harrow" aria-hidden="true">→</li>
            <li>
                <span>
                    <svg viewBox="0 0 24 24"><path d="M9 8a3 3 0 1 1 3 3v1" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M8 14h8v5H8z" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M12 8V6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                </span>
                <strong>2. Prix recommandé par l’IA</strong>
                <p>Le système analyse les données du marché et propose un prix optimal.</p>
            </li>
            <li class="harrow" aria-hidden="true">→</li>
            <li>
                <span>
                    <svg viewBox="0 0 24 24"><path d="M12 3.5 5 6.5v5.5c0 4 2.7 6.8 7 8.5 4.3-1.7 7-4.5 7-8.5V6.5L12 3.5Z" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>
                </span>
                <strong>3. Validation admin</strong>
                <p>L’administrateur vérifie et valide l’offre avant publication.</p>
            </li>
            <li class="harrow" aria-hidden="true">→</li>
            <li>
                <span>
                    <svg viewBox="0 0 24 24"><path d="M8 11.5c1.6 0 2.6-1.6 3-3.5.4 1.9 1.4 3.5 3 3.5 1.4 1.8 2 3.4 2 4.6 0 2.6-2.2 4.4-5 4.4s-5-1.8-5-4.4c0-1.2.6-2.8 2-4.6Z" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>
                </span>
                <strong>4. Mise en relation et vente</strong>
                <p>Les acheteurs proches sont alertés, réservent et organisent la collecte.</p>
            </li>
        </ol>
    </section>

    <section class="hproducts" id="produits">
        <div class="hproducts-head">
            <h2>Nos produits</h2>
            <a href="{{ route('home') }}#galerie">Voir tous les produits <span aria-hidden="true">→</span></a>
        </div>
        <div class="hgallery" id="galerie">
            <div><img src="{{ asset('images/home/produit-tomates.jpg') }}" alt="Tomates"></div>
            <div><img src="{{ asset('images/home/produit-bananes.jpg') }}" alt="Bananes"></div>
            <div><img src="{{ asset('images/home/produit-ananas.jpg') }}" alt="Ananas"></div>
            <div><img src="{{ asset('images/home/produit-mangues.jpg') }}" alt="Mangues"></div>
        </div>

        <div class="hlive">
            @if ($recherche !== '')
                <h3>Résultats pour « {{ $recherche }} »</h3>
            @else
                <h3>Disponibles maintenant</h3>
            @endif
            @if ($offres->isEmpty())
                <p class="hempty">Aucune offre publiée ne correspond. Essayez un produit ou un quartier, comme Adidogomé ou Tokoin.</p>
            @else
                <div class="hlive-grid">
                    @foreach ($offres as $offre)
                        <article class="hlive-card">
                            <img src="{{ $offre->product->visuel() }}" alt="{{ $offre->product->name }}">
                            <div>
                                <p>{{ $offre->product->name }}</p>
                                <strong>{{ fcfa($offre->seller_price) }} / {{ $offre->unit }}</strong>
                                <small>{{ number_format($offre->availableQuantity(), 0, ',', ' ') }} {{ $offre->unit }} · {{ $offre->quartier }}</small>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="hbands">
        <article id="vendeurs">
            <p class="hkicker">Vendeurs</p>
            <h2>Écoulez un stock avant qu’il ne se perde.</h2>
            <p>Déclarez le produit, la quantité et la fraîcheur. Le prix recommandé laisse le dernier mot au vendeur, puis à l’administrateur.</p>
            <a class="hbtn hbtn-solid" href="{{ route('register') }}">Devenir vendeur</a>
        </article>
        <article id="acheteurs">
            <p class="hkicker">Acheteurs</p>
            <h2>Collectez près de chez vous.</h2>
            <p>Les offres urgentes du quartier apparaissent en premier. La quantité réservée est bloquée jusqu’au rendez-vous.</p>
            <a class="hbtn hbtn-line" href="{{ route('register') }}">Devenir acheteur</a>
        </article>
        <article id="propos">
            <p class="hkicker">À propos</p>
            <h2>Un prix expliqué, puis une validation humaine.</h2>
            <p>La régression linéaire estime une fourchette à partir de la saison, de l’urgence et des ventes passées. Rien n’est publié sans contrôle.</p>
        </article>
    </section>
@endsection
