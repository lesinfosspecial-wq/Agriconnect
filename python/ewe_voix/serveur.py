"""Voix éwé pour VAƑLE.

Le texte n'est jamais renvoyé à l'écran : Laravel le garde en coulisse.
MMS de Meta transcrit (facebook/mms-1b-all, adaptateur ewe)
et lit la réponse (facebook/mms-tts-ewe). Ce second modèle est
entraîné pour l'éwé. Les tons et les mots français (FCFA, Adidogomé)
restent la limite connue.
"""

import io
import os
import wave
from threading import Lock

os.environ.setdefault("OMP_NUM_THREADS", str(os.cpu_count() or 4))
os.environ.setdefault("MKL_NUM_THREADS", os.environ["OMP_NUM_THREADS"])

import numpy as np
from flask import Flask, jsonify, request, send_file

PORT = 8765
ASR_ID = "facebook/mms-1b-all"
TTS_ID = "facebook/mms-tts-ewe"
LANGUE = "ewe"

app = Flask(__name__)
verrou = Lock()
asr = None
tts = None
tokeniseur = None


def charger_asr():
    global asr
    if asr is not None:
        return asr
    from transformers import AutoProcessor, Wav2Vec2ForCTC

    processeur = AutoProcessor.from_pretrained(ASR_ID)
    modele = Wav2Vec2ForCTC.from_pretrained(ASR_ID)
    processeur.tokenizer.set_target_lang(LANGUE)
    modele.load_adapter(LANGUE)
    modele.eval()
    import torch

    torch.set_num_threads(int(os.environ["OMP_NUM_THREADS"]))
    asr = (processeur, modele)
    return asr


def charger_tts():
    global tts, tokeniseur
    if tts is not None:
        return tts, tokeniseur
    from transformers import AutoTokenizer, VitsModel

    tokeniseur = AutoTokenizer.from_pretrained(TTS_ID)
    tts = VitsModel.from_pretrained(TTS_ID)
    tts.eval()
    import torch

    torch.set_num_threads(int(os.environ["OMP_NUM_THREADS"]))
    return tts, tokeniseur


def lire_wav(octets: bytes) -> np.ndarray:
    with wave.open(io.BytesIO(octets), "rb") as fichier:
        canaux = fichier.getnchannels()
        cadence = fichier.getframerate()
        signal = np.frombuffer(fichier.readframes(fichier.getnframes()), dtype=np.int16)
    if canaux > 1:
        signal = signal.reshape(-1, canaux)[:, 0]
    audio = signal.astype(np.float32) / 32768.0
    if cadence != 16000 and len(audio) > 1:
        duree = len(audio) / cadence
        cible = max(1, int(duree * 16000))
        audio = np.interp(
            np.linspace(0, len(audio) - 1, cible),
            np.arange(len(audio)),
            audio,
        ).astype(np.float32)
    return audio


def ecrire_wav(signal: np.ndarray, cadence: int) -> bytes:
    valeurs = np.clip(signal, -1, 1)
    entiers = (valeurs * 32767).astype(np.int16)
    tampon = io.BytesIO()
    with wave.open(tampon, "wb") as fichier:
        fichier.setnchannels(1)
        fichier.setsampwidth(2)
        fichier.setframerate(cadence)
        fichier.writeframes(entiers.tobytes())
    return tampon.getvalue()


@app.get("/sante")
def sante():
    return jsonify({
        "pret": True,
        "modeles": asr is not None and tts is not None,
        "langue": LANGUE,
        "asr": ASR_ID,
        "tts": TTS_ID,
    })


@app.post("/transcrire")
def transcrire():
    fichier = request.files.get("audio")
    if fichier is None:
        return jsonify({"texte": ""}), 422
    audio = lire_wav(fichier.read())
    if len(audio) < 1600:
        return jsonify({"texte": ""})
    try:
        with verrou:
            processeur, modele = charger_asr()
            import torch

            entree = processeur(audio, sampling_rate=16000, return_tensors="pt")
            with torch.inference_mode():
                logits = modele(**entree).logits
            ids = torch.argmax(logits, dim=-1)[0]
            texte = processeur.decode(ids)
    except Exception as erreur:
        return jsonify({"texte": "", "erreur": str(erreur)}), 503
    return jsonify({"texte": (texte or "").strip()})


@app.post("/parler")
def parler():
    texte = (request.form.get("texte") or (request.get_json(silent=True) or {}).get("texte") or "").strip()
    if texte == "":
        return jsonify({"erreur": "texte vide"}), 422
    with verrou:
        modele, tok = charger_tts()
        import torch

        entree = tok(texte, return_tensors="pt")
        with torch.inference_mode():
            onde = modele(**entree).waveform[0].cpu().numpy()
    return send_file(
        io.BytesIO(ecrire_wav(onde, int(modele.config.sampling_rate))),
        mimetype="audio/wav",
        download_name="note.wav",
    )


def journal(message: str) -> None:
    print(message, flush=True)


def precharger() -> None:
    from huggingface_hub.utils import enable_progress_bars

    enable_progress_bars()
    journal("Telechargement du modele qui ecoute (environ 3,9 Go).")
    try:
        charger_asr()
        journal("Modele d'ecoute pret.")
        journal("Telechargement du modele qui parle.")
        charger_tts()
        journal("Les deux modeles sont prets. Vous pouvez envoyer une note vocale.")
    except Exception as erreur:
        journal(f"Echec du chargement : {erreur}")


if __name__ == "__main__":
    import sys

    try:
        sys.stdout.write("")
        sys.stdout.flush()
    except OSError:
        pass

    precharger()

    from werkzeug.serving import run_simple

    journal("Service de voix sur http://127.0.0.1:8765")
    run_simple("127.0.0.1", PORT, app, threaded=True, use_reloader=False)
