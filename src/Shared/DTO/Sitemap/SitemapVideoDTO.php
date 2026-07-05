<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\DTO\Sitemap;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class SitemapVideoDTO implements \JsonSerializable
{
    public string $thumbnailLoc;
    public string $title;
    public string $description;
    public ?string $contentLoc;
    public ?string $playerLoc;
    public ?int $duration;
    public ?string $publicationDate;

    public function __construct(
        string $thumbnailLoc,
        string $title,
        string $description,
        ?string $contentLoc = null,
        ?string $playerLoc = null,
        ?int $duration = null,
        ?string $publicationDate = null,
    ) {
        $normalizedThumbnailLoc = trim($thumbnailLoc);
        if ($normalizedThumbnailLoc === '') {
            throw SeoInvalidArgumentException::emptyField('thumbnailLoc');
        }
        if (filter_var($normalizedThumbnailLoc, FILTER_VALIDATE_URL) === false) {
            throw SeoInvalidArgumentException::invalidUrl($normalizedThumbnailLoc);
        }

        $normalizedTitle = trim($title);
        if ($normalizedTitle === '') {
            throw SeoInvalidArgumentException::emptyField('title');
        }

        $normalizedDescription = trim($description);
        if ($normalizedDescription === '') {
            throw SeoInvalidArgumentException::emptyField('description');
        }

        $normalizedContentLoc = self::nullableNonEmptyString($contentLoc);
        if ($normalizedContentLoc !== null && filter_var($normalizedContentLoc, FILTER_VALIDATE_URL) === false) {
            throw SeoInvalidArgumentException::invalidUrl($normalizedContentLoc);
        }

        $normalizedPlayerLoc = self::nullableNonEmptyString($playerLoc);
        if ($normalizedPlayerLoc !== null && filter_var($normalizedPlayerLoc, FILTER_VALIDATE_URL) === false) {
            throw SeoInvalidArgumentException::invalidUrl($normalizedPlayerLoc);
        }

        if ($normalizedContentLoc === null && $normalizedPlayerLoc === null) {
            throw SeoInvalidArgumentException::emptyField('contentLoc');
        }

        if ($duration !== null && $duration <= 0) {
            throw SeoInvalidArgumentException::invalidValue('duration', 'must be greater than 0.');
        }

        $normalizedPublicationDate = self::nullableNonEmptyString($publicationDate);
        if ($normalizedPublicationDate !== null && !SitemapUrlDTO::isValidLastmod($normalizedPublicationDate)) {
            throw SeoInvalidArgumentException::emptyField('publicationDate');
        }

        $this->thumbnailLoc = $normalizedThumbnailLoc;
        $this->title = $normalizedTitle;
        $this->description = $normalizedDescription;
        $this->contentLoc = $normalizedContentLoc;
        $this->playerLoc = $normalizedPlayerLoc;
        $this->duration = $duration;
        $this->publicationDate = $normalizedPublicationDate;
    }

    /**
     * @return array{thumbnailLoc: string, title: string, description: string, contentLoc: ?string, playerLoc: ?string, duration: ?int, publicationDate: ?string}
     */
    public function jsonSerialize(): array
    {
        return [
            'thumbnailLoc' => $this->thumbnailLoc,
            'title' => $this->title,
            'description' => $this->description,
            'contentLoc' => $this->contentLoc,
            'playerLoc' => $this->playerLoc,
            'duration' => $this->duration,
            'publicationDate' => $this->publicationDate,
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
