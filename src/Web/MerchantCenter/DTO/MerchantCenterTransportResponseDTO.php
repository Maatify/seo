<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\MerchantCenter\DTO;

final readonly class MerchantCenterTransportResponseDTO
{
    /**
     * @param array<string, mixed> $decodedBody
     */
    public function __construct(
        public int $httpStatus,
        public array $decodedBody,
    ) {
    }
}
