<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Indexing\SearchConsole\DTO;

final readonly class SearchConsoleInspectionResultDTO implements \JsonSerializable
{
    public function __construct(
        public string $providerIdentity,
        public ?string $inspectionResultLink,
        public SearchConsoleIndexStatusResultDTO $indexStatusResult,
        public ?SearchConsoleRichResultsResultDTO $richResultsResult,
    ) {
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'provider_identity' => $this->providerIdentity,
            'inspection_result_link' => $this->inspectionResultLink,
            'index_status_result' => $this->indexStatusResult->jsonSerialize(),
            'rich_results_result' => $this->richResultsResult?->jsonSerialize(),
        ];
    }
}
