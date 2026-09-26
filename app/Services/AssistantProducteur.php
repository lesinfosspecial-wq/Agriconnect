<?php

namespace App\Services;

use App\Models\MarketNotification;
use App\Models\Order;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Support\Libelles;
use App\Support\Lieux;
use App\Support\Urgence;
use Illuminate\Support\Carbon;

class AssistantProducteur
{
    public function __construct(
        private PricePredictor $prix,
        private Meteo $meteo,
        private EweIntentModel $ewe,
    ) {}

    public function etatInitial(User $producteur): array
    {
        return [
            'mode' => null,
            'pret' => false,
            'brouillon' => $this->brouillonVide(),
            'messages' => [[
                'de' => 'assistant',
                'texte' => 'Bonjour '.strtok((string) $producteur->nom, ' ').". Je suis l'assistant de Agriconnect.\n\nAjouter un produit, ou discuter de vos stocks, de vos ventes et de la façon de les écouler ?",
                'heure' => now()->format('H:i'),
            ]],
        ];
    }

    public function ouvrir(array $etat, string $mode, User $producteur): array
    {
        $etat['mode'] = $mode;
        $etat['pret'] = false;
        $etat['brouillon'] = $this->brouillonVide();
        $etat['messages'] = [[
            'de' => 'assistant',
            'heure' => now()->format('H:i'),
            'texte' => match ($mode) {
                'ajouter' => "Dites-moi ce que vous voulez publier : produit, quantité, et si vous les avez, le prix, le quartier et la date de récolte.\nExemple : 80 kg de tomates à 700 FCFA, récoltées aujourd'hui à ".$producteur->quartier.'.',
                'ewe' => "Woezɔ. Eʋegbe ko me le fom.\nDzra nu : 80 kilo timáti, egbe, 700 FCFA, ".$producteur->quartier.".\nNe ésɔ la, gblɔ « Ẽ ». Ne nye mé se egɔme o la, magblɔe ake.",
                default => "Posez votre question : vos ventes, un stock à écouler, un prix, vos commandes, la météo, ou comment utiliser Agriconnect.",
            },
        ]];

        return $this->couper($etat);
    }

    public function repondre(User $producteur, string $brut, array $etat): array
    {
        $brut = trim($brut);
        $etat['messages'][] = ['de' => 'producteur', 'texte' => $brut, 'heure' => now()->format('H:i')];
        $mode = $this->modeDemande($brut);

        if ($mode !== null) {
            $etat = $this->ouvrir($etat, $mode, $producteur);

            return $this->couper($etat);
        }

        if (($etat['mode'] ?? null) === null) {
            $etat['mode'] = $this->devinerMode($brut);
        }

        $texte = match ($etat['mode'] ?? null) {
            'ajouter' => $this->ajout($producteur, $brut, $etat),
            'discuter' => $this->discuter($producteur, $brut),
            'ewe' => $this->conversationEwe($producteur, $brut, $etat),
            default => "Choisissez d'abord : ajouter un produit, discuter, ou parler en éwé.",
        };

        $etat['messages'][] = ['de' => 'assistant', 'texte' => $texte, 'heure' => now()->format('H:i')];

        return $this->couper($etat);
    }

    private function ajout(User $producteur, string $brut, array &$etat): string
    {
        if ($etat['pret'] && $this->estConfirmation($brut)) {
            return $this->publier($producteur, $etat);
        }

        if ($etat['pret'] && $this->estRefus($brut)) {
            $etat['pret'] = false;

            return 'La fiche n’est pas enregistrée. Dites ce qu’il faut changer : le prix, la quantité, le quartier, la date ou le retrait.';
        }

        $lu = $this->lire($brut);
        $brouillon = $etat['brouillon'];

        if ($lu['date_refusee']) {
            return 'La date de récolte ne peut pas être après aujourd’hui. Donnez « aujourd’hui », « hier », ou une date comme 20/09/2026.';
        }

        if ($lu['quartier_inconnu'] !== null) {
            return 'Je n’ai pas reconnu le quartier « '.$lu['quartier_inconnu'].' ». Voici ceux que je connais : '.implode(', ', Lieux::noms()).'.';
        }

        if ($lu['ambigu']) {
            return 'J’ai vu plusieurs nombres et je ne sais pas lequel est la quantité. Reformulez, par exemple : 80 kg de tomates à 700 FCFA.';
        }

        if ($lu['min_invalide']) {
            return 'Le prix plancher doit rester inférieur ou égal au prix de vente. Donnez un autre plancher, ou seulement le prix de vente.';
        }

        if ($lu['product'] === null && $lu['inconnu'] !== null && ($brouillon['product_id'] ?? null) === null) {
            $this->appliquer($brouillon, $lu, $producteur);
            $etat['brouillon'] = $brouillon;

            return 'Je n’ai pas reconnu « '.$lu['inconnu'].' ». Les produits sont : '.$this->nomsProduits().'. Lequel voulez-vous enregistrer ?';
        }

        $this->appliquer($brouillon, $lu, $producteur);
        $etat['brouillon'] = $brouillon;
        $etat['pret'] = false;

        if (($brouillon['product_id'] ?? null) === null) {
            return 'Quel produit du catalogue voulez-vous publier ? '.$this->nomsProduits().'.';
        }

        if (($brouillon['quantity'] ?? null) === null) {
            $produit = Product::find($brouillon['product_id']);

            return 'J’ai retenu '.($produit->nom ?? 'ce produit').'. Quelle quantité, en '.($produit->unite ?? 'kg').' ?';
        }

        if ($this->complet($brouillon)) {
            $etat['pret'] = true;

            return $this->proposition($brouillon);
        }

        return 'Il me manque encore un point pour proposer la fiche. Précisez le produit et la quantité.';
    }

