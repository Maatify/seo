<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Indexing\SearchConsole\DTO;

final readonly class SearchConsoleRichResultsResultDTO implements \JsonSerializable
{
    /**
     * @param list<SearchConsoleDetectedItemDTO> $detectedItems
     */
    public function __construct(
        public string $verdict,
        public array $detectedItems,
    ) {
    }

    /** @return array{verdict: string, detected_items: list<array<string, mixed>>} */
    public function jsonSerialize(): array
    {
        return [
            'verdict' => $this->verdict,
            'detected_items' => array_map(
                static fn (SearchConsoleDetectedItemDTO $item): array => $item->jsonSerialize(),
                $this->detectedItems,
            ),
        ];
    }
}
