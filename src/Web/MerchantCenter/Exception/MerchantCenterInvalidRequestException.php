<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\MerchantCenter\Exception;

final class MerchantCenterInvalidRequestException extends MerchantCenterException
{
    public static function forField(string $field, string $reason): self
    {
        return new self("Merchant Center request field [{$field}] is invalid: {$reason}.");
    }
}
