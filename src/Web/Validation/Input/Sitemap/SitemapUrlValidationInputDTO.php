<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\Input\Sitemap;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class SitemapUrlValidationInputDTO
{
    /**
     * @param list<SitemapImageValidationInputDTO> $images
     * @param list<SitemapVideoValidationInputDTO> $videos
     * @param list<SitemapNewsValidationInputDTO> $news
     */
    public function __construct(
        public ?string $loc = null,
        public ?string $lastmod = null,
        public ?string $changefreq = null,
        public int|float|null $priority = null,
        public array $images = [],
        public array $videos = [],
        public array $news = [],
    ) {
        self::assertChildList('images', $this->images, SitemapImageValidationInputDTO::class);
        self::assertChildList('videos', $this->videos, SitemapVideoValidationInputDTO::class);
        self::assertChildList('news', $this->news, SitemapNewsValidationInputDTO::class);
    }

    /** @param class-string $expectedClass */
    private static function assertChildList(string $field, mixed $values, string $expectedClass): void
    {
        if (!is_array($values) || !array_is_list($values)) {
            throw SeoInvalidArgumentException::invalidValue($field, 'Expected a list of candidate child DTOs.');
        }

        foreach ($values as $index => $value) {
            if (!$value instanceof $expectedClass) {
                throw SeoInvalidArgumentException::invalidValue("{$field}.{$index}", "Expected {$expectedClass}.");
            }
        }
    }
}
