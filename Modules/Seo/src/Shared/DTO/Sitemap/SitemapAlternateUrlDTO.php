<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\DTO\Sitemap;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class SitemapAlternateUrlDTO implements \JsonSerializable
{
    public function __construct(
        public string $hreflang,
        public string $url,
    ) {
        if (trim($this->hreflang) === '') {
            throw SeoInvalidArgumentException::emptyField('hreflang');
        }
        if (!self::isValidHreflang($this->hreflang)) {
            throw SeoInvalidArgumentException::emptyField('hreflang');
        }
        if (trim($this->url) === '') {
            throw SeoInvalidArgumentException::emptyField('url');
        }
        if (filter_var($this->url, FILTER_VALIDATE_URL) === false) {
            throw SeoInvalidArgumentException::emptyField('url');
        }
    }

    /**
     * @return array{hreflang: string, url: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'hreflang' => strtolower(trim($this->hreflang)),
            'url' => trim($this->url),
        ];
    }

    private static function isValidHreflang(string $hreflang): bool
    {
        $value = strtolower(trim($hreflang));

        return $value === 'x-default' || preg_match('/^[a-z]{2,3}(?:-[a-z0-9]{2,8})*$/', $value) === 1;
    }
}
