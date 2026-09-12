<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Hreflang;

use Maatify\Seo\Exception\SeoInvalidArgumentException;
use Maatify\Seo\Shared\Service\Internal\HreflangTagNormalizer;

final readonly class HreflangLinkDTO implements \JsonSerializable
{
    public string $hreflang;
    public string $url;

    public function __construct(string $hreflang, string $url)
    {
        $normalizedHreflang = self::normalizeHreflang($hreflang);
        $normalizedUrl = trim($url);

        if ($normalizedHreflang === '') {
            throw SeoInvalidArgumentException::emptyField('hreflang');
        }

        if ($normalizedUrl === '') {
            throw SeoInvalidArgumentException::emptyField('url');
        }

        if (filter_var($normalizedUrl, FILTER_VALIDATE_URL) === false) {
            throw SeoInvalidArgumentException::invalidUrl($normalizedUrl);
        }

        $this->hreflang = $normalizedHreflang;
        $this->url = $normalizedUrl;
    }

    public static function normalizeHreflang(string $hreflang): string
    {
        return HreflangTagNormalizer::normalize($hreflang);
    }

    /**
     * @return array{hreflang: string, url: string}
     */
    public function toArray(): array
    {
        return [
            'hreflang' => $this->hreflang,
            'url' => $this->url,
        ];
    }

    /**
     * @return array{hreflang: string, url: string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
