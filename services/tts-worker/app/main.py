from fastapi import FastAPI, Header, HTTPException
from pydantic import BaseModel

app = FastAPI(title='DocSach TTS Worker', version='0.1.0')

class SynthesizeRequest(BaseModel):
    text: str
    voice: str | None = None
    format: str = 'mp3'

@app.get('/health')
def health():
    return {'status': 'ok'}

@app.post('/v1/synthesize')
def synthesize(payload: SynthesizeRequest, x_worker_token: str | None = Header(default=None)):
    if payload.format != 'mp3':
        raise HTTPException(status_code=400, detail='Only mp3 is supported initially.')
    if not payload.text.strip():
        raise HTTPException(status_code=422, detail='text is required')
    raise HTTPException(status_code=501, detail='TTS runtime is not configured yet')