    private function conversationEwe(User $producteur, string $brut, array &$etat): string
    {
        if ($etat['pret'] && ($this->ewe->estOui($brut) || $this->estConfirmation($brut))) {
            $copie = $etat['brouillon'];
            $texte = $this->publier($producteur, $etat);
            if (! str_contains($texte, 'est publié')) {
                return 'Nye mé se egɔme o. Gblɔ nu si nèdzra kple kilo.';
            }

            $produit = Product::find($copie['product_id']);

            return $this->ewe->nom((string) $produit?->nom).' le asinye fifia : '.number_format((float) $copie['quantity'], 0, ',', ' ').' kg, '.fcfa($copie['seller_price']).' / kg, '.$copie['quartier'].". Núƒlelawo se nya la. Akpe.\nNe èdi nu bubu la, gblɔe.";
        }

        if ($etat['pret'] && ($this->ewe->estNon($brut) || $this->estRefus($brut))) {
            $etat['pret'] = false;

            return 'Mete ɖe edzi o. Gblɔ nu si nàtrɔ : ga, kilo, alo teƒe.';
        }

        $classe = $this->ewe->predire($brut)['intention'];
        if (in_array($classe, ['saluer', 'prix', 'ventes', 'stocks', 'commandes', 'meteo', 'guide'], true)) {
            return $this->reponseEwe($producteur, $classe, $brut);
        }

        $reponse = $this->ajout($producteur, $this->ewe->versFrancais($brut), $etat);
        if ($etat['pret']) {
            return $this->propositionEwe($etat['brouillon']);
        }

        if (str_contains($reponse, 'après aujourd')) {
            return 'Ŋkeke mate ŋu ayi ŋgɔ o. Gblɔ egbe.';
        }
        if (str_contains($reponse, 'quartier')) {
            return 'Nye mé se teƒe la o. Adidogomé, Bè, Agoè, Tokoin, alo '.$producteur->quartier.'.';
        }
        if (str_contains($reponse, 'plusieurs nombres')) {
            return 'Nye mé se alidzi o. Gblɔ : 80 kilo timáti, 700 FCFA.';
        }
        if (str_contains($reponse, 'pas reconnu') || str_contains($reponse, 'Quel produit')) {
            return 'Nu ka? '.$this->ewe->catalogue().'.';
        }
        if (str_contains($reponse, 'Quelle quantité')) {
            $produit = Product::find($etat['brouillon']['product_id'] ?? 0);

            return $this->ewe->nom((string) ($produit->nom ?? '')).'. Kilo nenie?';
        }

        return 'Nye mé se egɔme o. Gblɔ ake : 80 kilo timáti, egbe, '.$producteur->quartier.'.';
    }

    private function propositionEwe(array $brouillon): string
    {
        $produit = Product::findOrFail($brouillon['product_id']);

        return "Me ɖo nu sia gbã. Mele ɖem ɖe edzi o.\n"
            .'Nusi : '.$this->ewe->nom($produit->nom)."\n"
            .'Kilo : '.number_format((float) $brouillon['quantity'], 0, ',', ' ')." kg\n"
            .'Ga : '.fcfa($brouillon['seller_price']).' / kg'."\n"
            .'Teƒe : '.$brouillon['quartier']."\n"
            .'Ŋkeke : '.Carbon::parse($brouillon['harvested_on'])->translatedFormat('d F Y')."\n"
            .'Ne ésɔ la, gblɔ « Ẽ ». Ne ao la, gblɔ ao.';
    }

    private function reponseEwe(User $producteur, string $intention, string $brut): string
    {
        $stocks = Stock::query()->with('product')->where('seller_id', $producteur->id)->get();
        $commandes = Order::query()->with('stock.product')->whereHas('stock', fn ($q) => $q->where('seller_id', $producteur->id))->get();

        return match ($intention) {
            'saluer' => 'Woezɔ '.strtok((string) $producteur->nom, ' ').'. Bia ga, xexe, nu si le asinye, nufle, alo yame. Alo dzra nu yeye.',
            'prix' => $this->prixEwe($producteur, $brut, $stocks),
            'ventes' => $this->ventesEwe($commandes),
            'stocks' => $this->stocksEwe($stocks),
            'commandes' => $this->commandesEwe($commandes),
            'meteo' => $this->meteoEwe($producteur),
            default => 'Dzra nu gblɔe, emegbe gblɔ Ẽ. Àte ŋu bia ga, xexe, nufle, kple yame hã.',
        };
    }

