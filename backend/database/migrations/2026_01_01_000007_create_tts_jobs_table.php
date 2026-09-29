<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tts_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('book_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('chapter_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 24)->default('queued');
            $table->string('provider', 64)->default('self-hosted');
            $table->string('voice', 100)->nullable();
            $table->unsignedInteger('processed_chunks')->default(0);
            $table->unsignedInteger('total_chunks')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['book_id', 'status']);
        });
    }

    public function down(): void { Schema::dropIfExists('tts_jobs'); }
};
