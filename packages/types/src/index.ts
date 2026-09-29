export type BookStatus = "draft" | "ready" | "processing" | "failed";

export interface Book { id: string; title: string; author?: string; status: BookStatus; coverUrl?: string; progress: number; }
export interface Chapter { id: string; bookId: string; title: string; position: number; text?: string; durationSeconds?: number; }
export interface ReadingProgress { bookId: string; chapterId?: string; positionSeconds: number; progressPercent: number; updatedAt: string; }
