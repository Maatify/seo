<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Sitemap;

use Maatify\Seo\Exception\SeoInvalidArgumentException;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapUrlDTO;
use XMLWriter;

final readonly class SitemapXmlStringRenderer
{
    /**
     * @param array<mixed> $urls
     */
    public function renderUrlSet(array $urls): string
    {
        $writer = $this->createWriter();
        $writer->startElement('urlset');
        $writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        foreach ($urls as $url) {
            $this->writeUrlEntry($writer, $this->normalizeUrlEntry($url));
        }

        $writer->endElement();
        $writer->endDocument();

        return $this->flushWriter($writer);
    }

    public function renderUrlEntry(mixed $url): string
    {
        $writer = $this->createWriter();
        $this->writeUrlEntry($writer, $this->normalizeUrlEntry($url));
        $writer->endDocument();

        return $this->flushWriter($writer);
    }

    /**
     * @return array{loc: string, lastmod: ?string, changefreq: ?string, priority: ?float}
     */
    private function normalizeUrlEntry(mixed $url): array
    {
        if ($url instanceof SitemapUrlDTO) {
            return [
                'loc' => trim($url->loc),
                'lastmod' => $this->nullableNonEmptyString($url->lastmod),
                'changefreq' => $this->nullableNonEmptyString($url->changefreq),
                'priority' => $url->priority,
            ];
        }

        if (!is_array($url)) {
            throw SeoInvalidArgumentException::emptyField('url');
        }

        $loc = $url['loc'] ?? null;
        if (!is_scalar($loc) || trim((string) $loc) === '') {
            throw SeoInvalidArgumentException::emptyField('loc');
        }

        return [
            'loc' => trim((string) $loc),
            'lastmod' => $this->nullableArrayString($url, 'lastmod'),
            'changefreq' => $this->nullableArrayString($url, 'changefreq'),
            'priority' => $this->nullableArrayFloat($url, 'priority'),
        ];
    }

    /**
     * @param array<mixed> $url
     */
    private function nullableArrayString(array $url, string $field): ?string
    {
        if (!array_key_exists($field, $url) || $url[$field] === null) {
            return null;
        }

        if (!is_scalar($url[$field])) {
            throw SeoInvalidArgumentException::emptyField($field);
        }

        return $this->nullableNonEmptyString((string) $url[$field]);
    }

    /**
     * @param array<mixed> $url
     */
    private function nullableArrayFloat(array $url, string $field): ?float
    {
        if (!array_key_exists($field, $url) || $url[$field] === null || $url[$field] === '') {
            return null;
        }

        if (!is_int($url[$field]) && !is_float($url[$field]) && !is_string($url[$field])) {
            throw SeoInvalidArgumentException::emptyField($field);
        }

        if (!is_numeric($url[$field])) {
            throw SeoInvalidArgumentException::emptyField($field);
        }

        return (float) $url[$field];
    }

    private function nullableNonEmptyString(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return trim($value);
    }

    /**
     * @param array{loc: string, lastmod: ?string, changefreq: ?string, priority: ?float} $url
     */
    private function writeUrlEntry(XMLWriter $writer, array $url): void
    {
        $writer->startElement('url');
        $writer->writeElement('loc', $url['loc']);

        if ($url['lastmod'] !== null) {
            $writer->writeElement('lastmod', $url['lastmod']);
        }
        if ($url['changefreq'] !== null) {
            $writer->writeElement('changefreq', $url['changefreq']);
        }
        if ($url['priority'] !== null) {
            $writer->writeElement('priority', number_format($url['priority'], 1, '.', ''));
        }

        $writer->endElement();
    }

    private function createWriter(): XMLWriter
    {
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->startDocument('1.0', 'UTF-8');

        return $writer;
    }

    private function flushWriter(XMLWriter $writer): string
    {
        $xml = $writer->outputMemory();
        if ($xml === '') {
            throw SeoInvalidArgumentException::emptyField('xml');
        }

        return $xml;
    }
}
