<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\Input\Sitemap;

final readonly class SitemapVideoValidationInputDTO
{
    public function __construct(
        public ?string $thumbnailLoc = null,
        public ?string $title = null,
        public ?string $description = null,
        public ?string $contentLoc = null,
        public ?string $playerLoc = null,
        public ?int $duration = null,
        public ?string $publicationDate = null,
    ) {
    }
}
