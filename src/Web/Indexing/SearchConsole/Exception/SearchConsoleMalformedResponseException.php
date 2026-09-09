<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Indexing\SearchConsole\Exception;

final class SearchConsoleMalformedResponseException extends SearchConsoleException
{
    public static function forPath(string $path, string $reason): self
    {
        return new self("Search Console response field [{$path}] is malformed: {$reason}.");
    }
}
