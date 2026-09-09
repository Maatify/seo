<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Indexing\SearchConsole\DTO;

final readonly class SearchConsoleRichResultItemDTO implements \JsonSerializable
{
    /**
     * @param list<SearchConsoleRichResultIssueDTO> $issues
     */
    public function __construct(
        public ?string $name,
        public array $issues,
    ) {
    }

    /** @return array{name: ?string, issues: list<array{issue_message: string, severity: string}>} */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'issues' => array_map(
                static fn (SearchConsoleRichResultIssueDTO $issue): array => $issue->jsonSerialize(),
                $this->issues,
            ),
        ];
    }
}
