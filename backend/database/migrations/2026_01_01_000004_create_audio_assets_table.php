<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audio_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('chapter_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('chunk_id')->nullable()->constrained('chapter_chunks')->nullOnDelete();
            $table->string('storage_disk')->default('s3');
            $table->string('storage_path');
            $table->string('mime_type', 64)->default('audio/mpeg');
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('voice', 100)->nullable();
            $table->decimal('speed', 4, 2)->default(1.00);
            $table->timestamps();
            $table->index(['chapter_id', 'chunk_id']);
        });
    }

    public function down(): void { Schema::dropIfExists('audio_assets'); }
};
