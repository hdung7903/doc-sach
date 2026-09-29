"use client";

import { useCallback, useEffect, useRef, useState } from "react";
import {
  BookOpen,
  Headphones,
  Library,
  Loader2,
  LogIn,
  LogOut,
  Play,
  Plus,
  RefreshCw,
  Upload,
  UserPlus,
} from "lucide-react";
import AudioPlayer, { type AudioItem } from "./components/AudioPlayer";

type Book = {
  id: string;
  title: string;
  author?: string;
  status: "draft" | "ready" | "processing" | "failed" | string;
  progress?: number;
};
type Chapter = { id: string; title: string; position: number };
type ChapterDetail = Chapter & { content: string; word_count: number; duration_seconds?: number | null };
type ReadingProgress = { chapter_id: string | null; position_seconds: number; progress_percent: number };
type ApiList<T> = { data: T[] };
type TtsResponse = {
  id: string | null;
  status: string;
  cached_chunks?: number;
  processed_chunks?: number;
  total_chunks: number;
  error_message?: string;
};

const API = process.env.NEXT_PUBLIC_BACKEND_URL ?? "http://127.0.0.1:8000";
const tokenKey = "doc-sach:token";
const userKey = "doc-sach:user";

async function api<T>(path: string, init?: RequestInit): Promise<T> {
  const token =
    typeof window !== "undefined"
      ? window.localStorage.getItem(tokenKey)
      : null;
  const isFormData =
    typeof FormData !== "undefined" && init?.body instanceof FormData;

  const response = await fetch(`${API}/api/v1/${path}`, {
    ...init,
    headers: {
      Accept: "application/json",
      ...(init?.body && !isFormData
        ? { "Content-Type": "application/json" }
        : {}),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...init?.headers,
    },
  });

  if (!response.ok) {
    let message = `HTTP ${response.status}`;
    try {
      const body = await response.json();
      message = body.message || body.errors
        ? body.message || Object.values(body.errors).flat().join(" ")
        : message;
    } catch {
      // Keep the HTTP fallback.
    }
    throw new Error(message);
  }

  if (response.status === 204) return undefined as T;
  return response.json();
}

