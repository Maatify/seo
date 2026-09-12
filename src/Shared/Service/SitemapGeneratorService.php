<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\Service;

use Maatify\Seo\Exception\SeoInvalidArgumentException;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapGenerationResultDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapIndexEntryDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapUrlDTO;
use Maatify\Seo\Shared\Service\Internal\SitemapCanonicalXmlWriter;

final readonly class SitemapGeneratorService
{
    /**
     * @param array<mixed> $urls
     */
    public function generateUrlSitemap(array $urls): SitemapGenerationResultDTO
    {
        $validUrls = $this->validateUrls($urls);

        $xml = (new SitemapCanonicalXmlWriter())->renderUrlSet($validUrls);

        return new SitemapGenerationResultDTO($xml, count($validUrls), 'urlset');
    }

    /**
     * @param array<mixed> $entries
     */
    public function generateSitemapIndex(array $entries): SitemapGenerationResultDTO
    {
        $validEntries = $this->validateEntries($entries);

        $xml = (new SitemapCanonicalXmlWriter())->renderSharedIndex($validEntries);

        return new SitemapGenerationResultDTO($xml, count($validEntries), 'sitemapindex');
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

}
