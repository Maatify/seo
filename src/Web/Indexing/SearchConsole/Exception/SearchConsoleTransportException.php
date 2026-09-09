<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Indexing\SearchConsole\Exception;

final class SearchConsoleTransportException extends SearchConsoleException
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
            "Search Console provider request failed with HTTP status [{$httpStatus}].",
            $httpStatus,
        );
    }
}
