<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Indexing\SearchConsole\Exception;

final class SearchConsoleInvalidRequestException extends SearchConsoleException
{
    public static function forField(string $field, string $reason): self
    {
        return new self("Search Console request field [{$field}] is invalid: {$reason}.");
    }
}
