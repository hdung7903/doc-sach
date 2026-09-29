<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chapter_chunks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('chapter_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->text('content');
            $table->unsignedInteger('word_count')->default(0);
            $table->timestamps();
            $table->unique(['chapter_id', 'position']);
        });
    }

    public function down(): void { Schema::dropIfExists('chapter_chunks'); }
};
