<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\MerchantCenter\DTO;

final readonly class MerchantCenterProductRequestDTO
{
    public function __construct(
        public string $name,
    ) {
    }
}
