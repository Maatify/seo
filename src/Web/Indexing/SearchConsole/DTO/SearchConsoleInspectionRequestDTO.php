<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Indexing\SearchConsole\DTO;

final readonly class SearchConsoleInspectionRequestDTO
{
    public function __construct(
        public string $inspectionUrl,
        public string $siteUrl,
        public ?string $languageCode = null,
    ) {
    }
}
