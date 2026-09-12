<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\DTO;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class SeoDiagnosticTargetDTO implements \JsonSerializable
{
    /** @var list<string> */
    private const SCOPES = [
        'robots_document',
        'robots_rule',
        'robots_meta',
        'sitemap_document',
        'sitemap_url',
        'sitemap_index_entry',
        'sitemap_image',
        'sitemap_video',
        'sitemap_news',
        'meta',
        'canonical',
        'hreflang_page',
        'hreflang_link',
    ];

    public function __construct(
        public string $scope,
        public ?int $entryIndex = null,
        public ?int $itemIndex = null,
        public ?int $line = null,
    ) {
        if (!in_array($this->scope, self::SCOPES, true)) {
            throw SeoInvalidArgumentException::invalidValue('scope', 'Expected a supported diagnostic target scope.');
        }

        if ($this->entryIndex !== null && $this->entryIndex < 0) {
            throw SeoInvalidArgumentException::invalidValue('entryIndex', 'Expected a zero-based non-negative index.');
        }

        if ($this->itemIndex !== null && $this->itemIndex < 0) {
            throw SeoInvalidArgumentException::invalidValue('itemIndex', 'Expected a zero-based non-negative index.');
        }

        if ($this->line !== null && $this->line < 1) {
            throw SeoInvalidArgumentException::invalidValue('line', 'Expected a one-based line number.');
        }

        $validShape = match ($this->scope) {
            'robots_rule' => $this->entryIndex === null && $this->itemIndex === null && $this->line !== null,
            'sitemap_url', 'sitemap_index_entry', 'hreflang_page' => $this->entryIndex !== null && $this->itemIndex === null && $this->line === null,
            'sitemap_image', 'sitemap_video', 'sitemap_news', 'hreflang_link' => $this->entryIndex !== null && $this->itemIndex !== null && $this->line === null,
            'robots_document', 'robots_meta', 'sitemap_document', 'meta', 'canonical' => $this->entryIndex === null && $this->itemIndex === null && $this->line === null,
        };

        if (!$validShape) {
            throw SeoInvalidArgumentException::invalidValue('target', 'The indexes and line must match the selected scope.');
        }
    }

    /** @return array{scope: string, entry_index: int|null, item_index: int|null, line: int|null} */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /** @return array{scope: string, entry_index: int|null, item_index: int|null, line: int|null} */
    public function toArray(): array
    {
        return [
            'scope' => $this->scope,
            'entry_index' => $this->entryIndex,
            'item_index' => $this->itemIndex,
            'line' => $this->line,
        ];
    }
}
