<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Indexing\SearchConsole\Exception;

use Maatify\Seo\Exception\SeoExceptionInterface;

abstract class SearchConsoleException extends \RuntimeException implements SeoExceptionInterface
{
}
