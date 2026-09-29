# Đọc Sách

Cross-platform personal reading and audiobook platform.

## MVP
- Next.js web reader
- EPUB ingestion
- Reading progress and bookmarks
- Audio player architecture
- Expo mobile app planned
- Laravel API + PostgreSQL + Redis planned
- Python TTS worker planned

## Structure
- apps/web — Next.js web app
- apps/mobile — Expo mobile app
- backend — Laravel API
- services/tts-worker — self-hosted TTS worker
- packages/types — shared domain types

## Development
pnpm install
pnpm dev
