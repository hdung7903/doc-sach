<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chapters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('book_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('position');
            $table->longText('content')->nullable();
            $table->unsignedBigInteger('word_count')->default(0);
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamps();
            $table->unique(['book_id', 'position']);
            $table->index('book_id');
        });
    }

    public function down(): void { Schema::dropIfExists('chapters'); }
};
