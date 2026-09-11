<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\MerchantCenter\DTO;

final readonly class MerchantCenterAggregateStatusListResultDTO
{
    /**
     * @param list<MerchantCenterAggregateStatusResultDTO> $statuses
     */
    public function __construct(
        public array $statuses,
        public ?string $nextPageToken,
    ) {
    }
}