    private function prixEwe(User $producteur, string $brut, $stocks): string
    {
        $lu = $this->lire($this->ewe->versFrancais($brut));
        $produit = $lu['product'] ?? $stocks->first()?->product;
        if (! $produit) {
            return 'Nu ka ƒe ga? '.$this->ewe->catalogue().'.';
        }

        $stock = $stocks->first(fn (Stock $s) => $s->product_id === $produit->id && $s->availableQuantity() > 0);
        $jours = $stock ? $stock->joursRestants() : max(1, (int) round($produit->conservation_heures_reference / 24));
        $lecture = $this->estimation($produit, $jours, $stock ? (int) ($stock->freshness ?: 5) : 5, $stock ? (float) $stock->availableQuantity() : 50);
        $phrase = $this->ewe->nom($produit->nom).' ƒe ga : '.fcfa($lecture['recommended']).' / kg.';
        if ($stock) {
            $phrase .= ' Wò ga enye '.fcfa($stock->seller_price).', '.$stock->quartier.'.';
        }

        return $phrase;
    }

    private function ventesEwe($commandes): string
    {
        $ventes = $commandes->filter(fn (Order $order) => $order->statut === Order::TERMINEE && $order->created_at && $order->created_at->greaterThanOrEqualTo(now()->subDays(29)->startOfDay()));
        $total = $ventes->sum(fn (Order $order) => $order->quantite * $order->prix_unitaire);

        return 'Xexe le ŋkeke 30 me : '.fcfa($total).'. '.$ventes->count().' dzodzro.';
    }

    private function stocksEwe($stocks): string
    {
        $enLigne = $stocks->filter(fn (Stock $stock) => in_array($stock->statut, [Stock::PUBLIE, 'partiellement_reserve'], true) && $stock->availableQuantity() > 0);
        if ($enLigne->isEmpty()) {
            return 'Nu aɖeke mele asinye o. Dzra nu yeye, emegbe gblɔ Ẽ.';
        }

        return $enLigne->sortBy(fn (Stock $stock) => $stock->joursRestants())->take(3)->map(function (Stock $stock) {
            return $this->ewe->nom($stock->product->nom).' le '.$stock->quartier.' : '.number_format($stock->availableQuantity(), 0, ',', ' ').' kg, '.fcfa($stock->seller_price).', '.$stock->joursRestants().' ŋkeke';
        })->implode("\n");
    }

    private function commandesEwe($commandes): string
    {
        $ouvertes = $commandes->whereNotIn('statut', [Order::TERMINEE, Order::ANNULEE]);
        if ($ouvertes->isEmpty()) {
            return 'Nufle aɖeke mele mɔ dzi o.';
        }

        return 'Nufle '.$ouvertes->count().' le mɔ dzi.';
    }

    private function meteoEwe(User $producteur): string
    {
        $releve = $this->meteo->pour($producteur);

        return $releve
            ? 'Yame le '.$releve['lieu'].' : '.$releve['temperature'].' °C, '.$releve['texte'].'.'
            : 'Nye mé se yame o.';
    }

    private function publier(User $producteur, array &$etat): string
    {
        $brouillon = $etat['brouillon'];
        $produit = Product::find($brouillon['product_id'] ?? 0);

        if (! $produit || ! $this->complet($brouillon)) {
            $etat['pret'] = false;

            return 'Je ne peux pas encore enregistrer : le produit ou la quantité manque. Reprenez, par exemple : 80 kg de tomates.';
        }

        $lieu = Lieux::coordonnees((string) $brouillon['quartier']);
        $recolte = Carbon::parse($brouillon['harvested_on'])->startOfDay();
        $limite = $recolte->copy()->addHours((int) $produit->conservation_heures_reference);
        $jours = max(0, (int) floor(($limite->timestamp - now()->startOfDay()->timestamp) / 86400));

        $stock = Stock::create([
            'seller_id' => $producteur->id,
            'product_id' => $produit->id,
            'quantite' => $brouillon['quantity'],
            'quantite_disponible' => $brouillon['quantity'],
            'quantite_bloquee' => 0,
            'unite' => $produit->unite,
            'quartier' => $brouillon['quartier'],
            'ville' => Lieux::ville($brouillon['quartier']),
            'latitude' => $lieu['latitude'],
            'longitude' => $lieu['longitude'],
            'recolte_le' => $recolte,
            'expiration_estimee' => $limite,
            'fraicheur' => 5,
            'prix_souhaite' => $brouillon['seller_price'],
            'prix_vendeur' => $brouillon['seller_price'],
            'prix_minimum' => $brouillon['min_price'],
            'statut' => Stock::PUBLIE,
            'mode_collecte' => $brouillon['pickup_mode'],
            'urgence' => Urgence::classify($jours, 5),
        ]);

        $this->prevenir($stock, $produit->nom.' frais disponible à '.$stock->quartier.' : '.fcfa($stock->prix_vendeur).'/'.$stock->unite.'.');

        $etat['pret'] = false;
        $etat['brouillon'] = $this->brouillonVide();

        return $produit->nom.' est publié : '.number_format((float) $stock->quantite, 0, ',', ' ').' '.$stock->unite.' à '.fcfa($stock->prix_vendeur).' / '.$stock->unite.', '.$stock->quartier.". Les acheteurs sont prévenus.\nLa photo et la relecture du prix se font quand vous modifiez l’offre.\nVous pouvez en ajouter un autre, ou passer en discussion.";
    }

