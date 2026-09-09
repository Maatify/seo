<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\MerchantCenter\DTO;

final readonly class MerchantCenterItemIssueDTO
{
    /**
     * @param list<string> $applicableCountries
     */
    public function __construct(
        public ?string $code,
        public ?string $severity,
        public ?string $resolution,
        public ?string $attribute,
        public ?string $reportingContext,
        public ?string $description,
        public ?string $detail,
        public ?string $documentation,
        public array $applicableCountries,
    ) {
    }
}
