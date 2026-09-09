<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Indexing\SearchConsole\DTO;

final readonly class SearchConsoleDetectedItemDTO implements \JsonSerializable
{
    /**
     * @param list<SearchConsoleRichResultItemDTO> $items
     */
    public function __construct(
        public string $richResultType,
        public array $items,
    ) {
    }

    /** @return array{rich_result_type: string, items: list<array<string, mixed>>} */
    public function jsonSerialize(): array
    {
        return [
            'rich_result_type' => $this->richResultType,
            'items' => array_map(
                static fn (SearchConsoleRichResultItemDTO $item): array => $item->jsonSerialize(),
                $this->items,
            ),
        ];
    }
}