    private function proposition(array $brouillon): string
    {
        $produit = Product::findOrFail($brouillon['product_id']);
        $limite = Carbon::parse($brouillon['harvested_on'])->addHours((int) $produit->conservation_heures_reference);

        $lignes = [
            'Voici la fiche que je propose. Rien n’est enregistré tant que vous n’avez pas dit oui.',
            'Produit : '.$produit->nom,
            'Quantité : '.number_format((float) $brouillon['quantity'], 0, ',', ' ').' '.$produit->unite.' (donnée par vous)',
            'Prix : '.fcfa($brouillon['seller_price']).' / '.$produit->unite.' ('.$this->origine($brouillon['prix_source']).')',
            'Prix plancher : '.fcfa($brouillon['min_price']).' ('.$this->origine($brouillon['plancher_source']).')',
            'Quartier : '.$brouillon['quartier'].' ('.$this->origine($brouillon['quartier_source']).')',
            'Récolte : '.Carbon::parse($brouillon['harvested_on'])->translatedFormat('d F Y').' ('.$this->origine($brouillon['date_source']).')',
            'Limite estimée : '.$limite->translatedFormat('d F Y'),
            'Retrait : '.Libelles::collecte($brouillon['pickup_mode']).' ('.$this->origine($brouillon['retrait_source']).')',
            'Dites « oui, c’est bon » pour publier. Pour corriger : « prix 650 », « quantité 40 », « quartier Bè » ou « hier ».',
        ];

        return implode("\n", $lignes);
    }

    private function discuter(User $producteur, string $brut): string
    {
        $t = $this->texte($brut);
        $stocks = Stock::query()->with('product')->where('seller_id', $producteur->id)->get();
        $commandes = Order::query()->with('stock.product')->whereHas('stock', fn ($q) => $q->where('seller_id', $producteur->id))->get();

        if ($this->contient($t, ['meteo', 'temps', 'pluie', 'chaleur', 'soleil'])) {
            $releve = $this->meteo->pour($producteur);

            return $releve
                ? 'À '.$releve['lieu'].' : '.$releve['temperature'].' °C, '.$releve['texte'].'. S’il fait chaud, écoulez d’abord les stocks déjà marqués urgents.'
                : 'Je n’ai pas pu lire la météo. Vos stocks urgents restent le bon point de départ pour écouler.';
        }

        if ($this->contient($t, ['confiance', 'score', 'badge', 'identite'])) {
            return "Le badge confirme votre pièce d’agriculteur. Il ne bloque pas la publication. Pour renforcer la confiance des acheteurs : une photo récente sur le produit, un prix dans la fourchette, et des retraits confirmés à l’heure.";
        }

        if ($this->contient($t, ['acheteur', 'proche', 'pres de'])) {
            $origine = Lieux::coordonnees((string) $producteur->quartier);
            $proches = User::query()->where('role', 'acheteur')->get()->filter(function (User $acheteur) use ($origine) {
                if ($acheteur->latitude === null || $origine === null) {
                    return false;
                }

                $distance = Lieux::distanceKm((float) $origine['latitude'], (float) $origine['longitude'], (float) $acheteur->latitude, (float) $acheteur->longitude);

                return $distance <= (float) ($acheteur->rayon_km ?: 25);
            })->count();

            return $proches.' acheteur(s) ont un rayon qui couvre '.$producteur->quartier.'. Publier ici les prévient tous, même plus loin. L’accueil acheteur montre d’abord les offres proches.';
        }

        if ($this->contient($t, ['ameliore', 'ameliorer', 'vendre plus', 'ecoule', 'ecouler', 'conseil', 'comment vendre'])) {
            return $this->conseil($producteur, $stocks);
        }

        if ($this->contient($t, ['rapport', 'vente', 'ventes', 'chiffre', 'revenu', 'gagne', 'encaisse', 'statistique'])) {
            return $this->rapport($commandes);
        }

        if ($this->contient($t, ['commande', 'reservation', 'reserve', 'retrait', 'livraison', 'retirer'])) {
            return $this->commandes($commandes);
        }

        if ($this->contient($t, ['prix', 'cher', 'baisser', 'tarif'])) {
            return $this->parlerPrix($producteur, $brut, $stocks);
        }

        if ($this->contient($t, ['ajouter un', 'nouveau produit', 'comment ajouter'])) {
            return "Pour publier : choisissez Ajouter un produit, décrivez l’offre (par exemple 80 kg de tomates à 700 FCFA), relisez la fiche, puis dites « oui, c’est bon ». Rien n’est enregistré avant cette confirmation.";
        }

        if ($this->contient($t, ['stock', 'produit', 'urgent', 'perime', 'perimer', 'frais'])) {
            return $this->parlerStocks($stocks);
        }

        if ($this->contient($t, ['badge', 'identite', 'photo', 'analyse', 'modifier'])) {
            return "La photo et l’analyse du prix se font en modifiant un stock déjà publié. À l’ajout, vous fixez le prix : l’assistant peut le proposer, vous confirmez, puis les acheteurs sont prévenus. Le badge d’identité n’empêche pas de publier.";
        }

        if ($this->contient($t, ['guide', 'marche', 'fonctionne', 'utiliser', 'aide', 'comment'])) {
            return "Je peux vous guider sur trois points.\n1. Ajouter un produit ici, en le décrivant, puis confirmer par « oui, c’est bon ».\n2. Suivre les commandes jusqu’au retrait, et enregistrer une vente sur place dans Ventes.\n3. Baisser un prix trop haut pour prévenir tous les acheteurs.\nPosez une question sur vos chiffres, un stock, ou une étape.";
        }

        if ($this->contient($t, ['bonjour', 'salut', 'bonsoir'])) {
            return 'Bonjour. Demandez vos ventes, le stock à écouler en premier, un prix, vos commandes, ou le mode d’emploi.';
        }

        return 'Je n’ai pas compris. Vous pouvez parler de vos ventes, de vos stocks, de vos commandes, d’un prix, de la météo, ou me demander comment faire sur Agriconnect. Reformulez en une phrase.';
    }

