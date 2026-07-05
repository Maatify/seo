<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\DTO\Sitemap;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class SitemapUrlDTO implements \JsonSerializable
{
    /** @var list<SitemapAlternateUrlDTO> */
    public array $alternates;

    /** @var list<SitemapImageDTO> */
    public array $images;

    /** @var list<SitemapVideoDTO> */
    public array $videos;

    /** @var list<SitemapNewsDTO> */
    public array $news;

    /**
     * @param array<mixed> $alternates
     * @param array<mixed> $images
     * @param array<mixed> $videos
     * @param array<mixed> $news
     */
    public function __construct(
        public string $loc,
        public ?string $lastmod = null,
        public ?string $changefreq = null,
        public ?float $priority = null,
        array $alternates = [],
        array $images = [],
        array $videos = [],
        array $news = [],
    ) {
        if (trim($this->loc) === '') {
            throw SeoInvalidArgumentException::emptyField('loc');
        }
        if (filter_var($this->loc, FILTER_VALIDATE_URL) === false) {
            throw SeoInvalidArgumentException::emptyField('loc');
        }
        if ($this->lastmod !== null && !self::isValidLastmod($this->lastmod)) {
            throw SeoInvalidArgumentException::emptyField('lastmod');
        }
        if ($this->changefreq !== null && !in_array($this->changefreq, self::allowedChangefreqValues(), true)) {
            throw SeoInvalidArgumentException::emptyField('changefreq');
        }
        if ($this->priority !== null && ($this->priority < 0.0 || $this->priority > 1.0)) {
            throw SeoInvalidArgumentException::emptyField('priority');
        }

        $validAlternates = [];
        foreach ($alternates as $alternate) {
            if (!$alternate instanceof SitemapAlternateUrlDTO) {
                throw SeoInvalidArgumentException::emptyField('alternates');
            }

            $validAlternates[] = $alternate;
        }

        $this->alternates = $validAlternates;

        $validImages = [];
        foreach ($images as $image) {
            if (!$image instanceof SitemapImageDTO) {
                throw SeoInvalidArgumentException::emptyField('images');
            }

            $validImages[] = $image;
        }

        $this->images = $validImages;

        $validVideos = [];
        foreach ($videos as $video) {
            if (!$video instanceof SitemapVideoDTO) {
                throw SeoInvalidArgumentException::emptyField('videos');
            }

            $validVideos[] = $video;
        }

        $this->videos = $validVideos;

        $validNews = [];
        foreach ($news as $newsEntry) {
            if (!$newsEntry instanceof SitemapNewsDTO) {
                throw SeoInvalidArgumentException::emptyField('news');
            }

            $validNews[] = $newsEntry;
        }

        $this->news = $validNews;
    }

    public static function isValidLastmod(string $lastmod): bool
    {
        $value = trim($lastmod);
        if ($value === '') {
            return false;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            $parts = explode('-', $value);

            return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
        }

        return \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $value) instanceof \DateTimeImmutable;
    }

    /**
     * @return list<string>
     */
    public static function allowedChangefreqValues(): array
    {
        return ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];
    }

    /**
     * @return array{loc: string, lastmod: ?string, changefreq: ?string, priority: ?float, alternates: list<array{hreflang: string, url: string}>, images: list<array{loc: string, title: ?string, caption: ?string, geoLocation: ?string, license: ?string}>, videos: list<array{thumbnailLoc: string, title: string, description: string, contentLoc: ?string, playerLoc: ?string, duration: ?int, publicationDate: ?string}>, news: list<array{publicationName: string, publicationLanguage: string, publicationDate: string, title: string, access: ?string, genres: ?string, keywords: ?string, stockTickers: ?string}>}
     */
    public function jsonSerialize(): array
    {
        $alternates = [];
        foreach ($this->alternates as $alternate) {
            $alternates[] = $alternate->jsonSerialize();
        }
        $images = [];
        foreach ($this->images as $image) {
            $images[] = $image->jsonSerialize();
        }
        $videos = [];
        foreach ($this->videos as $video) {
            $videos[] = $video->jsonSerialize();
        }
        $news = [];
        foreach ($this->news as $newsEntry) {
            $news[] = $newsEntry->jsonSerialize();
        }

        return [
            'loc' => trim($this->loc),
            'lastmod' => $this->lastmod === null ? null : trim($this->lastmod),
            'changefreq' => $this->changefreq,
            'priority' => $this->priority,
            'alternates' => $alternates,
            'images' => $images,
            'videos' => $videos,
            'news' => $news,
        ];
    }
}
