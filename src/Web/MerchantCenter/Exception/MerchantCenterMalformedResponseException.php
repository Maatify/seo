<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\MerchantCenter\Exception;

final class MerchantCenterMalformedResponseException extends MerchantCenterException
{
    public static function forPath(string $path, string $reason): self
    {
        return new self("Merchant Center response field [{$path}] is malformed: {$reason}.");
    }
}