    private function conseil(User $producteur, $stocks): string
    {
        $enLigne = $stocks->filter(fn (Stock $stock) => in_array($stock->statut, [Stock::PUBLIE, 'partiellement_reserve'], true) && $stock->availableQuantity() > 0);
        $urgent = $enLigne->sortBy(fn (Stock $stock) => $stock->joursRestants())->first();

        if (! $urgent) {
            return 'Aucun stock n’est en ligne. Décrivez un produit à ajouter, puis confirmez la fiche : les acheteurs seront prévenus aussitôt.';
        }

        $lecture = $this->estimation($urgent->product, $urgent->joursRestants(), (int) ($urgent->freshness ?: 5), (float) $urgent->availableQuantity());
        $phrase = 'Écoulez d’abord '.$urgent->product->nom.' à '.$urgent->quartier.' : '.$urgent->joursRestants().' jour(s) avant la limite, '.fcfa($urgent->seller_price).' / '.$urgent->unite.'.';

        if ((float) $urgent->seller_price > (float) $lecture['max']) {
            $phrase .= ' Ce prix est au-dessus de la fourchette ('.fcfa($lecture['recommended']).'). Si vous le baissez, les acheteurs reçoivent une alerte de prix.';
        } else {
            $phrase .= ' Le prix est dans la fourchette actuelle. Les acheteurs du quartier '.$producteur->quartier.' voient déjà l’offre, et une nouvelle publication les prévient tous.';
        }

        return $phrase;
    }

    private function rapport($commandes): string
    {
        $debut = now()->subDays(29)->startOfDay();
        $ventes = $commandes->filter(fn (Order $order) => $order->statut === Order::TERMINEE && $order->created_at && $order->created_at->greaterThanOrEqualTo($debut));
        $total = $ventes->sum(fn (Order $order) => $order->quantite * $order->prix_unitaire);
        $meilleur = $ventes->groupBy(fn (Order $order) => $order->stock->product->nom ?? 'Produit')->map(fn ($groupe) => $groupe->sum('quantite'))->sortDesc();
        $nom = $meilleur->keys()->first();

        $phrase = 'Sur 30 jours : '.fcfa($total).' encaissés, '.$ventes->count().' vente(s) terminée(s).';
        if ($nom) {
            $phrase .= ' Le plus vendu : '.$nom.' ('.number_format((float) $meilleur->first(), 0, ',', ' ').' '.($ventes->first()->stock->unite ?? 'kg').').';
        }
        $phrase .= ' Le détail imprimable est dans Rapport.';

        return $phrase;
    }

    private function commandes($commandes): string
    {
        $ouvertes = $commandes->whereNotIn('statut', [Order::TERMINEE, Order::ANNULEE]);
        if ($ouvertes->isEmpty()) {
            return 'Aucune commande n’est en cours. Les ventes déjà terminées sont dans Ventes. Une nouvelle offre, une fois confirmée ici, prévient les acheteurs.';
        }

        $lignes = $ouvertes->groupBy('statut')->map(function ($groupe, $statut) {
            return Libelles::commande($statut).' : '.$groupe->count();
        })->implode('. ');

        return 'Commandes en cours : '.$lignes.'. Avancez l’étape depuis la fiche de la commande. « À retirer » se suit dans Livraisons.';
    }

