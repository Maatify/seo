<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\Input\Sitemap;

final readonly class SitemapIndexEntryValidationInputDTO
{
    public function __construct(
        public ?string $loc = null,
        public ?string $lastmod = null,
    ) {
    }
}
