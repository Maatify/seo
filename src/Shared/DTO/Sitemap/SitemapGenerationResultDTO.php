<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\DTO\Sitemap;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class SitemapGenerationResultDTO implements \JsonSerializable
{
    public function __construct(
        public string $xml,
        public int $entryCount,
        public string $type,
    ) {
        if (trim($this->xml) === '') {
            throw SeoInvalidArgumentException::emptyField('xml');
        }
        if ($this->entryCount < 1) {
            throw SeoInvalidArgumentException::invalidId('entryCount');
        }
        if ($this->type !== 'urlset' && $this->type !== 'sitemapindex') {
            throw SeoInvalidArgumentException::emptyField('type');
        }
    }

    /**
     * @return array{xml: string, entry_count: int, type: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'xml' => $this->xml,
            'entry_count' => $this->entryCount,
            'type' => $this->type,
        ];
    }
}
