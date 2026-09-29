<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AudioController;
use App\Http\Controllers\Api\V1\BookController;
use App\Http\Controllers\Api\V1\ChapterController;
use App\Http\Controllers\Api\V1\ReadingProgressController;
use App\Http\Controllers\Api\V1\TtsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::apiResource('books', BookController::class)->only(['index','store','show','destroy']);
        Route::get('books/{book}/chapters', [ChapterController::class, 'index']);
        Route::get('chapters/{chapter}', [ChapterController::class, 'show']);
        Route::get('chapters/{chapter}/audio', [AudioController::class, 'index']);
        Route::post('chapters/{chapter}/tts', [TtsController::class, 'store']);
        Route::get('tts-jobs/{ttsJob}', [TtsController::class, 'show']);
        Route::get('books/{book}/progress', [ReadingProgressController::class, 'show']);
        Route::put('books/{book}/progress', [ReadingProgressController::class, 'update']);
    });
});
