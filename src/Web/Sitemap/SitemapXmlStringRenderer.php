<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Sitemap;

use Maatify\Seo\Web\Sitemap\Internal\SitemapCanonicalXmlWriter;

final readonly class SitemapXmlStringRenderer
{
    /**
     * @param array<mixed> $urls
     */
    public function renderUrlSet(array $urls): string
    {
        return (new SitemapCanonicalXmlWriter())->renderUrlSet($urls);
    }

    public function renderUrlEntry(mixed $url): string
    {
        return (new SitemapCanonicalXmlWriter())->renderUrlEntry($url);
    }
}
