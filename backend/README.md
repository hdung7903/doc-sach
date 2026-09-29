# Đọc Sách API

Laravel 12 API boundary for the reading platform.

## Domain
Book → Chapter → ChapterChunk → AudioAsset

User-owned state:
- ReadingProgress
- Bookmark

Processing:
- TTSJob

## API v1

GET    /api/v1/books
POST   /api/v1/books
GET    /api/v1/books/{book}
DELETE /api/v1/books/{book}

GET    /api/v1/books/{book}/chapters
GET    /api/v1/chapters/{chapter}

GET    /api/v1/books/{book}/progress
PUT    /api/v1/books/{book}/progress

GET    /api/v1/books/{book}/bookmarks
POST   /api/v1/books/{book}/bookmarks
DELETE /api/v1/bookmarks/{bookmark}

GET    /api/v1/chapters/{chapter}/audio

Authentication will use Laravel Sanctum.
