"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import {
  Pause,
  Play,
  Volume2,
  VolumeX,
  RotateCcw,
  RotateCw,
  Gauge,
} from "lucide-react";

export type AudioItem = {
  id: string;
  chunk_id: string;
  position: number;
  duration_seconds?: number | null;
  mime_type: string;
  url: string;
  voice?: string;
  speed?: number;
};

type Props = {
  items: AudioItem[];
  title?: string;
  onProgress?: (item: AudioItem, positionSeconds: number, durationSeconds: number) => void;
  storageKey?: string;
};

const STORAGE_KEY = "doc-sach:player-state";

function clamp(value: number, min: number, max: number) {
  return Math.min(max, Math.max(min, value));
}

export default function AudioPlayer({ items, title = "Audiobook", onProgress, storageKey = STORAGE_KEY }: Props) {
  const audioRef = useRef<HTMLAudioElement>(null);
  const [index, setIndex] = useState(0);
  const [playing, setPlaying] = useState(false);
  const [current, setCurrent] = useState(0);
  const [duration, setDuration] = useState(0);
  const [volume, setVolume] = useState(1);
  const [muted, setMuted] = useState(false);
  const [speed, setSpeed] = useState(1);

  const item = items[index];
  const progress = duration > 0 ? (current / duration) * 100 : 0;

  const timeLabel = useMemo(() => {
    const format = (value: number) => {
      const seconds = Math.max(0, Math.floor(value));
      const minutes = Math.floor(seconds / 60);
      const rest = seconds % 60;
      return `${minutes}:${rest.toString().padStart(2, "0")}`;
    };
    return `${format(current)} / ${format(duration)}`;
  }, [current, duration]);

  useEffect(() => {
    const saved = window.localStorage.getItem(storageKey);
    if (!saved) return;
    try {
      const state = JSON.parse(saved) as {
        index?: number;
        current?: number;
        volume?: number;
        muted?: boolean;
        speed?: number;
      };
      setIndex(clamp(Number(state.index ?? 0), 0, Math.max(0, items.length - 1)));
      setCurrent(Math.max(0, Number(state.current ?? 0)));
      setVolume(clamp(Number(state.volume ?? 1), 0, 1));
      setMuted(Boolean(state.muted));
      setSpeed(clamp(Number(state.speed ?? 1), 0.5, 2));
    } catch {
      // Ignore malformed local player state.
    }
  }, [items.length, storageKey]);

  useEffect(() => {
    const audio = audioRef.current;
    if (!audio || !item) return;

    audio.src = item.url;
    audio.load();

    const saved = window.localStorage.getItem(storageKey);
    let savedCurrent = 0;
    if (saved) {
      try {
        const state = JSON.parse(saved) as { index?: number; current?: number };
        if (Number(state.index) === index) savedCurrent = Math.max(0, Number(state.current ?? 0));
      } catch {}
    }
    audio.addEventListener("loadedmetadata", () => {
      setDuration(audio.duration || item.duration_seconds || 0);
      if (savedCurrent > 0 && savedCurrent < audio.duration) audio.currentTime = savedCurrent;
    }, { once: true });

    if (playing) {
      void audio.play().catch(() => setPlaying(false));
    }
  }, [item?.url, index, storageKey]);

  useEffect(() => {
    const audio = audioRef.current;
    if (!audio) return;
    audio.volume = volume;
    audio.muted = muted;
  }, [volume, muted]);

  useEffect(() => {
    const audio = audioRef.current;
    if (!audio) return;
    audio.playbackRate = speed;
  }, [speed]);

  useEffect(() => {
    const audio = audioRef.current;
    if (!audio) return;

    const onTime = () => {
      setCurrent(audio.currentTime);
      setDuration(audio.duration || item?.duration_seconds || 0);
      if (item) onProgress?.(item, audio.currentTime, audio.duration || duration || 0);

      window.localStorage.setItem(storageKey, JSON.stringify({
        index,
        current: audio.currentTime,
        volume,
        muted,
        speed,
      }));
    };
    const onEnded = () => {
      if (index < items.length - 1) {
        setIndex((value) => value + 1);
        setCurrent(0);
      } else {
        setPlaying(false);
        setCurrent(0);
      }
    };
    const onPlay = () => setPlaying(true);
    const onPause = () => setPlaying(false);

    audio.addEventListener("timeupdate", onTime);
    audio.addEventListener("ended", onEnded);
    audio.addEventListener("play", onPlay);
    audio.addEventListener("pause", onPause);
    return () => {
      audio.removeEventListener("timeupdate", onTime);
      audio.removeEventListener("ended", onEnded);
      audio.removeEventListener("play", onPlay);
      audio.removeEventListener("pause", onPause);
    };
  }, [index, item, items.length, onProgress, volume, muted, speed, duration, storageKey]);

  const togglePlay = () => {
    const audio = audioRef.current;
    if (!audio || !item) return;
    if (audio.paused) void audio.play().catch(() => setPlaying(false));
    else audio.pause();
  };

  const seekBy = (seconds: number) => {
    const audio = audioRef.current;
    if (!audio) return;
    audio.currentTime = clamp(audio.currentTime + seconds, 0, audio.duration || duration);
  };

  const changeVolume = (value: number) => {
    const next = clamp(value, 0, 1);
    setVolume(next);
    setMuted(next === 0);
    if (audioRef.current) {
      audioRef.current.volume = next;
      audioRef.current.muted = next === 0;
    }
  };

  if (!item) {
    return (
      <div className="audio-player empty">
        <div><Volume2 size={20} /><strong>{title}</strong></div>
        <span>Chưa có audio cache. Hãy tạo TTS cho chương trước.</span>
      </div>
    );
  }

  return (
    <div className="audio-player">
      <audio ref={audioRef} preload="auto" />
      <div className="audio-head">
        <div>
          <span className="audio-kicker">NOW PLAYING</span>
          <strong>{title}</strong>
          <small>Chunk {index + 1} / {items.length}</small>
        </div>
        <select
          aria-label="Tốc độ phát"
          value={speed}
          onChange={(event) => setSpeed(Number(event.target.value))}
        >
          {[0.5, 0.75, 1, 1.25, 1.5, 1.75, 2].map(value => (
            <option key={value} value={value}>{value}×</option>
          ))}
        </select>
      </div>

      <input
        className="seek"
        aria-label="Vị trí phát"
        type="range"
        min="0"
        max={Math.max(duration, 0)}
        step="0.1"
        value={Math.min(current, duration || 0)}
        onChange={(event) => {
          const next = Number(event.target.value);
          setCurrent(next);
          if (audioRef.current) audioRef.current.currentTime = next;
        }}
      />

      <div className="audio-row">
        <span className="time">{timeLabel}</span>
        <div className="audio-controls">
          <button className="player-btn subtle" type="button" onClick={() => seekBy(-15)} aria-label="Lùi 15 giây"><RotateCcw size={18}/></button>
          <button className="player-btn primary" type="button" onClick={togglePlay} aria-label={playing ? "Tạm dừng" : "Phát"}>
            {playing ? <Pause size={20}/> : <Play size={20}/>}
          </button>
          <button className="player-btn subtle" type="button" onClick={() => seekBy(30)} aria-label="Tiến 30 giây"><RotateCw size={18}/></button>
        </div>

        <div className="volume-control">
          <button className="player-btn subtle" type="button" onClick={() => setMuted(value => !value)} aria-label={muted ? "Bật âm lượng" : "Tắt âm lượng"}>
            {muted || volume === 0 ? <VolumeX size={18}/> : <Volume2 size={18}/>}
          </button>
          <input
            aria-label="Âm lượng"
            type="range"
            min="0"
            max="1"
            step="0.01"
            value={muted ? 0 : volume}
            onChange={(event) => changeVolume(Number(event.target.value))}
          />
          <span>{Math.round((muted ? 0 : volume) * 100)}%</span>
        </div>
      </div>
    </div>
  );
}
