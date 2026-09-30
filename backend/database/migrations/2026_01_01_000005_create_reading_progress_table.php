<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reading_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('book_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('chapter_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('position_seconds')->default(0);
            $table->decimal('progress_percent', 5, 2)->default(0);
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'book_id']);
            $table->index(['user_id', 'last_read_at']);
        });
    }

    public function down(): void { Schema::dropIfExists('reading_progress'); }
};
