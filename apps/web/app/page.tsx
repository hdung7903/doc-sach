import { BookOpen, Headphones, Library, Play } from "lucide-react";

const books = [
  { title: "Sách của bạn", author: "Thư viện cá nhân", progress: 0 },
  { title: "Thêm EPUB đầu tiên", author: "EPUB → chương → audio", progress: 0 },
];

export default function Home() {
  return <main className="shell">
    <header className="topbar"><div className="brand"><BookOpen size={22}/> Đọc Sách</div><nav><a href="#library">Thư viện</a><a href="#audio">Audiobook</a></nav></header>
    <section className="hero"><div><p className="eyebrow">PERSONAL READING PLATFORM</p><h1>Đọc sách. Nghe sách.<br/>Tiếp tục ở mọi thiết bị.</h1><p className="lead">Thư viện cá nhân cho EPUB và audiobook, đồng bộ tiến độ giữa web và điện thoại.</p><div className="actions"><button><Library size={18}/> Mở thư viện</button><button className="secondary"><Headphones size={18}/> Nghe sách</button></div></div><div className="hero-card"><BookOpen size={42}/><strong>EPUB → Reader → TTS</strong><span>Pipeline sẵn sàng cho backend và worker.</span></div></section>
    <section id="library" className="section"><div className="section-title"><div><p className="eyebrow">LIBRARY</p><h2>Thư viện của bạn</h2></div><button className="secondary">+ Thêm sách</button></div><div className="grid">{books.map(book=><article className="book" key={book.title}><div className="cover"><BookOpen size={30}/></div><div><h3>{book.title}</h3><p>{book.author}</p><div className="progress"><span style={{width: book.progress + "%"}}/></div><small>{book.progress}% đã đọc</small></div><button className="icon"><Play size={17}/></button></article>)}</div></section>
    <section id="audio" className="player"><div><Headphones size={20}/><span>Audiobook player</span></div><span>Chưa có audio — TTS worker sẽ xử lý sau khi upload EPUB.</span></section>
  </main>;
}
