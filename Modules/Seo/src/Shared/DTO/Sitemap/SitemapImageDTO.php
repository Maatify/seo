<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\DTO\Sitemap;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class SitemapImageDTO implements \JsonSerializable
{
    public string $loc;
    public ?string $title;
    public ?string $caption;
    public ?string $geoLocation;
    public ?string $license;

    public function __construct(
        string $loc,
        ?string $title = null,
        ?string $caption = null,
        ?string $geoLocation = null,
        ?string $license = null,
    ) {
        $normalizedLoc = trim($loc);
        if ($normalizedLoc === '') {
            throw SeoInvalidArgumentException::emptyField('loc');
        }
        if (filter_var($normalizedLoc, FILTER_VALIDATE_URL) === false) {
            throw SeoInvalidArgumentException::invalidUrl($normalizedLoc);
        }

        $normalizedLicense = self::nullableNonEmptyString($license);
        if ($normalizedLicense !== null && filter_var($normalizedLicense, FILTER_VALIDATE_URL) === false) {
            throw SeoInvalidArgumentException::invalidUrl($normalizedLicense);
        }

        $this->loc = $normalizedLoc;
        $this->title = self::nullableNonEmptyString($title);
        $this->caption = self::nullableNonEmptyString($caption);
        $this->geoLocation = self::nullableNonEmptyString($geoLocation);
        $this->license = $normalizedLicense;
    }

    /**
     * @return array{loc: string, title: ?string, caption: ?string, geoLocation: ?string, license: ?string}
     */
    public function jsonSerialize(): array
    {
        return [
            'loc' => $this->loc,
            'title' => $this->title,
            'caption' => $this->caption,
            'geoLocation' => $this->geoLocation,
            'license' => $this->license,
        ];
    }

    private static function nullableNonEmptyString(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return trim($value);
    }
}
