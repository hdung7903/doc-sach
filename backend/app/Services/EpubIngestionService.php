<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Chapter;
use App\Models\ChapterChunk;
use DOMDocument;
use DOMXPath;
use RuntimeException;
use ZipArchive;

class EpubIngestionService
{
    public function ingest(Book $book, string $epubPath): void
    {
        $zip = new ZipArchive();
        if ($zip->open($epubPath) !== true) {
            throw new RuntimeException('Unable to open EPUB archive.');
        }

        try {
            $container = $zip->getFromName('META-INF/container.xml');
            if ($container === false) throw new RuntimeException('Invalid EPUB: container.xml is missing.');

            $containerDoc = $this->xml($container);
            $rootfile = $containerDoc->getElementsByTagName('rootfile')->item(0);
            $opfPath = $rootfile?->attributes?->getNamedItem('full-path')?->nodeValue;
            if (!$opfPath) throw new RuntimeException('Invalid EPUB: OPF package is missing.');

            $opf = $zip->getFromName($opfPath);
            if ($opf === false) throw new RuntimeException('Invalid EPUB: OPF file is missing.');

            $opfDoc = $this->xml($opf);
            $xpath = new DOMXPath($opfDoc);
            $xpath->registerNamespace('opf', 'http://www.idpf.org/2007/opf');
            $xpath->registerNamespace('dc', 'http://purl.org/dc/elements/1.1/');

            $manifest = [];
            foreach ($xpath->query('//opf:manifest/opf:item') as $item) {
                $manifest[$item->getAttribute('id')] = [
                    'href' => $item->getAttribute('href'),
                    'media_type' => $item->getAttribute('media-type'),
                ];
            }

            $base = str_contains($opfPath, '/') ? dirname($opfPath).'/' : '';
            $position = 0;
            $totalWords = 0;

            foreach ($xpath->query('//opf:spine/opf:itemref') as $itemref) {
                $id = $itemref->getAttribute('idref');
                $item = $manifest[$id] ?? null;
                if (!$item || !str_contains($item['media_type'], 'html')) continue;

                $entry = rawurldecode($base.$item['href']);
                $html = $zip->getFromName($entry);
                if ($html === false) continue;

                [$title, $text] = $this->extractText($html);
                $text = $this->cleanText($text);
                if ($text === '') continue;

                $position++;
                $wordCount = str_word_count($text);
                $totalWords += $wordCount;

                $chapter = $book->chapters()->create([
                    'title' => $title ?: 'Chương '.$position,
                    'position' => $position,
                    'content' => $text,
                    'word_count' => $wordCount,
                ]);

                foreach ($this->chunk($text) as $chunkPosition => $chunkText) {
                    $chapter->chunks()->create([
                        'position' => $chunkPosition + 1,
                        'content' => $chunkText,
                        'word_count' => str_word_count($chunkText),
                    ]);
                }
            }

            if ($position === 0) throw new RuntimeException('EPUB contains no readable chapters.');

            $book->update([
                'status' => 'ready',
                'total_chapters' => $position,
                'total_words' => $totalWords,
            ]);
        } finally {
            $zip->close();
        }
    }

    private function xml(string $content): DOMDocument
    {
        $doc = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        if (!$doc->loadXML($content, LIBXML_NONET | LIBXML_NOCDATA)) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            throw new RuntimeException('Invalid EPUB XML.');
        }
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        return $doc;
    }

    private function extractText(string $html): array
    {
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $xpath = new DOMXPath($doc);
        foreach ($xpath->query('//script|//style|//nav') as $node) {
            $node->parentNode?->removeChild($node);
        }

        $title = trim($xpath->evaluate('string((//h1|//h2|//title)[1])'));
        $text = trim($doc->textContent ?? '');

        return [$title, $text];
    }

    private function cleanText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[\t ]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\R{3,}/u', "\n\n", $text) ?? $text;
        return trim($text);
    }

    private function chunk(string $text, int $maxWords = 450): array
    {
        $paragraphs = preg_split('/\n\s*\n/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [$text];
        $chunks = [];
        $current = '';

        foreach ($paragraphs as $paragraph) {
            $candidate = trim($current === '' ? $paragraph : $current."\n\n".$paragraph);
            if (str_word_count($candidate) <= $maxWords) {
                $current = $candidate;
                continue;
            }

            if ($current !== '') $chunks[] = $current;
            $current = $paragraph;

            if (str_word_count($current) > $maxWords) {
                $sentences = preg_split('/(?<=[.!?])\s+/u', $current, -1, PREG_SPLIT_NO_EMPTY) ?: [$current];
                $current = '';
                foreach ($sentences as $sentence) {
                    $candidate = trim($current === '' ? $sentence : $current.' '.$sentence);
                    if (str_word_count($candidate) <= $maxWords) $current = $candidate;
                    else {
                        if ($current !== '') $chunks[] = $current;
                        $current = $sentence;
                    }
                }
            }
        }

        if ($current !== '') $chunks[] = $current;
        return $chunks;
    }
}
