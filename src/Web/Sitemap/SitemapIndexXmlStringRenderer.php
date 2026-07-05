<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Sitemap;

use Maatify\Seo\Exception\SeoInvalidArgumentException;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapUrlDTO;
use Maatify\Seo\Web\Sitemap\DTO\SitemapIndexEntryDTO;
use XMLWriter;

final readonly class SitemapIndexXmlStringRenderer
{
    /**
     * @param array<mixed> $sitemaps
     */
    public function renderIndex(array $sitemaps): string
    {
        $writer = $this->createWriter();
        $writer->startElement('sitemapindex');
        $writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        foreach ($sitemaps as $sitemap) {
            $this->writeEntry($writer, $this->normalizeEntry($sitemap));
        }

        $writer->endElement();
        $writer->endDocument();

        return $this->flushWriter($writer);
    }

    public function renderEntry(mixed $sitemap): string
    {
        $writer = $this->createWriter();
        $this->writeEntry($writer, $this->normalizeEntry($sitemap));
        $writer->endDocument();

        return $this->flushWriter($writer);
    }

    /**
     * @return array{loc: string, lastmod: ?string}
     */
    private function normalizeEntry(mixed $sitemap): array
    {
        if ($sitemap instanceof SitemapIndexEntryDTO) {
            return [
                'loc' => trim($sitemap->loc),
                'lastmod' => $sitemap->lastmod === null ? null : trim($sitemap->lastmod),
            ];
        }

        if (!is_array($sitemap)) {
            throw SeoInvalidArgumentException::emptyField('sitemap');
        }

        if (array_is_list($sitemap)) {
            throw SeoInvalidArgumentException::emptyField('sitemap');
        }

        $loc = $sitemap['loc'] ?? null;
        if (!is_scalar($loc) || trim((string) $loc) === '') {
            throw SeoInvalidArgumentException::emptyField('loc');
        }

        $normalizedLoc = trim((string) $loc);
        if (filter_var($normalizedLoc, FILTER_VALIDATE_URL) === false) {
            throw SeoInvalidArgumentException::invalidUrl($normalizedLoc);
        }

        $lastmod = $this->nullableArrayString($sitemap, 'lastmod');
        if ($lastmod !== null && !SitemapUrlDTO::isValidLastmod($lastmod)) {
            throw SeoInvalidArgumentException::emptyField('lastmod');
        }

        return [
            'loc' => $normalizedLoc,
            'lastmod' => $lastmod,
        ];
    }

    /**
     * @param array<mixed> $sitemap
     */
    private function nullableArrayString(array $sitemap, string $field): ?string
    {
        if (!array_key_exists($field, $sitemap) || $sitemap[$field] === null) {
            return null;
        }

        if (!is_scalar($sitemap[$field])) {
            throw SeoInvalidArgumentException::emptyField($field);
        }

        $value = trim((string) $sitemap[$field]);
        if ($value === '') {
            return null;
        }

        return $value;
    }

    /**
     * @param array{loc: string, lastmod: ?string} $sitemap
     */
    private function writeEntry(XMLWriter $writer, array $sitemap): void
    {
        $writer->startElement('sitemap');
        $writer->writeElement('loc', $sitemap['loc']);

        if ($sitemap['lastmod'] !== null) {
            $writer->writeElement('lastmod', $sitemap['lastmod']);
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