export default function Home() {
  const [token, setToken] = useState<string | null>(null);
  const [userName, setUserName] = useState("");
  const [authMode, setAuthMode] = useState<"login" | "register">("login");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [name, setName] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [authLoading, setAuthLoading] = useState(false);

  const [books, setBooks] = useState<Book[]>([]);
  const [chapters, setChapters] = useState<Chapter[]>([]);
  const [selectedBook, setSelectedBook] = useState<Book | null>(null);
  const [selectedChapter, setSelectedChapter] = useState<Chapter | null>(null);
  const [chapterDetail, setChapterDetail] = useState<ChapterDetail | null>(null);
  const [readingProgress, setReadingProgress] = useState<ReadingProgress | null>(null);
  const [readerOpen, setReaderOpen] = useState(false);
  const [audio, setAudio] = useState<AudioItem[]>([]);
  const [readerSaving, setReaderSaving] = useState(false);
  const [loading, setLoading] = useState(false);
  const [loadingAudio, setLoadingAudio] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [ttsStatus, setTtsStatus] = useState("");
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");
  const fileInputRef = useRef<HTMLInputElement>(null);
  const readerContentRef = useRef<HTMLElement | null>(null);
  const readerSaveTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const restoringReaderRef = useRef(false);

  useEffect(() => {
    const savedToken = window.localStorage.getItem(tokenKey);
    const savedUser = window.localStorage.getItem(userKey);
    setToken(savedToken);
    if (savedUser) {
      try {
        setUserName(JSON.parse(savedUser).name || "");
      } catch {
        // Ignore stale user metadata.
      }
    }
  }, []);

  const loadBooks = useCallback(async () => {
    if (!token) return;
    try {
      setLoading(true);
      setError("");
      const result = await api<ApiList<Book>>("books");
      setBooks(result.data ?? []);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Không tải được thư viện.");
    } finally {
      setLoading(false);
    }
  }, [token]);

  useEffect(() => {
    void loadBooks();
  }, [loadBooks]);

  const authenticate = async (event: React.FormEvent) => {
    event.preventDefault();
    try {
      setAuthLoading(true);
      setError("");
      const path =
        authMode === "login" ? "auth/login" : "auth/register";
      const payload =
        authMode === "login"
          ? { email, password }
          : {
              name,
              email,
              password,
              password_confirmation: passwordConfirmation,
            };
      const result = await api<{
        token: string;
        user: { name: string; email: string };
      }>(path, {
        method: "POST",
        body: JSON.stringify(payload),
      });

      window.localStorage.setItem(tokenKey, result.token);
      window.localStorage.setItem(userKey, JSON.stringify(result.user));
      setToken(result.token);
      setUserName(result.user.name);
      setPassword("");
      setPasswordConfirmation("");
      setNotice(authMode === "login" ? "Đăng nhập thành công." : "Tài khoản đã được tạo.");
    } catch (e) {
      setError(e instanceof Error ? e.message : "Xác thực thất bại.");
    } finally {
      setAuthLoading(false);
    }
  };

  const logout = async () => {
    try {
      if (token) await api("auth/logout", { method: "POST" });
    } catch {
      // Local token is still cleared below.
    }
    window.localStorage.removeItem(tokenKey);
    window.localStorage.removeItem(userKey);
    setToken(null);
    setUserName("");
    setBooks([]);
    setSelectedBook(null);
    setSelectedChapter(null);
    setChapterDetail(null);
    setReaderOpen(false);
    setAudio([]);
    if (readerSaveTimerRef.current) {
      clearTimeout(readerSaveTimerRef.current);
      readerSaveTimerRef.current = null;
    }
  };

  const selectBook = async (book: Book) => {
    setSelectedBook(book);
    setSelectedChapter(null);
    setChapterDetail(null);
    setReadingProgress(null);
    setReaderOpen(false);
    setAudio([]);
    setTtsStatus("");
    setError("");
    try {
      const result = await api<ApiList<Chapter>>(`books/${book.id}/chapters`);
      setChapters((result.data ?? []).sort((a, b) => a.position - b.position));
      const progress = await api<ReadingProgress | null>(`books/${book.id}/progress`);
      setReadingProgress(progress);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Không tải được chương.");
    }
  };

  const openReader = async (chapter: Chapter) => {
    if (!selectedBook) return;
    try {
      setError("");
      const [detail, progress] = await Promise.all([
        api<ChapterDetail>(`chapters/${chapter.id}`),
        api<ReadingProgress | null>(`books/${selectedBook.id}/progress`),
      ]);
      setSelectedChapter(chapter);
      setChapterDetail(detail);
      setReadingProgress(progress);
      restoringReaderRef.current = true;
      setReaderOpen(true);
    } catch (e) {
      setError(e instanceof Error ? e.message : "Không mở được chương.");
    }
  };

  const saveReaderProgress = async (percent: number) => {
    if (!selectedBook || !chapterDetail) return;
    try {
      setReaderSaving(true);
      const progress = await api<ReadingProgress>(`books/${selectedBook.id}/progress`, {
        method: "PUT",
        body: JSON.stringify({
          chapter_id: chapterDetail.id,
          position_seconds: 0,
          progress_percent: Math.max(0, Math.min(100, Number(percent.toFixed(2)))),
        }),
      });
      setReadingProgress(progress);
      setBooks((items) => items.map((book) =>
        book.id === selectedBook.id ? { ...book, progress: progress.progress_percent } : book
      ));
    } catch (e) {
      setError(e instanceof Error ? e.message : "Không lưu được tiến độ.");
    } finally {
      setReaderSaving(false);
    }
  };

  useEffect(() => {
    if (!readerOpen || !chapterDetail || !readerContentRef.current) return;

    const element = readerContentRef.current;
    const percent = Number(readingProgress?.progress_percent ?? 0);
    const restore = () => {
      const maxScroll = Math.max(0, element.scrollHeight - element.clientHeight);
      element.scrollTop = maxScroll * (Math.max(0, Math.min(100, percent)) / 100);
      restoringReaderRef.current = false;
    };

    const frame = window.requestAnimationFrame(restore);
    return () => window.cancelAnimationFrame(frame);
  }, [readerOpen, chapterDetail, readingProgress?.chapter_id]);

  useEffect(() => {
    if (!readerOpen || !chapterDetail || !readerContentRef.current) return;

    const element = readerContentRef.current;
    const handleScroll = () => {
      if (restoringReaderRef.current) return;

      const maxScroll = Math.max(0, element.scrollHeight - element.clientHeight);
      const percent = maxScroll === 0 ? 100 : (element.scrollTop / maxScroll) * 100;
      const rounded = Number(percent.toFixed(2));
      const current = Number(readingProgress?.progress_percent ?? 0);

      setReadingProgress((progress) => progress
        ? { ...progress, chapter_id: chapterDetail.id, progress_percent: rounded }
        : { chapter_id: chapterDetail.id, position_seconds: 0, progress_percent: rounded }
      );

      if (Math.abs(rounded - current) < 0.25) return;
      if (readerSaveTimerRef.current) clearTimeout(readerSaveTimerRef.current);
      readerSaveTimerRef.current = setTimeout(() => {
        void saveReaderProgress(rounded);
        readerSaveTimerRef.current = null;
      }, 1500);
    };

    element.addEventListener("scroll", handleScroll, { passive: true });
    return () => {
      element.removeEventListener("scroll", handleScroll);
      if (readerSaveTimerRef.current) {
        clearTimeout(readerSaveTimerRef.current);
        readerSaveTimerRef.current = null;
      }
    };
  }, [readerOpen, chapterDetail, readingProgress?.progress_percent]);

  const nextChapter = () => {
    if (!selectedChapter) return;
    const next = chapters.find((chapter) => chapter.position === selectedChapter.position + 1);
    if (next) void openReader(next);
  };

  const uploadBook = async (file: File) => {
    if (!token) return;
    if (!file.name.toLowerCase().endsWith(".epub")) {
      setError("Chỉ hỗ trợ file EPUB.");
      return;
    }

    const form = new FormData();
    const title = file.name.replace(/\.epub$/i, "").replace(/[_-]+/g, " ").trim();
    form.append("title", title || "Sách mới");
    form.append("source_format", "epub");
    form.append("file", file);

    try {
      setUploading(true);
      setError("");
      setNotice("Đang upload EPUB…");
      const created = await api<Book>("books", {
        method: "POST",
        body: form,
      });

      await loadBooks();
      setNotice("Upload xong. Đang phân tích nội dung EPUB…");

      for (let attempt = 0; attempt < 90; attempt++) {
        await new Promise((resolve) => setTimeout(resolve, 2000));
        const current = await api<Book>(`books/${created.id}`);
        setBooks((items) =>
          items.map((item) => (item.id === current.id ? current : item)),
        );

        if (current.status === "ready") {
          setNotice(`“${current.title}” đã sẵn sàng.`);
          await selectBook(current);
          return;
        }

        if (current.status === "failed") {
          throw new Error("Không thể xử lý EPUB. Hãy kiểm tra file hoặc log backend.");
        }
      }

      setNotice("EPUB vẫn đang được xử lý. Bạn có thể làm việc khác và bấm Làm mới sau.");
    } catch (e) {
      setError(e instanceof Error ? e.message : "Upload EPUB thất bại.");
    } finally {
      setUploading(false);
      if (fileInputRef.current) fileInputRef.current.value = "";
    }
  };

  const loadAudio = async (chapter: Chapter, generateIfMissing = true) => {
    setSelectedChapter(chapter);
    setLoadingAudio(true);
    setError("");
    setTtsStatus("");
    try {
      const result = await api<{ items: AudioItem[] }>(`chapters/${chapter.id}/audio`);
      const items = result.items ?? [];
      setAudio(items);
      if (items.length || !generateIfMissing) return;

      setTtsStatus("Audio chưa có cache. Đang tạo audiobook…");
      const job = await api<TtsResponse>(`chapters/${chapter.id}/tts`, {
        method: "POST",
        body: JSON.stringify({
          voice: "vi_VN-vais1000-medium",
          speed: 1,
        }),
      });

      if (job.status === "cached") {
        await loadAudio(chapter, false);
        return;
      }

      if (!job.id) return;
      for (let attempt = 0; attempt < 60; attempt++) {
        await new Promise((resolve) => setTimeout(resolve, 2000));
        const status = await api<TtsResponse>(`tts-jobs/${job.id}`);
        const processed = status.processed_chunks ?? status.cached_chunks ?? 0;
        setTtsStatus(
          `Đang tạo audio: ${processed}/${status.total_chunks}`,
        );

        if (status.status === "completed") {
          await loadAudio(chapter, false);
          return;
        }
        if (status.status === "failed") {
          throw new Error(status.error_message || "TTS thất bại.");
        }
      }
      throw new Error("TTS vẫn đang xử lý. Bạn có thể chọn lại chương sau.");
    } catch (e) {
      setError(e instanceof Error ? e.message : "Không tải được audio.");
    } finally {
      setLoadingAudio(false);
    }
  };

  if (!token) {
    return (
      <main className="auth-shell">
        <section className="auth-card">
          <div className="brand"><BookOpen size={22} /> Đọc Sách</div>
          <p className="eyebrow">PERSONAL READING PLATFORM</p>
          <h1>{authMode === "login" ? "Đăng nhập thư viện" : "Tạo tài khoản"}</h1>
          <p className="auth-copy">
            Sách và tiến độ được lưu theo tài khoản của bạn.
          </p>

          {error && <p className="error">{error}</p>}
          {notice && <p className="notice">{notice}</p>}

          <form onSubmit={authenticate} className="auth-form">
            {authMode === "register" && (
              <label>Tên
                <input value={name} onChange={(e) => setName(e.target.value)} required />
              </label>
            )}
            <label>Email
              <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
            </label>
            <label>Mật khẩu
              <input type="password" value={password} onChange={(e) => setPassword(e.target.value)} minLength={8} required />
            </label>
            {authMode === "register" && (
              <label>Nhập lại mật khẩu
                <input type="password" value={passwordConfirmation} onChange={(e) => setPasswordConfirmation(e.target.value)} minLength={8} required />
              </label>
            )}
            <button type="submit" disabled={authLoading}>
              {authLoading ? <Loader2 className="spin" size={17} /> : authMode === "login" ? <LogIn size={17} /> : <UserPlus size={17} />}
              {authMode === "login" ? "Đăng nhập" : "Đăng ký"}
            </button>
          </form>

          <button className="secondary auth-switch" onClick={() => {
            setAuthMode(authMode === "login" ? "register" : "login");
            setError("");
          }}>
            {authMode === "login" ? "Chưa có tài khoản? Đăng ký" : "Đã có tài khoản? Đăng nhập"}
          </button>
        </section>
      </main>
    );
  }

  return (
    <main className="shell">
      <header className="topbar">
        <div className="brand"><BookOpen size={22} /> Đọc Sách</div>
        <nav>
          <a href="#library">Thư viện</a>
          <a href="#audio">Audiobook</a>
          <span className="user-chip">{userName}</span>
          <button className="icon small-icon" title="Đăng xuất" onClick={() => void logout()}><LogOut size={16} /></button>
        </nav>
      </header>

      <section className="hero">
        <div>
          <p className="eyebrow">PERSONAL READING PLATFORM</p>
          <h1>Đọc sách. Nghe sách.<br />Tiếp tục ở mọi thiết bị.</h1>
          <p className="lead">Thư viện cá nhân cho EPUB và audiobook, đồng bộ tiến độ giữa web và điện thoại.</p>
          <div className="actions">
            <button onClick={() => document.getElementById("library")?.scrollIntoView({ behavior: "smooth" })}><Library size={18} /> Mở thư viện</button>
            <button className="secondary" onClick={() => document.getElementById("audio")?.scrollIntoView({ behavior: "smooth" })}><Headphones size={18} /> Nghe sách</button>
          </div>
        </div>
        <div className="hero-card">
          <BookOpen size={42} />
          <strong>EPUB → Reader → TTS → Cache</strong>
          <span>Audio đã chuyển đổi được lưu lại để phát lại mà không cần TTS lần nữa.</span>
        </div>
      </section>

      <section id="library" className="section">
        <div className="section-title">
          <div><p className="eyebrow">LIBRARY</p><h2>Thư viện của bạn</h2></div>
          <div className="section-actions">
            <input ref={fileInputRef} type="file" accept=".epub,application/epub+zip" hidden onChange={(e) => {
              const file = e.target.files?.[0];
              if (file) void uploadBook(file);
            }} />
            <button onClick={() => fileInputRef.current?.click()} disabled={uploading}>
              {uploading ? <Loader2 className="spin" size={16} /> : <Plus size={16} />}
              {uploading ? "Đang upload…" : "Thêm sách"}
            </button>
            <button className="secondary" onClick={() => void loadBooks()}><RefreshCw size={16} /> Làm mới</button>
          </div>
        </div>
        {error && <p className="error">{error}</p>}
        {notice && <p className="notice">{notice}</p>}
        {loading ? (
          <div className="loading"><Loader2 className="spin" size={20} /> Đang tải thư viện…</div>
        ) : books.length === 0 ? (
          <div className="empty-library"><Upload size={28} /><strong>Thư viện đang trống</strong><span>Chọn một file EPUB để bắt đầu.</span></div>
        ) : (
          <div className="grid">
            {books.map((book) => (
              <article className={`book ${selectedBook?.id === book.id ? "selected" : ""}`} key={book.id} onClick={() => void selectBook(book)}>
                <div className="cover"><BookOpen size={30} /></div>
                <div>
                  <h3>{book.title}</h3>
                  <p>{book.author || "Không rõ tác giả"}</p>
                  <span className={`status status-${book.status}`}>{book.status === "processing" ? "Đang xử lý" : book.status === "ready" ? "Sẵn sàng" : book.status === "failed" ? "Lỗi" : book.status}</span>
                </div>
                <button className="icon" onClick={(event) => { event.stopPropagation(); void selectBook(book); }}><Play size={17} /></button>
              </article>
            ))}
          </div>
        )}
      </section>

      {selectedBook && (
        <section className="section chapter-section">
          <div className="section-title"><div><p className="eyebrow">CHAPTERS</p><h2>{selectedBook.title}</h2></div></div>
          <div className="chapters">
            {chapters.map((chapter) => (
              <button key={chapter.id} className={`chapter ${selectedChapter?.id === chapter.id ? "active" : ""}`} onClick={() => void openReader(chapter)}>
                <span>{chapter.position}</span><strong>{chapter.title}</strong><BookOpen size={16} />
              </button>
            ))}
          </div>
          {readingProgress && (
            <p className="reader-progress-note">
              Tiến độ: {Number(readingProgress.progress_percent).toFixed(0)}%
              {readingProgress.chapter_id ? " · đã lưu trên thiết bị khác" : ""}
            </p>
          )}
        </section>
      )}

      {readerOpen && chapterDetail && (
        <section className="section reader-section">
          <div className="reader-head">
            <div>
              <p className="eyebrow">READER</p>
              <h2>{chapterDetail.title}</h2>
              <span>{chapterDetail.word_count.toLocaleString("vi-VN")} từ</span>
            </div>
            <div className="reader-actions">
              <button className="secondary" onClick={() => void saveReaderProgress(100)}>Đánh dấu đã đọc</button>
              <button className="secondary" onClick={nextChapter} disabled={!chapters.some((chapter) => chapter.position === (selectedChapter?.position ?? -1) + 1)}>Chương tiếp</button>
            </div>
          </div>
          <article ref={readerContentRef} className="reader-content">
            {chapterDetail.content.split(/\n\s*\n/).map((paragraph, index) => (
              <p key={index}>{paragraph}</p>
            ))}
          </article>
          <div className="reader-footer">
            <span>{readerSaving ? "Đang lưu…" : `Đã lưu ${Number(readingProgress?.progress_percent ?? 0).toFixed(0)}%`}</span>
            <input
              type="range"
              min="0"
              max="100"
              step="1"
              value={Number(readingProgress?.progress_percent ?? 0)}
              onChange={(event) => setReadingProgress((current) => current ? { ...current, progress_percent: Number(event.target.value) } : { chapter_id: chapterDetail.id, position_seconds: 0, progress_percent: Number(event.target.value) })}
              onMouseUp={(event) => void saveReaderProgress(Number((event.target as HTMLInputElement).value))}
              onTouchEnd={(event) => void saveReaderProgress(Number((event.target as HTMLInputElement).value))}
            />
          </div>
        </section>
      )}

      <section id="audio" className="section audio-section">
        <div className="section-title"><div><p className="eyebrow">AUDIOBOOK</p><h2>{selectedChapter?.title || "Nghe sách"}</h2></div></div>
        {loadingAudio ? (
          <div className="loading"><Loader2 className="spin" size={20} /> {ttsStatus || "Đang kiểm tra audio cache…"}</div>
        ) : (
          <AudioPlayer items={audio} title={selectedChapter?.title || "Chọn một chương để bắt đầu"} />
        )}
        {ttsStatus && !loadingAudio && <p className="tts-status">{ttsStatus}</p>}
      </section>
    </main>
  );
}
