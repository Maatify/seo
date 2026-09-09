<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\MerchantCenter\DTO;

final readonly class MerchantCenterAggregateIssueDTO
{
    public function __construct(
        public ?string $code,
        public ?string $severity,
        public ?string $resolution,
        public ?string $attribute,
        public ?string $description,
        public ?string $detail,
        public ?string $documentationUri,
        public ?string $productCount,
    ) {
    }
}
