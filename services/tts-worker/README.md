# TTS Worker

Independent FastAPI service.

POST /v1/synthesize accepts text, voice and format=mp3, returning binary MP3.

The runtime is isolated from Laravel so Piper, Kokoro, or XTTS can be swapped without changing the API contract.
