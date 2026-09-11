<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\Input\Sitemap;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class SitemapValidationDocumentDTO
{
    /**
     * @param list<SitemapUrlValidationInputDTO>|list<SitemapIndexEntryValidationInputDTO> $entries
     * @phpstan-param array<int|string, mixed> $entries
     */
    public function __construct(
        public string $type,
        public array $entries,
        public ?SitemapValidationLocationDTO $location = null,
        public ?int $uncompressedSizeBytes = null,
    ) {
        if (!in_array($this->type, ['urlset', 'sitemapindex'], true)) {
            throw SeoInvalidArgumentException::invalidValue('type', 'Expected urlset or sitemapindex.');
        }

        if (!array_is_list($this->entries)) {
            throw SeoInvalidArgumentException::invalidValue('entries', 'Expected a list of homogeneous sitemap candidate entries.');
        }

        foreach ($this->entries as $index => $entry) {
            $validEntry = $this->type === 'urlset'
                ? $entry instanceof SitemapUrlValidationInputDTO
                : $entry instanceof SitemapIndexEntryValidationInputDTO;

            if (!$validEntry) {
                throw SeoInvalidArgumentException::invalidValue("entries.{$index}", 'Entry type does not match the document type.');
            }
        }

        if ($this->uncompressedSizeBytes !== null && $this->uncompressedSizeBytes < 0) {
            throw SeoInvalidArgumentException::invalidValue('uncompressedSizeBytes', 'Expected null or a non-negative integer.');
        }
    }
}
