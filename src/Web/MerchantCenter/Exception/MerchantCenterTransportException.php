<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\MerchantCenter\Exception;

final class MerchantCenterTransportException extends MerchantCenterException
{
    public function __construct(
        string $message,
        public readonly ?int $httpStatus = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function forHttpStatus(int $httpStatus): self
    {
        return new self(
            "Merchant Center provider request failed with HTTP status [{$httpStatus}].",
            $httpStatus,
        );
    }
}
