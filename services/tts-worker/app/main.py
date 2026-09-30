import os
import subprocess
import tempfile
from pathlib import Path

from fastapi import FastAPI, Header, HTTPException
from fastapi.responses import Response
from pydantic import BaseModel, Field
from piper import PiperVoice, SynthesisConfig

APP_VERSION = "0.2.0"
MODEL_PATH = Path(os.getenv("PIPER_MODEL", "/models/vi_VN-vais1000-medium.onnx"))
WORKER_TOKEN = os.getenv("TTS_WORKER_TOKEN")
DEFAULT_SPEED = float(os.getenv("PIPER_LENGTH_SCALE", "1.0"))
NOISE_SCALE = float(os.getenv("PIPER_NOISE_SCALE", "0.667"))
NOISE_W = float(os.getenv("PIPER_NOISE_W", "0.8"))

app = FastAPI(title="DocSach TTS Worker", version=APP_VERSION)
voice: PiperVoice | None = None

class SynthesizeRequest(BaseModel):
    text: str = Field(min_length=1, max_length=12000)
    voice: str | None = None
    format: str = "mp3"
    speed: float = Field(default=1.0, ge=0.5, le=2.0)

def authorize(token: str | None) -> None:
    if WORKER_TOKEN and token != WORKER_TOKEN:
        raise HTTPException(status_code=401, detail="Invalid worker token")

def get_voice() -> PiperVoice:
    global voice
    if voice is None:
        if not MODEL_PATH.exists():
            raise HTTPException(status_code=503, detail="Piper voice model is missing")
        voice = PiperVoice.load(MODEL_PATH)
    return voice

@app.on_event("startup")
def warmup() -> None:
    get_voice()

@app.get("/health")
def health():
    return {"status": "ok", "version": APP_VERSION, "engine": "piper",
            "model": MODEL_PATH.name, "model_exists": MODEL_PATH.exists()}

@app.post("/v1/synthesize")
def synthesize(payload: SynthesizeRequest, x_worker_token: str | None = Header(default=None)):
    authorize(x_worker_token)
    if payload.format != "mp3":
        raise HTTPException(status_code=400, detail="Only mp3 is supported.")
    text = payload.text.strip()
    if not text:
        raise HTTPException(status_code=422, detail="text is required")

    tts_voice = get_voice()
    with tempfile.TemporaryDirectory(prefix="docsach-tts-") as tmp:
        wav_path = Path(tmp) / "speech.wav"
        mp3_path = Path(tmp) / "speech.mp3"
        config = SynthesisConfig(
            length_scale=DEFAULT_SPEED / payload.speed,
            noise_scale=NOISE_SCALE,
            noise_w_scale=NOISE_W,
            normalize_audio=True,
        )
        import wave
        with wave.open(str(wav_path), "wb") as wav_file:
            tts_voice.synthesize_wav(text, wav_file, syn_config=config)

        result = subprocess.run(
            ["ffmpeg", "-hide_banner", "-loglevel", "error", "-y", "-i", str(wav_path),
             "-codec:a", "libmp3lame", "-b:a", "96k", str(mp3_path)],
            check=False, capture_output=True, text=True,
        )
        if result.returncode != 0:
            raise HTTPException(status_code=500, detail=result.stderr[-1000:])

        duration_result = subprocess.run(
            ["ffprobe", "-v", "error", "-show_entries", "format=duration",
             "-of", "default=noprint_wrappers=1:nokey=1", str(mp3_path)],
            check=False, capture_output=True, text=True,
        )
        if duration_result.returncode != 0:
            raise HTTPException(status_code=500, detail=duration_result.stderr[-1000:])

        try:
            duration_seconds = float(duration_result.stdout.strip())
        except ValueError as exc:
            raise HTTPException(status_code=500, detail="Could not determine audio duration") from exc

        return Response(
            content=mp3_path.read_bytes(),
            media_type="audio/mpeg",
            headers={
                "Content-Disposition": "attachment; filename=\"speech.mp3\"",
                "X-Audio-Duration": f"{duration_seconds:.3f}",
            },
        )
