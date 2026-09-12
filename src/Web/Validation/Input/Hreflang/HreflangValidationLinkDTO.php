<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\Input\Hreflang;

final readonly class HreflangValidationLinkDTO
{
    public function __construct(
        public ?string $hreflang = null,
        public ?string $url = null,
    ) {
    }
}