    private function parlerPrix(User $producteur, string $brut, $stocks): string
    {
        $produit = $this->lire($brut)['product'] ?? $stocks->first()?->product;
        if (! $produit) {
            return 'De quel produit parlez-vous ? '.$this->nomsProduits().'.';
        }

        $stock = $stocks->first(fn (Stock $s) => $s->product_id === $produit->id && $s->availableQuantity() > 0);
        $jours = $stock ? $stock->joursRestants() : max(1, (int) round($produit->conservation_heures_reference / 24));
        $fraicheur = $stock ? (int) ($stock->freshness ?: 5) : 5;
        $quantite = $stock ? (float) $stock->availableQuantity() : 50;
        $lecture = $this->estimation($produit, $jours, $fraicheur, $quantite);
        $phrase = 'Pour '.$produit->nom.', la régression propose '.fcfa($lecture['recommended']).' / '.$produit->unite.' (fourchette '.fcfa($lecture['min']).' – '.fcfa($lecture['max']).').';

        if ($stock) {
            $phrase .= ' Votre prix actuel à '.$stock->quartier.' est '.fcfa($stock->seller_price).'.';
            if ((float) $stock->seller_price > (float) $lecture['max']) {
                $phrase .= ' Il est haut par rapport à cette fourchette : le baisser depuis la fiche prévient les acheteurs.';
            }
        } else {
            $phrase .= ' Vous n’avez pas ce produit en ligne. Pour le publier, passez en ajout et décrivez la quantité.';
        }

        return $phrase;
    }

    private function parlerStocks($stocks): string
    {
        $enLigne = $stocks->filter(fn (Stock $stock) => in_array($stock->statut, [Stock::PUBLIE, 'partiellement_reserve'], true) && $stock->availableQuantity() > 0);

        if ($enLigne->isEmpty()) {
            return 'Aucun stock n’est en vente. Décrivez-en un ici, puis dites oui pour le publier.';
        }

        $lignes = $enLigne->sortBy(fn (Stock $stock) => $stock->joursRestants())->take(4)->map(function (Stock $stock) {
            return $stock->product->nom.' à '.$stock->quartier.' : '.number_format($stock->availableQuantity(), 0, ',', ' ').' '.$stock->unite.', '.fcfa($stock->seller_price).', '.Libelles::urgence($stock->urgency).', '.$stock->joursRestants().' j restants';
        })->implode("\n");

        return "Stocks en ligne, les plus courts d’abord :\n".$lignes;
    }

    private function lire(string $brut): array
    {
        $t = $this->texte($brut);
        $lu = [
            'product' => null,
            'quantity' => null,
            'price' => null,
            'min_price' => null,
            'quartier' => null,
            'harvest' => null,
            'pickup' => null,
            'inconnu' => null,
            'quartier_inconnu' => null,
            'ambigu' => false,
            'date_refusee' => false,
            'min_invalide' => false,
        ];

        $produits = Product::query()->get()->sortByDesc(fn (Product $produit) => mb_strlen($produit->nom));
        foreach ($produits as $produit) {
            $nom = $this->texte($produit->nom);
            if ($nom !== '' && preg_match('/\b'.preg_quote($nom, '/').'s?\b/u', $t)) {
                $lu['product'] = $produit;
                break;
            }
        }

        if (preg_match('/(\d+(?:[.,]\d+)?)\s*k(?:g|ilos?)\b/u', $t, $nombre)) {
            $lu['quantity'] = (float) str_replace(',', '.', $nombre[1]);
        } elseif (preg_match('/\bquantite\s+(\d+(?:[.,]\d+)?)/u', $t, $nombre)) {
            $lu['quantity'] = (float) str_replace(',', '.', $nombre[1]);
        }

        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(?:fcfa|cfa|francs)\b/u', $t, $nombre)) {
            $lu['price'] = (float) str_replace(',', '.', $nombre[1]);
        } elseif (preg_match('/\bprix\s+(\d+(?:[.,]\d+)?)/u', $t, $nombre)) {
            $lu['price'] = (float) str_replace(',', '.', $nombre[1]);
        } elseif ($lu['quantity'] !== null && preg_match('/\ba\s+(\d+(?:[.,]\d+)?)\b/u', $t, $nombre)) {
            $lu['price'] = (float) str_replace(',', '.', $nombre[1]);
        }

        if (preg_match('/\b(?:plancher|minimum)\s+(\d+(?:[.,]\d+)?)/u', $t, $nombre)) {
            $lu['min_price'] = (float) str_replace(',', '.', $nombre[1]);
        }

        foreach (Lieux::noms() as $nom) {
            $clair = $this->texte($nom);
            if ($clair !== '' && preg_match('/\b'.preg_quote($clair, '/').'\b/u', $t)) {
                $lu['quartier'] = $nom;
                break;
            }
        }

        if (preg_match('/\bdemain\b/u', $t)) {
            $lu['date_refusee'] = true;
        } elseif (preg_match('/\b(aujourd|ce jour|ce matin)\b/u', $t)) {
            $lu['harvest'] = now()->toDateString();
        } elseif (preg_match('/\bhier\b/u', $t)) {
            $lu['harvest'] = now()->subDay()->toDateString();
        } elseif (preg_match('/\b(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2,4})\b/u', $t, $date)) {
            $annee = strlen($date[3]) === 2 ? (2000 + (int) $date[3]) : (int) $date[3];
            $candidat = Carbon::createFromDate($annee, (int) $date[2], (int) $date[1]);
            $lu['harvest'] = $candidat->toDateString();
            $lu['date_refusee'] = $candidat->startOfDay()->greaterThan(now()->startOfDay());
        }

