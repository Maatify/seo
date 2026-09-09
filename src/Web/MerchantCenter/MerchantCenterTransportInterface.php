<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\MerchantCenter;

use Maatify\Seo\Web\MerchantCenter\DTO\MerchantCenterAggregateRequestDTO;
use Maatify\Seo\Web\MerchantCenter\DTO\MerchantCenterProductRequestDTO;
use Maatify\Seo\Web\MerchantCenter\DTO\MerchantCenterTransportResponseDTO;

interface MerchantCenterTransportInterface
{
    public function getProduct(
        MerchantCenterProductRequestDTO $request,
    ): MerchantCenterTransportResponseDTO;

    public function listAggregateProductStatuses(
        MerchantCenterAggregateRequestDTO $request,
    ): MerchantCenterTransportResponseDTO;
}
