<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\DTO\Sitemap;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class SitemapIndexEntryDTO implements \JsonSerializable
{
    public function __construct(
        public string $loc,
        public ?string $lastmod = null,
    ) {
        if (trim($this->loc) === '') {
            throw SeoInvalidArgumentException::emptyField('loc');
        }
        if (filter_var($this->loc, FILTER_VALIDATE_URL) === false) {
            throw SeoInvalidArgumentException::emptyField('loc');
        }
        if ($this->lastmod !== null && !SitemapUrlDTO::isValidLastmod($this->lastmod)) {
            throw SeoInvalidArgumentException::emptyField('lastmod');
        }
    }

    /**
     * @return array{loc: string, lastmod: ?string}
     */
    public function jsonSerialize(): array
    {
        return [
            'loc' => trim($this->loc),
            'lastmod' => $this->lastmod === null ? null : trim($this->lastmod),
        ];
    }
}
