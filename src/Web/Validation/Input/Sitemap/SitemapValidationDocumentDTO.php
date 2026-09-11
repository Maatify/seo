<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\Input\Sitemap;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class SitemapValidationDocumentDTO
{
    /**
     * @param list<SitemapUrlValidationInputDTO>|list<SitemapIndexEntryValidationInputDTO> $entries
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

        self::assertEntries($this->type, $this->entries);

        if ($this->uncompressedSizeBytes !== null && $this->uncompressedSizeBytes < 0) {
            throw SeoInvalidArgumentException::invalidValue('uncompressedSizeBytes', 'Expected null or a non-negative integer.');
        }
    }

    private static function assertEntries(string $type, mixed $entries): void
    {
        if (!is_array($entries) || !array_is_list($entries)) {
            throw SeoInvalidArgumentException::invalidValue('entries', 'Expected a list of homogeneous sitemap candidate entries.');
        }

        foreach ($entries as $index => $entry) {
            $validEntry = $type === 'urlset'
                ? $entry instanceof SitemapUrlValidationInputDTO
                : $entry instanceof SitemapIndexEntryValidationInputDTO;

            if (!$validEntry) {
                throw SeoInvalidArgumentException::invalidValue("entries.{$index}", 'Entry type does not match the document type.');
            }
        }
    }
}
