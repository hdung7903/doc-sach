<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AudioController extends Controller
{
    public function index(Request $request, Chapter $chapter): JsonResponse
    {
        abort_unless($chapter->book->user_id === $request->user()->id, 404);

        $assets = $chapter->audioAssets()
            ->with('chunk:id,position')
            ->orderBy('chunk_id')
            ->get()
            ->sortBy(fn ($asset) => $asset->chunk?->position ?? PHP_INT_MAX)
            ->values();

        return response()->json([
            'chapter_id' => $chapter->id,
            'items' => $assets->map(fn ($asset) => [
                'id' => $asset->id,
                'chunk_id' => $asset->chunk_id,
                'position' => $asset->chunk?->position,
                'duration_seconds' => $asset->duration_seconds,
                'mime_type' => $asset->mime_type,
                'url' => Storage::disk($asset->storage_disk)->url($asset->storage_path),
                'engine' => $asset->engine,
                'text_hash' => $asset->text_hash,
                'format' => $asset->format,
                'voice' => $asset->voice,
                'speed' => (float) $asset->speed,
            ]),
        ]);
    }
}
