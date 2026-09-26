@extends('layouts.producteur')

@section('title', 'Assistant IA — Agriconnect')

@section('main')
    @if ($etat['mode'])
        <section class="assistant">
            <header class="wa-bar">
                <a href="{{ route('vendeur.assistant', ['ecran' => 'accueil']) }}" aria-label="Retour"><i class="bi bi-arrow-left"></i></a>
                <span class="wa-avatar"><i class="bi bi-stars"></i></span>
                <div>
                    <strong>Assistant</strong>
                    <small>{{ match ($etat['mode']) {
                        'ajouter' => 'Ajout d’un produit',
                        'ewe' => 'Eʋegbe',
                        default => 'En ligne',
                    } }}</small>
                </div>
                <form class="wa-clear" method="POST" action="{{ route('vendeur.assistant.vider') }}" onsubmit="return confirm('Effacer cette conversation ?')">
                    @csrf
                    <button type="submit" aria-label="Vider la conversation"><i class="bi bi-trash3"></i></button>
                </form>
            </header>
            <div class="chat-log" id="chat-log" @if (! empty($enCours)) data-voix-etat="{{ route('vendeur.assistant.voix') }}" @endif>
                @foreach ($etat['messages'] as $message)
                    <article @class(['bulle', ! empty($message['vocal']) ? 'voix-bulle' : null, $message['de'] === 'producteur' ? 'sortant' : 'entrant'])>
                        @if (! empty($message['vocal']) && ! empty($message['audio']))
                            <button type="button" class="voix" data-src="{{ asset('storage/'.$message['audio']) }}">
                                <i class="bi bi-play-fill"></i>
                                <span class="ondes" aria-hidden="true">
                                    @for ($i = 0; $i < 26; $i++)
                                        <span style="height: {{ 6 + (($i * 17) % 16) }}px"></span>
                                    @endfor
                                </span>
                                <em>0:00</em>
                            </button>
                        @else
                            <p>{!! nl2br(e($message['texte'])) !!}</p>
                        @endif
                        @if (! empty($message['heure']))<time>{{ $message['heure'] }}</time>@endif
                    </article>
                @endforeach
                @if (! empty($enCours))
                    <article class="bulle entrant en-ecriture">
                        <p>En train d’écrire<span></span><span></span><span></span></p>
                    </article>
                @endif
            </div>
            @if ($etat['pret'])
                <form class="chat-oui" method="POST" action="{{ route('vendeur.assistant.message') }}">
                    @csrf
                    <button type="submit" name="message" value="oui, c'est bon">Oui, c’est bon</button>
                </form>
            @endif
            <form class="chat-form" id="chat-form" method="POST" action="{{ route('vendeur.assistant.message') }}" novalidate>
                @csrf
                <label class="sr-only" for="message">Votre message</label>
                <textarea id="message" name="message" rows="1" maxlength="500" placeholder="{{ $etat['mode'] === 'ewe' ? 'Message en éwé' : 'Message' }}"></textarea>
                <div class="rec-bar" id="rec-bar" hidden>
                    <button type="button" id="annule-rec" aria-label="Annuler la note"><i class="bi bi-trash"></i></button>
                    <i class="rec-point"></i>
                    <span id="rec-temps">0:00</span>
                </div>
                @if ($etat['mode'] === 'ewe')
                    <button type="button" id="micro" aria-label="Note vocale"><i class="bi bi-mic-fill"></i></button>
                @endif
                <button type="submit" id="envoyer" aria-label="Envoyer"><i class="bi bi-send-fill"></i></button>
            </form>
            @error('message')<small class="chat-error">{{ $message }}</small>@enderror
        </section>
        <script>
            const journal = document.querySelector('#chat-log');
            if (journal) journal.scrollTop = journal.scrollHeight;
            if (journal?.dataset.voixEtat) {
                const suivi = setInterval(async () => {
                    try {
                        const reponse = await fetch(journal.dataset.voixEtat, { headers: { Accept: 'application/json' } });
                        const donnees = await reponse.json();
                        if (donnees.statut !== 'en_cours') {
                            clearInterval(suivi);
                            window.location.reload();
                        }
                    } catch (e) {}
                }, 3000);
            }

            const form = document.querySelector('#chat-form');
            const champ = document.querySelector('#message');
            const micro = document.querySelector('#micro');
            const barre = document.querySelector('#rec-bar');
            const temps = document.querySelector('#rec-temps');
            const annule = document.querySelector('#annule-rec');
            let enregistreur = null;
            let flux = null;
            let morceaux = [];
            let minuteur = null;
            let debut = 0;
            let jeter = false;

            let ecoule = 0;

            const iconeMicro = (classe, legende) => {
                const icone = micro?.querySelector('i');
                if (!icone) return;
                icone.className = classe;
                micro.setAttribute('aria-label', legende);
            };

            const afficherTemps = () => {
                const cours = enregistreur?.state === 'recording' ? Date.now() - debut : 0;
                const secondes = Math.floor((ecoule + cours) / 1000);
                if (temps) temps.textContent = Math.floor(secondes / 60) + ':' + String(secondes % 60).padStart(2, '0');
            };

            const ajouterNoteLocale = (url, duree) => {
                const article = document.createElement('article');
                article.className = 'bulle voix-bulle sortant';
                const ondes = Array.from({ length: 26 }, (_, i) => `<span style="height:${6 + ((i * 17) % 16)}px"></span>`).join('');
                article.innerHTML = `<button type="button" class="voix" data-src="${url}"><i class="bi bi-play-fill"></i><span class="ondes" aria-hidden="true">${ondes}</span><em>${duree}</em></button><time>à l'instant</time>`;
                journal?.appendChild(article);
                brancher(article.querySelector('.voix'));
                if (journal) journal.scrollTop = journal.scrollHeight;
            };

            const envoyerNote = (wav, duree) => {
                const blob = new Blob([wav], { type: 'audio/wav' });
                ajouterNoteLocale(URL.createObjectURL(blob), duree);
                const ecrit = document.createElement('article');
                ecrit.className = 'bulle entrant en-ecriture';
                ecrit.innerHTML = '<p>En train d’écrire<span></span><span></span><span></span></p>';
                journal?.appendChild(ecrit);
                if (journal) journal.scrollTop = journal.scrollHeight;
                if (micro) micro.disabled = false;
                const corps = new FormData();
                corps.append('audio', blob, 'note.wav');
                corps.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                fetch('{{ route('vendeur.assistant.vocal') }}', { method: 'POST', body: corps, redirect: 'follow' })
                    .then((reponse) => { window.location = reponse.url || '{{ route('vendeur.assistant') }}'; })
                    .catch(() => {
                        ecrit.remove();
                        const alerte = document.createElement('p');
                        alerte.className = 'chat-error';
                        alerte.textContent = 'La note est affichée, mais la voix n’a pas encore répondu. Réessayez dans un instant.';
                        form?.after(alerte);
                    });
            };

            const arreterMinuteur = () => {
                clearInterval(minuteur);
                minuteur = null;
                if (debut) {
                    ecoule += Date.now() - debut;
                    debut = 0;
                }
            };

            const fermerEnregistrement = () => {
                arreterMinuteur();
                ecoule = 0;
                if (temps) temps.textContent = '0:00';
                if (barre) barre.hidden = true;
                if (champ) champ.hidden = false;
                barre?.classList.remove('is-pause');
                iconeMicro('bi bi-mic-fill', 'Note vocale');
                flux?.getTracks().forEach((piste) => piste.stop());
                flux = null;
            };

            if (micro) {
                micro.addEventListener('click', async () => {
                    if (enregistreur && enregistreur.state === 'recording') {
                        enregistreur.pause();
                        arreterMinuteur();
                        afficherTemps();
                        barre?.classList.add('is-pause');
                        iconeMicro('bi bi-play-fill', 'Reprendre');
                        return;
                    }
                    if (enregistreur && enregistreur.state === 'paused') {
                        debut = Date.now();
                        enregistreur.resume();
                        minuteur = setInterval(afficherTemps, 250);
                        barre?.classList.remove('is-pause');
                        iconeMicro('bi bi-pause-fill', 'Pause');
                        return;
                    }
                    flux = await navigator.mediaDevices.getUserMedia({ audio: true });
                    morceaux = [];
                    jeter = false;
                    ecoule = 0;
                    enregistreur = new MediaRecorder(flux);
                    enregistreur.ondataavailable = (e) => { if (e.data.size) morceaux.push(e.data); };
                    enregistreur.onstop = async () => {
                        const envoyer = !jeter && morceaux.length > 0;
                        const duree = temps?.textContent || '0:00';
                        const audioBlob = new Blob(morceaux);
                        fermerEnregistrement();
                        if (!envoyer) return;
                        const audio = await new AudioContext().decodeAudioData(await audioBlob.arrayBuffer());
                        envoyerNote(echantillonner(audio, 16000), duree);
                    };
                    enregistreur.start();
                    debut = Date.now();
                    afficherTemps();
                    minuteur = setInterval(afficherTemps, 250);
                    champ.hidden = true;
                    barre.hidden = false;
                    iconeMicro('bi bi-pause-fill', 'Pause');
                });
            }

            annule?.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                jeter = true;
                morceaux = [];
                fermerEnregistrement();
                if (enregistreur && enregistreur.state !== 'inactive') enregistreur.stop();
            });

            form?.addEventListener('submit', (event) => {
                if (enregistreur && (enregistreur.state === 'recording' || enregistreur.state === 'paused')) {
                    event.preventDefault();
                    enregistreur.stop();
                    return;
                }
                if (!champ.value.trim()) event.preventDefault();
            });

            document.querySelectorAll('.voix').forEach((bouton) => brancher(bouton));

            function brancher(bouton) {
                const lecteur = new Audio(bouton.dataset.src);
                const duree = bouton.querySelector('em');
                const icone = bouton.querySelector('i');
                const rendre = () => {
                    const total = Number.isFinite(lecteur.duration) ? lecteur.duration : 0;
                    const gauche = Math.max(0, Math.floor(total - (lecteur.paused ? 0 : lecteur.currentTime)));
                    if (total) duree.textContent = Math.floor(gauche / 60) + ':' + String(gauche % 60).padStart(2, '0');
                    const progres = total ? lecteur.currentTime / total : 0;
                    bouton.querySelectorAll('.ondes span').forEach((barreOnde, index, barres) => {
                        barreOnde.classList.toggle('jouee', index / barres.length < progres);
                    });
                };
                lecteur.addEventListener('loadedmetadata', rendre);
                lecteur.addEventListener('timeupdate', rendre);
                lecteur.addEventListener('ended', () => { lecteur.currentTime = 0; icone.className = 'bi bi-play-fill'; rendre(); });
                bouton.addEventListener('click', () => {
                    if (lecteur.paused) {
                        document.querySelectorAll('.voix').forEach((autre) => { if (autre !== bouton) autre.dispatchEvent(new Event('pause-voix')); });
                        lecteur.play();
                        icone.className = 'bi bi-pause-fill';
                    } else {
                        lecteur.pause();
                        icone.className = 'bi bi-play-fill';
                    }
                });
                bouton.addEventListener('pause-voix', () => { lecteur.pause(); icone.className = 'bi bi-play-fill'; });
            }

            function echantillonner(audio, cible) {
                const origine = audio.getChannelData(0);
                const longueur = Math.max(1, Math.round(origine.length * cible / audio.sampleRate));
                const donnees = new Float32Array(longueur);
                for (let i = 0; i < longueur; i++) {
                    donnees[i] = origine[Math.min(origine.length - 1, Math.floor(i * audio.sampleRate / cible))] || 0;
                }
                const tampon = new ArrayBuffer(44 + longueur * 2);
                const vue = new DataView(tampon);
                const texte = (offset, valeur) => { for (let i = 0; i < valeur.length; i++) vue.setUint8(offset + i, valeur.charCodeAt(i)); };
                texte(0, 'RIFF'); vue.setUint32(4, 36 + longueur * 2, true); texte(8, 'WAVE'); texte(12, 'fmt ');
                vue.setUint32(16, 16, true); vue.setUint16(20, 1, true); vue.setUint16(22, 1, true);
                vue.setUint32(24, cible, true); vue.setUint32(28, cible * 2, true); vue.setUint16(32, 2, true); vue.setUint16(34, 16, true);
                texte(36, 'data'); vue.setUint32(40, longueur * 2, true);
                let curseur = 44;
                for (let i = 0; i < longueur; i++, curseur += 2) {
                    vue.setInt16(curseur, Math.max(-1, Math.min(1, donnees[i])) * 0x7fff, true);
                }
                return tampon;
            }
        </script>
    @else
        @php
            $questions = [
                'Quel est le prix conseillé pour mes '.$accueil['produit'].' ?',
                'Comment ajouter un nouveau produit ?',
                'Comment trouver des acheteurs proches ?',
                'Comment améliorer mon score de confiance ?',
            ];
        @endphp
        <div class="ia">
            <div class="ia-main">
                <section class="ia-hero">
                    <div class="ia-hero-copy">
                        <span class="ia-kicker"><i class="bi bi-stars"></i> Assistant IA</span>
                        <h1>Bonjour {{ $accueil['prenom'] }} !</h1>
                        <p>Je suis votre assistant intelligent. Je vous aide à gérer vos produits, vos ventes et à trouver rapidement des acheteurs près de chez vous.</p>
                        <span class="ia-pill"><i class="bi bi-leaf"></i> Choisissez ce que vous voulez faire aujourd’hui : ajouter un produit ou discuter avec moi.</span>
                    </div>
                    <div class="ia-hero-bot">
                        <span class="ia-bubble">Je suis là pour vous simplifier la vie !</span>
                        <img src="{{ asset('images/home/assistant-robot.png') }}" alt="Assistant agricole de Agriconnect">
                    </div>
                </section>

                <div class="ia-choices">
                    <article class="ia-choice">
                        <span class="ia-mark">
                            <img src="{{ asset('images/home/produit-legumes.jpg') }}" alt="">
                            <i class="bi bi-plus-lg"></i>
                        </span>
                        <h2>Ajouter un produit</h2>
                        <p>Décrivez rapidement votre produit et publiez-le pour trouver des acheteurs près de chez vous.</p>
                        <ul>
                            <li><i class="bi bi-check-lg"></i> Simple et rapide</li>
                            <li><i class="bi bi-check-lg"></i> Prix conseillé par l’IA</li>
                            <li><i class="bi bi-check-lg"></i> Publication en quelques clics</li>
                        </ul>
                        <form method="POST" action="{{ route('vendeur.assistant.message') }}">
                            @csrf
                            <button type="submit" name="message" value="ajouter un produit">Commencer <i class="bi bi-arrow-right"></i></button>
                        </form>
                    </article>
                    <article class="ia-choice">
                        <span class="ia-mark ia-mark-talk"><i class="bi bi-chat-dots-fill"></i></span>
                        <h2>Discuter</h2>
                        <p>Posez vos questions, obtenez des conseils sur vos produits, vos prix, vos commandes et bien plus encore.</p>
                        <ul>
                            <li><i class="bi bi-check-lg"></i> Conseils personnalisés</li>
                            <li><i class="bi bi-check-lg"></i> Analyse de vos produits (photo)</li>
                            <li><i class="bi bi-check-lg"></i> Aide à la vente</li>
                        </ul>
                        <form method="POST" action="{{ route('vendeur.assistant.message') }}">
                            @csrf
                            <button class="ia-btn-line" type="submit" name="message" value="discuter"><i class="bi bi-chat"></i> Démarrer une discussion</button>
                        </form>
                    </article>
                    <article class="ia-choice">
                        <span class="ia-mark ia-mark-ewe"><i class="bi bi-translate"></i></span>
                        <h2>Conversation en éwé</h2>
                        <p>Parlez en éwé pour décrire un produit ou poser une question sur vos ventes.</p>
                        <ul>
                            <li><i class="bi bi-check-lg"></i> Éwé du marché, pour commencer</li>
                            <li><i class="bi bi-check-lg"></i> Timáti, akɔɖu, atádí, sabala…</li>
                            <li><i class="bi bi-check-lg"></i> Rien n’est publié sans « Ẽ »</li>
                        </ul>
                        <form method="POST" action="{{ route('vendeur.assistant.message') }}">
                            @csrf
                            <button class="ia-btn-line" type="submit" name="message" value="conversation en ewe">Démarrer en éwé</button>
                        </form>
                    </article>
                </div>

                <section class="ia-tip">
                    <i class="bi bi-leaf"></i>
                    <div>
                        <strong>Un petit conseil aujourd’hui</strong>
                        <p>{{ $accueil['conseil'] }}</p>
                    </div>
                    <a href="{{ $accueil['stock'] ? route('vendeur.stocks.show', $accueil['stock']) : route('vendeur.produits') }}">Voir les opportunités <i class="bi bi-arrow-right"></i></a>
                </section>
            </div>

            <aside class="ia-side">
                <section class="ia-card">
                    <header>
                        <h2><i class="bi bi-lightbulb"></i> Questions fréquentes</h2>
                        <span>Voir tout</span>
                    </header>
                    @foreach ($questions as $question)
                        <form method="POST" action="{{ route('vendeur.assistant.message') }}">
                            @csrf
                            <button type="submit" name="message" value="{{ $question }}">{{ $question }} <i class="bi bi-chevron-right"></i></button>
                        </form>
                    @endforeach
                </section>

                <section class="ia-promo">
                    <div>
                        <h2>Votre réussite, notre priorité !</h2>
                        <p>L’IA vous aide à mieux vendre, à limiter les pertes et à augmenter vos revenus.</p>
                    </div>
                    <i class="bi bi-arrow-right"></i>
                </section>

                <section class="ia-card">
                    <header>
                        <h2>Mes statistiques IA</h2>
                        <a href="{{ route('vendeur.statistiques') }}">Voir plus</a>
                    </header>
                    @foreach ($accueil['stats'] as $stat)
                        <div class="ia-stat">
                            <i class="bi {{ $stat['icone'] }}"></i>
                            <div>
                                <span>{{ $stat['libelle'] }}</span>
                                <strong>{{ $stat['valeur'] }}</strong>
                            </div>
                            @if ($stat['delta'] > 0)
                                <em>+{{ $stat['delta'] }}{{ str_contains($stat['libelle'], 'Quantité') ? ' kg' : '' }} cette semaine</em>
                            @endif
                        </div>
                    @endforeach
                </section>
            </aside>
        </div>
    @endif
@endsection
