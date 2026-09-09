<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Indexing\SearchConsole\DTO;

final readonly class SearchConsoleRichResultIssueDTO implements \JsonSerializable
{
    public function __construct(
        public string $issueMessage,
        public string $severity,
    ) {
    }

    /** @return array{issue_message: string, severity: string} */
    public function jsonSerialize(): array
    {
        return [
            'issue_message' => $this->issueMessage,
            'severity' => $this->severity,
        ];
    }
}
