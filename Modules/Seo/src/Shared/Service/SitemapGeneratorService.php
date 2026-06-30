<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\Service;

use Maatify\Seo\Exception\SeoInvalidArgumentException;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapGenerationResultDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapIndexEntryDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapUrlDTO;
use XMLWriter;

final readonly class SitemapGeneratorService
{
    /**
     * @param array<mixed> $urls
     */
    public function generateUrlSitemap(array $urls): SitemapGenerationResultDTO
    {
        $validUrls = $this->validateUrls($urls);

        $writer = $this->createWriter();
        $writer->startElement('urlset');
        $writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        if ($this->containsAlternates($validUrls)) {
            $writer->writeAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');
        }

        foreach ($validUrls as $url) {
            $this->writeUrl($writer, $url);
        }

        $writer->endElement();
        $writer->endDocument();

        return new SitemapGenerationResultDTO($this->flushWriter($writer), count($validUrls), 'urlset');
    }

    /**
     * @param array<mixed> $entries
     */
    public function generateSitemapIndex(array $entries): SitemapGenerationResultDTO
    {
        $validEntries = $this->validateEntries($entries);

        $writer = $this->createWriter();
        $writer->startElement('sitemapindex');
        $writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        foreach ($validEntries as $entry) {
            $this->writeIndexEntry($writer, $entry);
        }

        $writer->endElement();
        $writer->endDocument();

        return new SitemapGenerationResultDTO($this->flushWriter($writer), count($validEntries), 'sitemapindex');
    }


    /**
     * @param array<mixed> $urls
     * @return list<SitemapUrlDTO>
     */
    private function validateUrls(array $urls): array
    {
        if ($urls === []) {
            throw SeoInvalidArgumentException::emptyField('urls');
        }

        $validUrls = [];
        foreach ($urls as $url) {
            if (!$url instanceof SitemapUrlDTO) {
                throw SeoInvalidArgumentException::emptyField('urls');
            }

            $validUrls[] = $url;
        }

        return $validUrls;
    }

    /**
     * @param array<mixed> $entries
     * @return list<SitemapIndexEntryDTO>
     */
    private function validateEntries(array $entries): array
    {
        if ($entries === []) {
            throw SeoInvalidArgumentException::emptyField('entries');
        }

        $validEntries = [];
        foreach ($entries as $entry) {
            if (!$entry instanceof SitemapIndexEntryDTO) {
                throw SeoInvalidArgumentException::emptyField('entries');
            }

            $validEntries[] = $entry;
        }

        return $validEntries;
    }

    /**
     * @param list<SitemapUrlDTO> $urls
     */
    private function containsAlternates(array $urls): bool
    {
        foreach ($urls as $url) {
            if ($url->alternates !== []) {
                return true;
            }
        }

        return false;
    }

    private function writeUrl(XMLWriter $writer, SitemapUrlDTO $url): void
    {
        $writer->startElement('url');
        $writer->writeElement('loc', trim($url->loc));

        if ($url->lastmod !== null) {
            $writer->writeElement('lastmod', trim($url->lastmod));
        }
        if ($url->changefreq !== null) {
            $writer->writeElement('changefreq', $url->changefreq);
        }
        if ($url->priority !== null) {
            $writer->writeElement('priority', number_format($url->priority, 1, '.', ''));
        }

        foreach ($url->alternates as $alternate) {
            $writer->startElement('xhtml:link');
            $writer->writeAttribute('rel', 'alternate');
            $writer->writeAttribute('hreflang', strtolower(trim($alternate->hreflang)));
            $writer->writeAttribute('href', trim($alternate->url));
            $writer->endElement();
        }

        $writer->endElement();
    }

    private function writeIndexEntry(XMLWriter $writer, SitemapIndexEntryDTO $entry): void
    {
        $writer->startElement('sitemap');
        $writer->writeElement('loc', trim($entry->loc));

        if ($entry->lastmod !== null) {
            $writer->writeElement('lastmod', trim($entry->lastmod));
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
