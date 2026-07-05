<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\DTO\Sitemap;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class SitemapNewsDTO implements \JsonSerializable
{
    public string $publicationName;
    public string $publicationLanguage;
    public string $publicationDate;
    public string $title;
    public ?string $access;
    public ?string $genres;
    public ?string $keywords;
    public ?string $stockTickers;

    public function __construct(
        string $publicationName,
        string $publicationLanguage,
        string $publicationDate,
        string $title,
        ?string $access = null,
        ?string $genres = null,
        ?string $keywords = null,
        ?string $stockTickers = null,
    ) {
        $normalizedPublicationName = trim($publicationName);
        if ($normalizedPublicationName === '') {
            throw SeoInvalidArgumentException::emptyField('publicationName');
        }

        $normalizedPublicationLanguage = trim($publicationLanguage);
        if ($normalizedPublicationLanguage === '') {
            throw SeoInvalidArgumentException::emptyField('publicationLanguage');
        }

        $normalizedPublicationDate = trim($publicationDate);
        if ($normalizedPublicationDate === '') {
            throw SeoInvalidArgumentException::emptyField('publicationDate');
        }

        $normalizedTitle = trim($title);
        if ($normalizedTitle === '') {
            throw SeoInvalidArgumentException::emptyField('title');
        }

        $this->publicationName = $normalizedPublicationName;
        $this->publicationLanguage = $normalizedPublicationLanguage;
        $this->publicationDate = $normalizedPublicationDate;
        $this->title = $normalizedTitle;
        $this->access = self::nullableNonEmptyString($access);
        $this->genres = self::nullableNonEmptyString($genres);
        $this->keywords = self::nullableNonEmptyString($keywords);
        $this->stockTickers = self::nullableNonEmptyString($stockTickers);
    }

    /**
     * @return array{publicationName: string, publicationLanguage: string, publicationDate: string, title: string, access: ?string, genres: ?string, keywords: ?string, stockTickers: ?string}
     */
    public function jsonSerialize(): array
    {
        return [
            'publicationName' => $this->publicationName,
            'publicationLanguage' => $this->publicationLanguage,
            'publicationDate' => $this->publicationDate,
            'title' => $this->title,
            'access' => $this->access,
            'genres' => $this->genres,
            'keywords' => $this->keywords,
            'stockTickers' => $this->stockTickers,
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
