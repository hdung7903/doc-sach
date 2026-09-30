<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSmokeTest extends TestCase
{
    public function test_core_database_schema_is_available(): void
    {
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('books'));
        $this->assertTrue(Schema::hasTable('chapters'));
        $this->assertTrue(Schema::hasTable('chapter_chunks'));
        $this->assertTrue(Schema::hasTable('audio_assets'));
        $this->assertTrue(Schema::hasTable('reading_progress'));
        $this->assertTrue(Schema::hasTable('bookmarks'));
        $this->assertTrue(Schema::hasTable('tts_jobs'));
    }

    public function test_books_endpoint_requires_authentication(): void
    {
        $this->withoutExceptionHandling();

        $this->getJson('/api/v1/books')->assertUnauthorized();
    }
}
