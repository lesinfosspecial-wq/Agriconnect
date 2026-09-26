<?php

use App\Http\Controllers\Acheteur\CompteController;
use App\Http\Controllers\Acheteur\DashboardController as AcheteurDashboardController;
use App\Http\Controllers\Acheteur\OffreController;
use App\Http\Controllers\Acheteur\ReservationController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ValidationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Acheteur\FavoriController;
use App\Http\Controllers\Vendeur\AssistantController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocalisationController;
use App\Http\Controllers\Vendeur\CommandeController;
use App\Http\Controllers\Vendeur\DashboardController as VendeurDashboardController;
use App\Http\Controllers\Vendeur\ProfilController;
use App\Http\Controllers\Vendeur\StockController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/connexion', [LoginController::class, 'create'])->name('login');
    Route::post('/connexion', [LoginController::class, 'store']);
    Route::get('/inscription', [RegisterController::class, 'create'])->name('register');
    Route::post('/inscription/profil', [RegisterController::class, 'choose'])->name('register.choose');
    Route::get('/inscription/profil', [RegisterController::class, 'form'])->name('register.form');
    Route::post('/inscription', [RegisterController::class, 'store']);
});

Route::post('/localisation', LocalisationController::class)
    ->middleware('auth')
    ->name('localisation');

Route::post('/deconnexion', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'role:vendeur'])->prefix('vendeur')->name('vendeur.')->group(function () {
    Route::get('/', [VendeurDashboardController::class, 'index'])->name('dashboard');
    Route::get('/produits', [VendeurDashboardController::class, 'produits'])->name('produits');
    Route::get('/commandes', [VendeurDashboardController::class, 'commandes'])->name('commandes');
    Route::get('/commandes/{order}', [CommandeController::class, 'show'])->name('commandes.show');
    Route::get('/ventes', [CommandeController::class, 'ventes'])->name('ventes');
    Route::post('/ventes', [CommandeController::class, 'enregistrer'])->name('ventes.store');
    Route::get('/ventes/{order}', [CommandeController::class, 'vente'])->name('ventes.show');
    Route::get('/livraisons', [CommandeController::class, 'livraisons'])->name('livraisons');
    Route::get('/statistiques', [VendeurDashboardController::class, 'statistiques'])->name('statistiques');
    Route::get('/profil', [ProfilController::class, 'edit'])->name('profil');
    Route::put('/profil', [ProfilController::class, 'update'])->name('profil.update');
    Route::get('/assistant', [AssistantController::class, 'index'])->name('assistant');
    Route::post('/assistant', [AssistantController::class, 'message'])->name('assistant.message');
    Route::get('/assistant/voix-etat', [AssistantController::class, 'voixEtat'])->name('assistant.voix');
    Route::post('/assistant/vocal', [AssistantController::class, 'vocal'])->name('assistant.vocal');
    Route::post('/assistant/vider', [AssistantController::class, 'vider'])->name('assistant.vider');
    Route::get('/parametres', [ProfilController::class, 'parametres'])->name('parametres');
    Route::put('/parametres/mot-de-passe', [ProfilController::class, 'motDePasse'])->name('parametres.mot-de-passe');
    Route::get('/stocks/nouveau', [StockController::class, 'create'])->name('stocks.create');
    Route::post('/stocks', [StockController::class, 'store'])->name('stocks.store');
    Route::get('/stocks/{stock}/modifier', [StockController::class, 'edit'])->name('stocks.edit');
    Route::put('/stocks/{stock}', [StockController::class, 'update'])->name('stocks.update');
    Route::get('/stocks/{stock}', [StockController::class, 'show'])->name('stocks.show');
    Route::post('/stocks/{stock}/ajuster', [StockController::class, 'ajuster'])->name('stocks.ajuster');
    Route::post('/stocks/{stock}/recalculer', [StockController::class, 'recalculer'])->name('stocks.recalculer');
    Route::post('/stocks/{stock}/retirer', [StockController::class, 'retirer'])->name('stocks.retirer');
    Route::post('/verification', [StockController::class, 'demanderVerification'])->name('verification');
    Route::post('/commandes/{order}/avancer', [CommandeController::class, 'avancer'])->name('commandes.avancer');
});

Route::middleware(['auth', 'role:acheteur'])->prefix('acheteur')->name('acheteur.')->group(function () {
    Route::get('/', [AcheteurDashboardController::class, 'index'])->name('dashboard');
    Route::get('/explorer', [AcheteurDashboardController::class, 'explorer'])->name('explorer');
    Route::get('/notifications', [AcheteurDashboardController::class, 'notifications'])->name('notifications');
    Route::get('/favoris', [FavoriController::class, 'index'])->name('favoris');
    Route::post('/favoris/{stock}', [FavoriController::class, 'toggle'])->name('favoris.toggle');
    Route::view('/messages', 'acheteur.simple', ['titre' => 'Messages', 'texte' => 'Aucun message pour le moment.'])->name('messages');
    Route::view('/portefeuille', 'acheteur.simple', ['titre' => 'Mon portefeuille', 'texte' => 'Les paiements ne passent pas encore par Agriconnect. Le règlement se fait au retrait.'])->name('portefeuille');
    Route::get('/offres/{stock}', [OffreController::class, 'show'])->name('offres.show');
    Route::post('/offres/{stock}/reserver', [ReservationController::class, 'store'])->name('reservations.store');
    Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::get('/reservations/{order}', [ReservationController::class, 'show'])->name('reservations.show');
    Route::post('/reservations/{order}/annuler', [ReservationController::class, 'annuler'])->name('reservations.annuler');
    Route::post('/reservations/{order}/noter', [ReservationController::class, 'noter'])->name('reservations.noter');
    Route::post('/verification', [ReservationController::class, 'demanderVerification'])->name('verification');
    Route::get('/profil', [CompteController::class, 'edit'])->name('profil');
    Route::put('/profil', [CompteController::class, 'update'])->name('profil.update');
    Route::get('/parametres', [CompteController::class, 'parametres'])->name('parametres');
    Route::put('/parametres/mot-de-passe', [CompteController::class, 'motDePasse'])->name('parametres.mot-de-passe');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/identites', [AdminDashboardController::class, 'identites'])->name('identites');
    Route::get('/identites/{user}', [AdminDashboardController::class, 'identite'])->name('identites.show');
    Route::get('/annonces', [AdminDashboardController::class, 'annonces'])->name('annonces.index');
    Route::get('/annonces/{stock}', [ValidationController::class, 'show'])->name('annonces.show');
    Route::post('/annonces/{stock}/suspension', [ValidationController::class, 'suspendre'])->name('annonces.suspendre');
    Route::get('/transactions', [AdminDashboardController::class, 'transactions'])->name('transactions');
    Route::get('/transactions/{order}', [AdminDashboardController::class, 'transaction'])->name('transactions.show');
    Route::post('/utilisateurs/{user}/verification', [ValidationController::class, 'verifier'])->name('utilisateurs.verifier');
});
