<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Indexing\SearchConsole\DTO;

final readonly class SearchConsoleTransportResponseDTO
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
