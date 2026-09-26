# Agriconnect

Plateforme de vente d’urgence des produits agricoles périssables, pour le Défi 1 de l’ESIG Tech Arena 2026 (Lomé, prix en FCFA).

Un producteur publie un stock dès la récolte. Un acheteur proche le réserve et le retire sur place. L’administrateur suit les comptes, les stocks et les ventes. Le règlement se fait au retrait : Agriconnect ne gère pas de paiement en ligne.

## Parcours

**Producteur**

- Accueil avec indicateurs, météo du quartier (Open-Meteo) et stocks en cours.
- Déclaration d’un stock : produit, quantité, prix, quartier et date de récolte. La date limite est calculée à partir de la durée de conservation du produit (tomate 6 jours, piment 8 jours, oignon 20 jours, etc.). Aucun prix n’est proposé à ce moment-là.
- Modification complète du stock, photo actuelle obligatoire. C’est là que l’analyse de la photo et l’estimation de prix se lancent.
- Commandes suivies jusqu’au retrait, ventes de l’application et ventes saisies sur place, produits prêts à retirer, rapport d’activité imprimable.
- Photo de profil et pièce d’agriculteur à l’inscription. Le badge d’identité est décoratif : il n’empêche pas de publier un stock.

**Acheteur**

- Offres dans son rayon (quartier ou position du navigateur, si l’autorisation est accordée).
- Explorer affiche toutes les offres publiées, y compris celles qui sont loin.
- Réservation, favoris, notation après le retrait, notifications.

**Administrateur**

- Tableau de bord, fiches d’identité (photo et pièce), suspension d’un stock, détail des ventes (application et sur place).

## Estimation

La photo et le prix n’interviennent qu’à la modification d’un stock déjà publié. L’éwé, lui, est dans l’assistant.

1. **Photo — MobileNetV2 (prévu).** C’est un réseau léger, adapté à un ordinateur portable et à des photos de marché, que l’on réentraîne par transfert sur des images classées par stade de pourriture. Les poids ne sont pas encore branchés : il manque un jeu de photos étiquetées. En attendant, `App\Services\FreshnessVision` produit la même sortie (putréfaction de 0 à 100, fraîcheur de 1 à 5). Avec l’extension GD, le score mélange l’âge depuis la récolte (65 %) et les couleurs de la photo (35 %). Sans GD, le score reste déterministe à partir du fichier et de l’âge. Ce n’est pas encore le réseau de neurones.

2. **Prix — régression linéaire ridge**, dans `App\Services\PricePredictor`. Elle s’appuie sur l’historique de prix du produit, la fraîcheur lue sur la photo, les jours restants, la quantité et le rapport demande / offre des réservations récentes. Elle compare ce prix au prix saisi par le producteur.

3. **Éwé — classifieur à centroïdes**, dans `App\Services\EweIntentModel`. Il est entraîné sur des phrases éwé du marché (publier, confirmer, prix, ventes, stocks, commandes, météo). Une phrase nouvelle est rapprochée de l’intention la plus proche. Les autres langues du Togo ne sont pas encore dans le jeu d’exemples. Les noms de cultures viennent du vocabulaire éwé courant : timáti, akɔɖu, atɔtɔ, atádí, sabala, et mango pour la mangue. Une note vocale passe par MMS de Meta : `facebook/mms-1b-all` (adaptateur `ewe`) transcrit sans afficher le texte, et `facebook/mms-tts-ewe` lit la réponse. Ce modèle de lecture est entraîné pour l’éwé ; les tons, « FCFA » et les noms de quartiers restent la limite. Le service se lance avec `python/ewe_voix/serveur.py` après `pip install -r python/ewe_voix/requirements.txt`.

Les stocks déjà chargés par le jeu de données (tomates de Kossi, etc.) ont encore une estimation issue de l’ancien flux. Un stock créé maintenant n’en a une qu’après une modification avec photo.

## Lancer le projet

PHP 8.2, Composer, MySQL (la démo locale utilise la base `agriconnect`).

```bash
composer install
copy .env.example .env
php artisan key:generate
```

Dans `.env`, pointer vers MySQL :

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=agriconnect
DB_USERNAME=root
DB_PASSWORD=
```

Puis :

```bash
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

L’application est sur [http://127.0.0.1:8000](http://127.0.0.1:8000). Connexion par numéro de téléphone.

## Comptes de démonstration

Mot de passe de tous les comptes : `password`.

| Rôle | Nom | Téléphone |
| --- | --- | --- |
| Admin | Awa MENSAH | 90000001 |
| Producteur | Kossi AGBEKO | 90011223 |
| Producteur | Afi DOSSOU | 90022334 |
| Producteur | Kodjo AMEGAN | 90033440 |
| Acheteur | Ama LAWSON | 90033445 |
| Acheteur | Cantine ESIG | 90055667 |

## Tests

```bash
php artisan test
```

```bash
php artisan optimize:clear
php artisan serve
.\python\ewe_voix\.venv\Scripts\python.exe python\ewe_voix\serveur.py
```

Les tests utilisent SQLite en mémoire. L’extension GD n’est pas requise.
