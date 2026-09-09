<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\MerchantCenter\DTO;

final readonly class MerchantCenterAggregateStatisticsDTO
{
    public function __construct(
        public string $activeCount,
        public string $pendingCount,
        public string $disapprovedCount,
        public string $expiringCount,
    ) {
    }
}
