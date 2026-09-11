<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Sitemap;

use Maatify\Seo\Shared\Service\Internal\SitemapCanonicalXmlWriter;
use Maatify\Seo\Web\Sitemap\DTO\SitemapIndexEntryDTO;

final readonly class SitemapIndexXmlStringRenderer
{
    /**
     * @param array<mixed> $sitemaps
     */
    public function renderIndex(array $sitemaps): string
    {
        $canonicalEntries = [];
        foreach ($sitemaps as $sitemap) {
            $canonicalEntries[] = $this->toCanonicalEntry($sitemap);
        }

        return (new SitemapCanonicalXmlWriter())->renderIndex($canonicalEntries);
    }

    public function renderEntry(mixed $sitemap): string
    {
        return (new SitemapCanonicalXmlWriter())->renderIndexEntry($this->toCanonicalEntry($sitemap));
    }

    /** @return mixed */
    private function toCanonicalEntry(mixed $sitemap): mixed
    {
        if (!$sitemap instanceof SitemapIndexEntryDTO) {
            return $sitemap;
        }

        return [
            'loc' => $sitemap->loc,
            'lastmod' => $sitemap->lastmod,
        ];
    }
}
