<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('audio_assets', function (Blueprint $table) {
            $table->string('engine', 64)->default('piper')->after('size_bytes');
            $table->string('text_hash', 64)->default('')->after('engine');
            $table->string('format', 16)->default('mp3')->after('text_hash');
            $table->string('voice', 100)->default('vi_VN-vais1000-medium')->change();
            $table->unique(
                ['chunk_id', 'engine', 'voice', 'speed', 'text_hash', 'format'],
                'audio_assets_cache_unique'
            );
        });

        Schema::table('tts_jobs', function (Blueprint $table) {
            $table->string('engine', 64)->default('piper')->after('provider');
            $table->decimal('speed', 4, 2)->default(1.00)->after('voice');
            $table->string('format', 16)->default('mp3')->after('speed');
            $table->string('text_hash', 64)->nullable()->after('format');
        });
    }

    public function down(): void
    {
        Schema::table('tts_jobs', function (Blueprint $table) {
            $table->dropColumn(['engine', 'speed', 'format', 'text_hash']);
        });

        Schema::table('audio_assets', function (Blueprint $table) {
            $table->dropUnique('audio_assets_cache_unique');
            $table->dropColumn(['engine', 'text_hash', 'format']);
            $table->string('voice', 100)->nullable()->change();
        });
    }
};
