<?php

return [
    'tts' => [
        'url' => env('TTS_WORKER_URL', 'http://127.0.0.1:8100'),
        'token' => env('TTS_WORKER_TOKEN'),
    ],
];
