<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\MerchantCenter\DTO;

final readonly class MerchantCenterAggregateRequestDTO
{
    public function __construct(
        public string $parent,
        public ?int $pageSize = null,
        public ?string $pageToken = null,
        public ?string $filter = null,
    ) {
    }
}
