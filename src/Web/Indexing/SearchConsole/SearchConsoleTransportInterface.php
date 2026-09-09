<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Indexing\SearchConsole;

use Maatify\Seo\Web\Indexing\SearchConsole\DTO\SearchConsoleInspectionRequestDTO;
use Maatify\Seo\Web\Indexing\SearchConsole\DTO\SearchConsoleTransportResponseDTO;

interface SearchConsoleTransportInterface
{
    public function inspect(SearchConsoleInspectionRequestDTO $request): SearchConsoleTransportResponseDTO;
}