        if (preg_match('/\b(rendez vous|rdv|point de rendez vous)\b/u', $t)) {
            $lu['pickup'] = 'point_rendez_vous';
        } elseif (preg_match('/\b(sur place|a la ferme|ferme)\b/u', $t)) {
            $lu['pickup'] = 'sur_place';
        }

        $vus = 0;
        preg_match_all('/\d+(?:[.,]\d+)?/u', $t, $tous);
        foreach ($tous[0] as $token) {
            $valeur = (float) str_replace(',', '.', $token);
            if ($valeur === (float) $lu['quantity'] || $valeur === (float) $lu['price'] || $valeur === (float) $lu['min_price']) {
                continue;
            }
            $vus++;
        }
        if ($lu['quantity'] === null && $lu['price'] === null && $vus >= 2) {
            $lu['ambigu'] = true;
        }

        if ($lu['quartier'] === null && preg_match('/\b(?:quartier|a|dans)\s+([a-z]{4,})\b/u', $t, $lieu)) {
            $mot = $lieu[1];
            $ignores = ['aujourd', 'tomates', 'bananes', 'ananas', 'piments', 'mangues', 'oignons', 'kilos', 'francs', 'place', 'ferme'];
            if (! in_array($mot, $ignores, true) && ($lu['product'] === null || ! str_contains($this->texte($lu['product']->nom), $mot))) {
                $lu['quartier_inconnu'] = $mot;
            }
        }

        if ($lu['product'] === null) {
            $copie = $t;
            foreach (array_merge(Lieux::noms(), $this->motsUtiles()) as $mot) {
                $copie = str_replace($this->texte($mot), ' ', $copie);
            }
            $copie = preg_replace('/\d+(?:[.,]\d+)?/u', ' ', (string) $copie) ?? '';
            foreach (preg_split('/\s+/u', trim($copie)) ?: [] as $mot) {
                if (mb_strlen($mot) >= 5) {
                    $lu['inconnu'] = $mot;
                    break;
                }
            }
        }

        if ($lu['min_price'] !== null && $lu['price'] !== null && $lu['min_price'] > $lu['price']) {
            $lu['min_invalide'] = true;
        }

