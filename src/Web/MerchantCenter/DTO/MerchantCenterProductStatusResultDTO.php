<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\MerchantCenter\DTO;

final readonly class MerchantCenterProductStatusResultDTO
{
    /**
     * @param list<MerchantCenterDestinationStatusDTO> $destinationStatuses
     * @param list<MerchantCenterItemIssueDTO> $itemLevelIssues
     */
    public function __construct(
        public string $productName,
        public array $destinationStatuses,
        public array $itemLevelIssues,
        public ?string $creationDate,
        public ?string $lastUpdateDate,
        public ?string $googleExpirationDate,
    ) {
    }
}
