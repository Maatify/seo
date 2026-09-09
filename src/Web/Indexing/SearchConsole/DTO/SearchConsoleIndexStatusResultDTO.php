<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Indexing\SearchConsole\DTO;

final readonly class SearchConsoleIndexStatusResultDTO implements \JsonSerializable
{
    public function __construct(
        public ?string $verdict,
        public ?string $coverageState,
        public ?string $robotsTxtState,
        public ?string $indexingState,
        public ?string $lastCrawlTime,
        public ?string $pageFetchState,
        public ?string $googleCanonical,
        public ?string $userCanonical,
        public ?string $crawledAs,
    ) {
    }

    /** @return array<string, ?string> */
    public function jsonSerialize(): array
    {
        return [
            'verdict' => $this->verdict,
            'coverage_state' => $this->coverageState,
            'robots_txt_state' => $this->robotsTxtState,
            'indexing_state' => $this->indexingState,
            'last_crawl_time' => $this->lastCrawlTime,
            'page_fetch_state' => $this->pageFetchState,
            'google_canonical' => $this->googleCanonical,
            'user_canonical' => $this->userCanonical,
            'crawled_as' => $this->crawledAs,
        ];
    }
}
