<?php

use App\\Http\\Controllers\\Api\\V1\\BookController;\nuse App\\Http\\Controllers\\Api\\V1\\AudioController;\nuse App\\Http\\Controllers\\Api\\V1\\TtsController;
use App\\Http\\Controllers\\Api\\V1\\ChapterController;
use App\\Http\\Controllers\\Api\\V1\\ReadingProgressController;
use Illuminate\\Support\\Facades\\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::apiResource('books', BookController::class)->only(['index','store','show','destroy']);
    Route::get('books/{book}/chapters', [ChapterController::class, 'index']);
    Route::get('chapters/{chapter}', [ChapterController::class, 'show']);\n    Route::get('chapters/{chapter}/audio', [AudioController::class, 'index']);\n    Route::post('chapters/{chapter}/tts', [TtsController::class, 'store']);\n    Route::get('tts-jobs/{ttsJob}', [TtsController::class, 'show']);
    Route::get('books/{book}/progress', [ReadingProgressController::class, 'show']);
    Route::put('books/{book}/progress', [ReadingProgressController::class, 'update']);
});
