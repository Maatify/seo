<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\MerchantCenter\DTO;

final readonly class MerchantCenterDestinationStatusDTO
{
    /**
     * @param list<string> $approvedCountries
     * @param list<string> $pendingCountries
     * @param list<string> $disapprovedCountries
     */
    public function __construct(
        public string $reportingContext,
        public array $approvedCountries,
        public array $pendingCountries,
        public array $disapprovedCountries,
    ) {
    }
}