        return $lu;
    }

    private function appliquer(array &$brouillon, array $lu, User $producteur): void
    {
        if ($lu['product'] instanceof Product) {
            $brouillon['product_id'] = $lu['product']->id;
        }
        if ($lu['quantity'] !== null && $lu['quantity'] >= 1) {
            $brouillon['quantity'] = $lu['quantity'];
        }
        if ($lu['price'] !== null && $lu['price'] >= 10) {
            $brouillon['seller_price'] = $lu['price'];
            $brouillon['prix_source'] = 'vous';
        }
        if ($lu['min_price'] !== null) {
            $brouillon['min_price'] = $lu['min_price'];
            $brouillon['plancher_source'] = 'vous';
        }
        if ($lu['quartier'] !== null) {
            $brouillon['quartier'] = $lu['quartier'];
            $brouillon['quartier_source'] = 'vous';
        }
        if ($lu['harvest'] !== null && ! $lu['date_refusee']) {
            $brouillon['harvested_on'] = $lu['harvest'];
            $brouillon['date_source'] = 'vous';
        }
        if ($lu['pickup'] !== null) {
            $brouillon['pickup_mode'] = $lu['pickup'];
            $brouillon['retrait_source'] = 'vous';
        }

        if (($brouillon['product_id'] ?? null) === null || ($brouillon['quantity'] ?? null) === null) {
            return;
        }

        $produit = Product::find($brouillon['product_id']);
        if (! $produit) {
            return;
        }

        if (($brouillon['quartier'] ?? null) === null) {
            $brouillon['quartier'] = $producteur->quartier ?: Lieux::noms()[0];
            $brouillon['quartier_source'] = 'votre quartier';
        }
        if (($brouillon['harvested_on'] ?? null) === null) {
            $brouillon['harvested_on'] = now()->toDateString();
            $brouillon['date_source'] = 'aujourd’hui, proposé';
        }
        if (($brouillon['pickup_mode'] ?? null) === null) {
            $brouillon['pickup_mode'] = 'sur_place';
            $brouillon['retrait_source'] = 'proposé';
        }

        $limite = Carbon::parse($brouillon['harvested_on'])->addHours((int) $produit->conservation_heures_reference);
        $jours = max(0, (int) floor(($limite->timestamp - now()->startOfDay()->timestamp) / 86400));
        $lecture = $this->estimation($produit, $jours, 5, (float) $brouillon['quantity']);

        if (($brouillon['seller_price'] ?? null) === null) {
            $brouillon['seller_price'] = $lecture['recommended'];
            $brouillon['prix_source'] = 'proposé selon l’historique';
        }
        if (($brouillon['min_price'] ?? null) === null || (float) $brouillon['min_price'] > (float) $brouillon['seller_price']) {
            $plancher = min((float) $lecture['min'], (float) $brouillon['seller_price']);
            $brouillon['min_price'] = $plancher;
            $brouillon['plancher_source'] = 'proposé';
        }
    }

    private function estimation(Product $produit, int $jours, int $fraicheur, float $quantite): array
    {
        return $this->prix->estimate($produit, [
            'days_left' => $jours,
            'freshness' => $fraicheur,
            'quantity' => max(0.1, $quantite),
        ]);
    }

    private function complet(array $brouillon): bool
    {
        return ($brouillon['product_id'] ?? null) !== null
            && ($brouillon['quantity'] ?? null) !== null
            && ($brouillon['seller_price'] ?? null) !== null
            && ($brouillon['min_price'] ?? null) !== null
            && ($brouillon['quartier'] ?? null) !== null
            && ($brouillon['harvested_on'] ?? null) !== null
            && ($brouillon['pickup_mode'] ?? null) !== null
            && (float) $brouillon['min_price'] <= (float) $brouillon['seller_price'];
    }

    private function prevenir(Stock $stock, string $message): void
    {
        $lignes = User::query()->where('role', 'acheteur')->pluck('id')->map(fn ($id) => [
            'user_id' => $id,
            'stock_id' => $stock->id,
            'type' => 'nouveau_stock',
            'message' => $message,
            'lu' => false,
            'created_at' => now(),
        ])->all();

        if ($lignes !== []) {
            MarketNotification::insert($lignes);
        }
    }

    private function modeDemande(string $brut): ?string
    {
        $t = $this->texte($brut);
        if (preg_match('/^(ajouter|ajouter un produit|nouveau produit|publier un produit|aider a ajouter)$/u', $t)) {
            return 'ajouter';
        }
        if (preg_match('/^(discuter|discussion|une question|j ai une question|guide)$/u', $t)) {
            return 'discuter';
        }
        if (preg_match('/^(conversation en ewe|ewe|ewegbe|evegbe|langue locale)$/u', $t)) {
            return 'ewe';
        }

        return null;
    }

    private function devinerMode(string $brut): ?string
    {
        $t = $this->texte($brut);
        if ($this->contient($t, ['comment', 'pourquoi', 'combien', 'vente', 'commande', 'rapport', 'conseil', 'meteo', 'guide', 'ameliore'])) {
            return 'discuter';
        }
        $lu = $this->lire($brut);
        if ($lu['product'] || $lu['quantity'] || $lu['inconnu']) {
            return 'ajouter';
        }

        return null;
    }

    private function estConfirmation(string $brut): bool
    {
        $t = $this->texte($brut);
        if (! preg_match('/^(oui|ouais|ok|okay|d accord|dac|c est bon|parfait|valide|confirme|enregistre|vas y|yes)\b/u', $t)) {
            return false;
        }

        return ! preg_match('/\d/u', $t) && ! preg_match('/\b(prix|quantite|quartier|date|kg|fcfa|recolte)\b/u', $t);
    }

    private function estRefus(string $brut): bool
    {
        return (bool) preg_match('/^(non|annule|pas bon|ce n est pas bon)\b/u', $this->texte($brut));
    }

    private function origine(?string $source): string
    {
        return match ($source) {
            'vous' => 'donnée par vous',
            'votre quartier' => 'votre quartier',
            default => $source ?: 'proposé',
        };
    }

    private function nomsProduits(): string
    {
        return Product::query()->orderBy('nom')->pluck('nom')->implode(', ');
    }

    private function contient(string $texte, array $mots): bool
    {
        foreach ($mots as $mot) {
            if (str_contains($texte, $this->texte($mot))) {
                return true;
            }
        }

        return false;
    }

    private function texte(string $valeur): string
    {
        $valeur = mb_strtolower(trim($valeur));
        $valeur = strtr($valeur, [
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'à' => 'a', 'â' => 'a', 'ä' => 'a',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ô' => 'o', 'ö' => 'o', 'î' => 'i', 'ï' => 'i',
            'ç' => 'c', '’' => ' ', "'" => ' ', '-' => ' ',
        ]);

        return trim((string) preg_replace('/\s+/u', ' ', $valeur));
    }

    private function motsUtiles(): array
    {
        return ['je', 'veux', 'vendre', 'ajouter', 'produit', 'kg', 'kilo', 'kilos', 'de', 'des', 'du', 'la', 'le', 'les', 'un', 'une', 'a', 'au', 'aux', 'fcfa', 'francs', 'aujourd', 'hui', 'recolte', 'recoltes', 'recoltee', 'prix', 'quantite', 'pour', 'dans', 'sur', 'place', 'bonjour', 'frais', 'fraiche'];
    }

    private function brouillonVide(): array
    {
        return [
            'product_id' => null,
            'quantity' => null,
            'seller_price' => null,
            'min_price' => null,
            'quartier' => null,
            'harvested_on' => null,
            'pickup_mode' => null,
            'prix_source' => null,
            'plancher_source' => null,
            'quartier_source' => null,
            'date_source' => null,
            'retrait_source' => null,
        ];
    }

    private function couper(array $etat): array
    {
        $etat['messages'] = array_slice($etat['messages'], -30);

        return $etat;
    }
}
