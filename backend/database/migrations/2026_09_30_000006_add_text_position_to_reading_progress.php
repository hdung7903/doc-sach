<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reading_progress', function (Blueprint $table) {
            $table->unsignedInteger('text_position_percent')->default(0)->after('progress_percent');
            $table->unsignedBigInteger('audio_position_seconds')->default(0)->after('text_position_percent');
            $table->index(['book_id', 'chapter_id']);
        });

        Schema::table('reading_progress', function (Blueprint $table) {
            $table->unsignedBigInteger('position_seconds')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('reading_progress', function (Blueprint $table) {
            $table->dropIndex(['book_id', 'chapter_id']);
            $table->dropColumn(['text_position_percent', 'audio_position_seconds']);
        });
    }
};
