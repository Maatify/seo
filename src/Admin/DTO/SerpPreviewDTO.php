<?php

declare(strict_types=1);

namespace Maatify\Seo\Admin\DTO;

final readonly class SerpPreviewDTO implements \JsonSerializable
{
    /** @param list<string> $warnings */
    public function __construct(
        public string $title,
        public ?string $description,
        public ?string $url,
        public ?string $displayUrl,
        public string $robots,
        public array $warnings = [],
        public ?int $score = null,
        public ?string $status = null,
    ) {
    }

    /** @return array{title:string, description:?string, url:?string, display_url:?string, robots:string, warnings:list<string>, score:?int, status:?string} */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    /** @return array{title:string, description:?string, url:?string, display_url:?string, robots:string, warnings:list<string>, score:?int, status:?string} */
    public function jsonSerialize(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'url' => $this->url,
            'display_url' => $this->displayUrl,
            'robots' => $this->robots,
            'warnings' => $this->warnings,
            'score' => $this->score,
            'status' => $this->status,
        ];
    }
}
