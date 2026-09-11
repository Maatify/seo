<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\Input\Sitemap;

final readonly class SitemapNewsValidationInputDTO
{
    public function __construct(
        public ?string $publicationName = null,
        public ?string $publicationLanguage = null,
        public ?string $publicationDate = null,
        public ?string $title = null,
        public ?string $access = null,
        public ?string $genres = null,
        public ?string $keywords = null,
        public ?string $stockTickers = null,
    ) {
    }
}
