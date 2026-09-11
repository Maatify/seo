<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Sitemap;

use Maatify\Seo\Web\Sitemap\Internal\SitemapCanonicalXmlWriter;

final readonly class SitemapIndexXmlStringRenderer
{
    /**
     * @param array<mixed> $sitemaps
     */
    public function renderIndex(array $sitemaps): string
    {
        return (new SitemapCanonicalXmlWriter())->renderWebIndex($sitemaps);
    }

    public function renderEntry(mixed $sitemap): string
    {
        return (new SitemapCanonicalXmlWriter())->renderWebIndexEntry($sitemap);
    }
}
