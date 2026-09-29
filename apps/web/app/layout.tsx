import type { Metadata } from "next";
import "./globals.css";
export const metadata: Metadata = { title: "Đọc Sách", description: "Thư viện sách cá nhân và audiobook" };
export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) { return <html lang="vi"><body>{children}</body></html>; }
