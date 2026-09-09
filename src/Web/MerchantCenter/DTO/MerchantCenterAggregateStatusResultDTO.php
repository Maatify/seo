<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\MerchantCenter\DTO;

final readonly class MerchantCenterAggregateStatusResultDTO
{
    /**
     * @param list<MerchantCenterAggregateIssueDTO> $itemLevelIssues
     */
    public function __construct(
        public string $name,
        public string $reportingContext,
        public string $country,
        public ?MerchantCenterAggregateStatisticsDTO $stats,
        public array $itemLevelIssues,
    ) {
    }
}
