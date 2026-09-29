"use client";

import { useCallback, useEffect, useState } from "react";
import { BookOpen, Headphones, Library, Loader2, Play, RefreshCw } from "lucide-react";
import AudioPlayer, { type AudioItem } from "./components/AudioPlayer";

type Book = { id: string; title: string; author?: string; status: string; progress?: number };
type Chapter = { id: string; title: string; position: number };
type ApiList<T> = { data: T[] };
type TtsResponse = { id: string | null; status: string; cached_chunks: number; total_chunks: number };

const API = process.env.NEXT_PUBLIC_BACKEND_URL ?? "http://127.0.0.1:8000";
const tokenKey = "doc-sach:token";

async function api<T>(path: string, init?: RequestInit): Promise<T> {
  const token = typeof window !== "undefined" ? window.localStorage.getItem(tokenKey) : null;
  const response = await fetch(`${API}/api/v1/${path}`, {
    ...init,
    headers: { Accept: "application/json", ...(init?.body ? { "Content-Type": "application/json" } : {}), ...(token ? { Authorization: `Bearer ${token}` } : {}), ...init?.headers },
  });
  if (!response.ok) throw new Error((await response.text()) || `HTTP ${response.status}`);
  return response.json();
}

export default function Home() {
  const [books, setBooks] = useState<Book[]>([]);
  const [chapters, setChapters] = useState<Chapter[]>([]);
  const [selectedBook, setSelectedBook] = useState<Book | null>(null);
  const [selectedChapter, setSelectedChapter] = useState<Chapter | null>(null);
  const [audio, setAudio] = useState<AudioItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [loadingAudio, setLoadingAudio] = useState(false);
  const [ttsStatus, setTtsStatus] = useState("");
  const [error, setError] = useState("");

  const loadBooks = useCallback(async () => {
    try {
      setLoading(true); setError("");
      const result = await api<ApiList<Book>>("books");
      setBooks(result.data ?? []);
    } catch (e) { setError(e instanceof Error ? e.message : "Không tải được thư viện."); }
    finally { setLoading(false); }
  }, []);

  useEffect(() => { void loadBooks(); }, [loadBooks]);

  const selectBook = async (book: Book) => {
    setSelectedBook(book); setSelectedChapter(null); setAudio([]); setTtsStatus(""); setError("");
    try {
      const result = await api<ApiList<Chapter>>(`books/${book.id}/chapters`);
      setChapters((result.data ?? []).sort((a,b) => a.position - b.position));
    } catch (e) { setError(e instanceof Error ? e.message : "Không tải được chương."); }
  };

  const loadAudio = async (chapter: Chapter, generateIfMissing = true) => {
    setSelectedChapter(chapter); setLoadingAudio(true); setError(""); setTtsStatus("");
    try {
      const result = await api<{ items: AudioItem[] }>(`chapters/${chapter.id}/audio`);
      const items = result.items ?? [];
      setAudio(items);
      if (items.length || !generateIfMissing) return;
      setTtsStatus("Audio chưa có cache. Đang tạo audiobook…");
      const job = await api<TtsResponse>(`chapters/${chapter.id}/tts`, { method: "POST", body: JSON.stringify({ voice: "vi_VN-vais1000-medium", speed: 1, }) });
      if (job.status === "cached") {
        await loadAudio(chapter, false);
        return;
      }
      if (!job.id) return;
      for (let attempt = 0; attempt < 60; attempt++) {
        await new Promise(resolve => setTimeout(resolve, 2000));
        const status = await api<TtsResponse & { error_message?: string }>(`tts-jobs/${job.id}`);
        setTtsStatus(`Đang tạo audio: ${status.cached_chunks ?? status.total_chunks - 1}/${status.total_chunks}`);
        if (status.status === "completed") {
          await loadAudio(chapter, false);
          return;
        }
        if (status.status === "failed") throw new Error(status.error_message || "TTS thất bại.");
      }
      throw new Error("TTS vẫn đang xử lý. Bạn có thể chọn lại chương sau.");
    } catch (e) { setError(e instanceof Error ? e.message : "Không tải được audio."); }
    finally { setLoadingAudio(false); }
  };

  return <main className="shell">
    <header className="topbar"><div className="brand"><BookOpen size={22}/> Đọc Sách</div><nav><a href="#library">Thư viện</a><a href="#audio">Audiobook</a></nav></header>

    <section className="hero">
      <div><p className="eyebrow">PERSONAL READING PLATFORM</p><h1>Đọc sách. Nghe sách.<br/>Tiếp tục ở mọi thiết bị.</h1><p className="lead">Thư viện cá nhân cho EPUB và audiobook, đồng bộ tiến độ giữa web và điện thoại.</p><div className="actions"><button onClick={() => document.getElementById("library")?.scrollIntoView({behavior:"smooth"})}><Library size={18}/> Mở thư viện</button><button className="secondary" onClick={() => document.getElementById("audio")?.scrollIntoView({behavior:"smooth"})}><Headphones size={18}/> Nghe sách</button></div></div>
      <div className="hero-card"><BookOpen size={42}/><strong>EPUB → Reader → TTS → Cache</strong><span>Audio đã chuyển đổi được lưu lại để phát lại mà không cần TTS lần nữa.</span></div>
    </section>

    <section id="library" className="section">
      <div className="section-title"><div><p className="eyebrow">LIBRARY</p><h2>Thư viện của bạn</h2></div><button className="secondary" onClick={() => void loadBooks()}><RefreshCw size={16}/> Làm mới</button></div>
      {error && <p className="error">{error}</p>}
      {loading ? <div className="loading"><Loader2 className="spin" size={20}/> Đang tải thư viện…</div> :
      <div className="grid">{books.map(book=><article className={`book ${selectedBook?.id === book.id ? "selected" : ""}`} key={book.id} onClick={() => void selectBook(book)}>
        <div className="cover"><BookOpen size={30}/></div><div><h3>{book.title}</h3><p>{book.author || "Không rõ tác giả"}</p><small>{book.status}</small></div><button className="icon" onClick={(event) => { event.stopPropagation(); void selectBook(book); }}><Play size={17}/></button>
      </article>)}</div>}
    </section>

    {selectedBook && <section className="section chapter-section"><div className="section-title"><div><p className="eyebrow">CHAPTERS</p><h2>{selectedBook.title}</h2></div></div><div className="chapters">{chapters.map(chapter=><button key={chapter.id} className={`chapter ${selectedChapter?.id === chapter.id ? "active" : ""}`} onClick={() => void loadAudio(chapter)}><span>{chapter.position + 1}</span><strong>{chapter.title}</strong><Play size={16}/></button>)}</div></section>}

    <section id="audio" className="section audio-section">
      <div className="section-title"><div><p className="eyebrow">AUDIOBOOK</p><h2>{selectedChapter?.title || "Nghe sách"}</h2></div></div>
      {loadingAudio ? <div className="loading"><Loader2 className="spin" size={20}/> {ttsStatus || "Đang kiểm tra audio cache…"}</div> : <AudioPlayer items={audio} title={selectedChapter?.title || "Chọn một chương để bắt đầu"} />}
      {ttsStatus && !loadingAudio && <p className="tts-status">{ttsStatus}</p>}
    </section>
  </main>;
}
