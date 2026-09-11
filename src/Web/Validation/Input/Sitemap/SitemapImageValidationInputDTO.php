<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\Input\Sitemap;

final readonly class SitemapImageValidationInputDTO
{
    public function __construct(
        public ?string $loc = null,
        public ?string $title = null,
        public ?string $caption = null,
        public ?string $geoLocation = null,
        public ?string $license = null,
    ) {
    }
}
