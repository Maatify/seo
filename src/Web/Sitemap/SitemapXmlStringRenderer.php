<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Sitemap;

use Maatify\Seo\Exception\SeoInvalidArgumentException;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapAlternateUrlDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapImageDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapNewsDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapUrlDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapVideoDTO;
use XMLWriter;

final readonly class SitemapXmlStringRenderer
{
    /**
     * @param array<mixed> $urls
     */
    public function renderUrlSet(array $urls): string
    {
        $writer = $this->createWriter();
        $normalizedUrls = [];
        $hasAlternates = false;
        $hasImages = false;
        $hasVideos = false;
        $hasNews = false;
        foreach ($urls as $url) {
            $normalizedUrl = $this->normalizeUrlEntry($url);
            if ($normalizedUrl['alternates'] !== []) {
                $hasAlternates = true;
            }
            if ($normalizedUrl['images'] !== []) {
                $hasImages = true;
            }
            if ($normalizedUrl['videos'] !== []) {
                $hasVideos = true;
            }
            if ($normalizedUrl['news'] !== []) {
                $hasNews = true;
            }
            $normalizedUrls[] = $normalizedUrl;
        }

        $writer->startElement('urlset');
        $writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        if ($hasAlternates) {
            $writer->writeAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');
        }
        if ($hasImages) {
            $writer->writeAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');
        }
        if ($hasVideos) {
            $writer->writeAttribute('xmlns:video', 'http://www.google.com/schemas/sitemap-video/1.1');
        }
        if ($hasNews) {
            $writer->writeAttribute('xmlns:news', 'http://www.google.com/schemas/sitemap-news/0.9');
        }

        foreach ($normalizedUrls as $url) {
            $this->writeUrlEntry($writer, $url, false);
        }

        $writer->endElement();
        $writer->endDocument();

        return $this->flushWriter($writer);
    }

    public function renderUrlEntry(mixed $url): string
    {
        $writer = $this->createWriter();
        $this->writeUrlEntry($writer, $this->normalizeUrlEntry($url), true);
        $writer->endDocument();

        return $this->flushWriter($writer);
    }

    /**
     * @return array{loc: string, lastmod: ?string, changefreq: ?string, priority: ?float, alternates: list<array{hreflang: string, url: string}>, images: list<array{loc: string, title: ?string, caption: ?string, geoLocation: ?string, license: ?string}>, videos: list<array{thumbnailLoc: string, title: string, description: string, contentLoc: ?string, playerLoc: ?string, duration: ?int, publicationDate: ?string}>, news: list<array{publicationName: string, publicationLanguage: string, publicationDate: string, title: string, access: ?string, genres: ?string, keywords: ?string, stockTickers: ?string}>}
     */
    private function normalizeUrlEntry(mixed $url): array
    {
        if ($url instanceof SitemapUrlDTO) {
            return [
                'loc' => trim($url->loc),
                'lastmod' => $this->nullableNonEmptyString($url->lastmod),
                'changefreq' => $this->nullableNonEmptyString($url->changefreq),
                'priority' => $url->priority,
                'alternates' => $this->normalizeDtoAlternates($url->alternates),
                'images' => $this->normalizeDtoImages($url->images),
                'videos' => $this->normalizeDtoVideos($url->videos),
                'news' => $this->normalizeDtoNews($url->news),
            ];
        }

        if (!is_array($url)) {
            throw SeoInvalidArgumentException::emptyField('url');
        }

        $loc = $url['loc'] ?? null;
        if (!is_scalar($loc) || trim((string) $loc) === '') {
            throw SeoInvalidArgumentException::emptyField('loc');
        }

        return [
            'loc' => trim((string) $loc),
            'lastmod' => $this->nullableArrayString($url, 'lastmod'),
            'changefreq' => $this->nullableArrayString($url, 'changefreq'),
            'priority' => $this->nullableArrayFloat($url, 'priority'),
            'alternates' => $this->normalizeArrayAlternates($url),
            'images' => $this->normalizeArrayImages($url),
            'videos' => $this->normalizeArrayVideos($url),
            'news' => $this->normalizeArrayNews($url),
        ];
    }

    /**
     * @param list<SitemapAlternateUrlDTO> $alternates
     * @return list<array{hreflang: string, url: string}>
     */
    private function normalizeDtoAlternates(array $alternates): array
    {
        $normalized = [];
        foreach ($alternates as $alternate) {
            $normalized[] = $this->normalizeAlternateValues($alternate->hreflang, $alternate->url);
        }

        return $normalized;
    }

    /**
     * @param list<SitemapImageDTO> $images
     * @return list<array{loc: string, title: ?string, caption: ?string, geoLocation: ?string, license: ?string}>
     */
    private function normalizeDtoImages(array $images): array
    {
        $normalized = [];
        foreach ($images as $image) {
            $normalized[] = $this->normalizeImageValues(
                $image->loc,
                $image->title,
                $image->caption,
                $image->geoLocation,
                $image->license,
            );
        }

        return $normalized;
    }

    /**
     * @param list<SitemapVideoDTO> $videos
     * @return list<array{thumbnailLoc: string, title: string, description: string, contentLoc: ?string, playerLoc: ?string, duration: ?int, publicationDate: ?string}>
     */
    private function normalizeDtoVideos(array $videos): array
    {
        $normalized = [];
        foreach ($videos as $video) {
            $normalized[] = $this->normalizeVideoValues(
                $video->thumbnailLoc,
                $video->title,
                $video->description,
                $video->contentLoc,
                $video->playerLoc,
                $video->duration,
                $video->publicationDate,
            );
        }

        return $normalized;
    }

    /**
     * @param list<SitemapNewsDTO> $news
     * @return list<array{publicationName: string, publicationLanguage: string, publicationDate: string, title: string, access: ?string, genres: ?string, keywords: ?string, stockTickers: ?string}>
     */
    private function normalizeDtoNews(array $news): array
    {
        $normalized = [];
        foreach ($news as $newsEntry) {
            $normalized[] = $this->normalizeNewsValues(
                $newsEntry->publicationName,
                $newsEntry->publicationLanguage,
                $newsEntry->publicationDate,
                $newsEntry->title,
                $newsEntry->access,
                $newsEntry->genres,
                $newsEntry->keywords,
                $newsEntry->stockTickers,
            );
        }

        return $normalized;
    }

    /**
     * @param array<mixed> $url
     * @return list<array{hreflang: string, url: string}>
     */
    private function normalizeArrayAlternates(array $url): array
    {
        if (!array_key_exists('alternates', $url) || $url['alternates'] === null) {
            return [];
        }

        if (!is_array($url['alternates']) || !$this->isListArray($url['alternates'])) {
            throw SeoInvalidArgumentException::emptyField('alternates');
        }

        $normalized = [];
        foreach ($url['alternates'] as $alternate) {
            if (!is_array($alternate) || $this->isListArray($alternate)) {
                throw SeoInvalidArgumentException::emptyField('alternates');
            }

            $hreflang = $alternate['hreflang'] ?? null;
            $alternateUrl = $alternate['url'] ?? null;
            if (!is_scalar($hreflang) || trim((string) $hreflang) === '') {
                throw SeoInvalidArgumentException::emptyField('hreflang');
            }
            if (!is_scalar($alternateUrl) || trim((string) $alternateUrl) === '') {
                throw SeoInvalidArgumentException::emptyField('url');
            }

            $normalized[] = $this->normalizeAlternateValues((string) $hreflang, (string) $alternateUrl);
        }

        return $normalized;
    }

    /**
     * @param array<mixed> $url
     * @return list<array{loc: string, title: ?string, caption: ?string, geoLocation: ?string, license: ?string}>
     */
    private function normalizeArrayImages(array $url): array
    {
        if (!array_key_exists('images', $url) || $url['images'] === null) {
            return [];
        }

        if (!is_array($url['images']) || !$this->isListArray($url['images'])) {
            throw SeoInvalidArgumentException::emptyField('images');
        }

        $normalized = [];
        foreach ($url['images'] as $image) {
            if (!is_array($image) || $this->isListArray($image)) {
                throw SeoInvalidArgumentException::emptyField('images');
            }

            $loc = $image['loc'] ?? null;
            if (!is_scalar($loc) || trim((string) $loc) === '') {
                throw SeoInvalidArgumentException::emptyField('loc');
            }

            $normalized[] = $this->normalizeImageValues(
                (string) $loc,
                $this->nullableArrayString($image, 'title'),
                $this->nullableArrayString($image, 'caption'),
                $this->nullableArrayString($image, 'geoLocation'),
                $this->nullableArrayString($image, 'license'),
            );
        }

        return $normalized;
    }

    /**
     * @param array<mixed> $url
     * @return list<array{thumbnailLoc: string, title: string, description: string, contentLoc: ?string, playerLoc: ?string, duration: ?int, publicationDate: ?string}>
     */
    private function normalizeArrayVideos(array $url): array
    {
        if (!array_key_exists('videos', $url) || $url['videos'] === null) {
            return [];
        }

        if (!is_array($url['videos']) || !$this->isListArray($url['videos'])) {
            throw SeoInvalidArgumentException::emptyField('videos');
        }

        $normalized = [];
        foreach ($url['videos'] as $video) {
            if (!is_array($video) || $this->isListArray($video)) {
                throw SeoInvalidArgumentException::emptyField('videos');
            }

            $thumbnailLoc = $video['thumbnailLoc'] ?? null;
            $title = $video['title'] ?? null;
            $description = $video['description'] ?? null;
            if (!is_scalar($thumbnailLoc) || trim((string) $thumbnailLoc) === '') {
                throw SeoInvalidArgumentException::emptyField('thumbnailLoc');
            }
            if (!is_scalar($title) || trim((string) $title) === '') {
                throw SeoInvalidArgumentException::emptyField('title');
            }
            if (!is_scalar($description) || trim((string) $description) === '') {
                throw SeoInvalidArgumentException::emptyField('description');
            }

            $normalized[] = $this->normalizeVideoValues(
                (string) $thumbnailLoc,
                (string) $title,
                (string) $description,
                $this->nullableArrayString($video, 'contentLoc'),
                $this->nullableArrayString($video, 'playerLoc'),
                $this->nullableArrayInt($video, 'duration'),
                $this->nullableArrayString($video, 'publicationDate'),
            );
        }

        return $normalized;
    }

    /**
     * @param array<mixed> $url
     * @return list<array{publicationName: string, publicationLanguage: string, publicationDate: string, title: string, access: ?string, genres: ?string, keywords: ?string, stockTickers: ?string}>
     */
    private function normalizeArrayNews(array $url): array
    {
        if (!array_key_exists('news', $url) || $url['news'] === null) {
            return [];
        }

        if (!is_array($url['news']) || !$this->isListArray($url['news'])) {
            throw SeoInvalidArgumentException::emptyField('news');
        }

        $normalized = [];
        foreach ($url['news'] as $newsEntry) {
            if (!is_array($newsEntry) || $this->isListArray($newsEntry)) {
                throw SeoInvalidArgumentException::emptyField('news');
            }

            $publicationName = $newsEntry['publicationName'] ?? null;
            $publicationLanguage = $newsEntry['publicationLanguage'] ?? null;
            $publicationDate = $newsEntry['publicationDate'] ?? null;
            $title = $newsEntry['title'] ?? null;
            if (!is_scalar($publicationName) || trim((string) $publicationName) === '') {
                throw SeoInvalidArgumentException::emptyField('publicationName');
            }
            if (!is_scalar($publicationLanguage) || trim((string) $publicationLanguage) === '') {
                throw SeoInvalidArgumentException::emptyField('publicationLanguage');
            }
            if (!is_scalar($publicationDate) || trim((string) $publicationDate) === '') {
                throw SeoInvalidArgumentException::emptyField('publicationDate');
            }
            if (!is_scalar($title) || trim((string) $title) === '') {
                throw SeoInvalidArgumentException::emptyField('title');
            }

            $normalized[] = $this->normalizeNewsValues(
                (string) $publicationName,
                (string) $publicationLanguage,
                (string) $publicationDate,
                (string) $title,
                $this->nullableArrayString($newsEntry, 'access'),
                $this->nullableArrayString($newsEntry, 'genres'),
                $this->nullableArrayString($newsEntry, 'keywords'),
                $this->nullableArrayString($newsEntry, 'stockTickers'),
            );
        }

        return $normalized;
    }

    /**
     * @return array{hreflang: string, url: string}
     */
    private function normalizeAlternateValues(string $hreflang, string $url): array
    {
        $hreflang = trim($hreflang);
        $url = trim($url);
        if ($hreflang === '') {
            throw SeoInvalidArgumentException::emptyField('hreflang');
        }
        if ($url === '') {
            throw SeoInvalidArgumentException::emptyField('url');
        }
        if (!$this->isValidHreflang($hreflang)) {
            throw SeoInvalidArgumentException::emptyField('hreflang');
        }
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw SeoInvalidArgumentException::invalidUrl($url);
        }

        return [
            'hreflang' => strtolower($hreflang),
            'url' => $url,
        ];
    }

    /**
     * @return array{loc: string, title: ?string, caption: ?string, geoLocation: ?string, license: ?string}
     */
    private function normalizeImageValues(
        string $loc,
        ?string $title,
        ?string $caption,
        ?string $geoLocation,
        ?string $license,
    ): array {
        $loc = trim($loc);
        if ($loc === '') {
            throw SeoInvalidArgumentException::emptyField('loc');
        }
        if (filter_var($loc, FILTER_VALIDATE_URL) === false) {
            throw SeoInvalidArgumentException::invalidUrl($loc);
        }

        $license = $this->nullableNonEmptyString($license);
        if ($license !== null && filter_var($license, FILTER_VALIDATE_URL) === false) {
            throw SeoInvalidArgumentException::invalidUrl($license);
        }

        return [
            'loc' => $loc,
            'title' => $this->nullableNonEmptyString($title),
            'caption' => $this->nullableNonEmptyString($caption),
            'geoLocation' => $this->nullableNonEmptyString($geoLocation),
            'license' => $license,
        ];
    }

    /**
     * @return array{thumbnailLoc: string, title: string, description: string, contentLoc: ?string, playerLoc: ?string, duration: ?int, publicationDate: ?string}
     */
    private function normalizeVideoValues(
        string $thumbnailLoc,
        string $title,
        string $description,
        ?string $contentLoc,
        ?string $playerLoc,
        ?int $duration,
        ?string $publicationDate,
    ): array {
        $thumbnailLoc = trim($thumbnailLoc);
        if ($thumbnailLoc === '') {
            throw SeoInvalidArgumentException::emptyField('thumbnailLoc');
        }
        if (filter_var($thumbnailLoc, FILTER_VALIDATE_URL) === false) {
            throw SeoInvalidArgumentException::invalidUrl($thumbnailLoc);
        }

        $title = trim($title);
        if ($title === '') {
            throw SeoInvalidArgumentException::emptyField('title');
        }

        $description = trim($description);
        if ($description === '') {
            throw SeoInvalidArgumentException::emptyField('description');
        }

        $contentLoc = $this->nullableNonEmptyString($contentLoc);
        if ($contentLoc !== null && filter_var($contentLoc, FILTER_VALIDATE_URL) === false) {
            throw SeoInvalidArgumentException::invalidUrl($contentLoc);
        }

        $playerLoc = $this->nullableNonEmptyString($playerLoc);
        if ($playerLoc !== null && filter_var($playerLoc, FILTER_VALIDATE_URL) === false) {
            throw SeoInvalidArgumentException::invalidUrl($playerLoc);
        }

        if ($contentLoc === null && $playerLoc === null) {
            throw SeoInvalidArgumentException::emptyField('contentLoc');
        }

        if ($duration !== null && $duration <= 0) {
            throw SeoInvalidArgumentException::invalidValue('duration', 'must be greater than 0.');
        }

        $publicationDate = $this->nullableNonEmptyString($publicationDate);
        if ($publicationDate !== null && !SitemapUrlDTO::isValidLastmod($publicationDate)) {
            throw SeoInvalidArgumentException::emptyField('publicationDate');
        }

        return [
            'thumbnailLoc' => $thumbnailLoc,
            'title' => $title,
            'description' => $description,
            'contentLoc' => $contentLoc,
            'playerLoc' => $playerLoc,
            'duration' => $duration,
            'publicationDate' => $publicationDate,
        ];
    }

    /**
     * @return array{publicationName: string, publicationLanguage: string, publicationDate: string, title: string, access: ?string, genres: ?string, keywords: ?string, stockTickers: ?string}
     */
    private function normalizeNewsValues(
        string $publicationName,
        string $publicationLanguage,
        string $publicationDate,
        string $title,
        ?string $access,
        ?string $genres,
        ?string $keywords,
        ?string $stockTickers,
    ): array {
        $publicationName = trim($publicationName);
        if ($publicationName === '') {
            throw SeoInvalidArgumentException::emptyField('publicationName');
        }
        $publicationLanguage = trim($publicationLanguage);
        if ($publicationLanguage === '') {
            throw SeoInvalidArgumentException::emptyField('publicationLanguage');
        }
        $publicationDate = trim($publicationDate);
        if ($publicationDate === '') {
            throw SeoInvalidArgumentException::emptyField('publicationDate');
        }
        $title = trim($title);
        if ($title === '') {
            throw SeoInvalidArgumentException::emptyField('title');
        }

        return [
            'publicationName' => $publicationName,
            'publicationLanguage' => $publicationLanguage,
            'publicationDate' => $publicationDate,
            'title' => $title,
            'access' => $this->nullableNonEmptyString($access),
            'genres' => $this->nullableNonEmptyString($genres),
            'keywords' => $this->nullableNonEmptyString($keywords),
            'stockTickers' => $this->nullableNonEmptyString($stockTickers),
        ];
    }

    private function isValidHreflang(string $hreflang): bool
    {
        $value = strtolower(trim($hreflang));

        return $value === 'x-default' || preg_match('/^[a-z]{2,3}(?:-[a-z0-9]{2,8})*$/', $value) === 1;
    }

    /**
     * @param array<mixed> $value
     */
    private function isListArray(array $value): bool
    {
        return array_is_list($value);
    }

    /**
     * @param array<mixed> $url
     */
    private function nullableArrayString(array $url, string $field): ?string
    {
        if (!array_key_exists($field, $url) || $url[$field] === null) {
            return null;
        }

        if (!is_scalar($url[$field])) {
            throw SeoInvalidArgumentException::emptyField($field);
        }

        return $this->nullableNonEmptyString((string) $url[$field]);
    }

    /**
     * @param array<mixed> $url
     */
    private function nullableArrayFloat(array $url, string $field): ?float
    {
        if (!array_key_exists($field, $url) || $url[$field] === null || $url[$field] === '') {
            return null;
        }

        if (!is_int($url[$field]) && !is_float($url[$field]) && !is_string($url[$field])) {
            throw SeoInvalidArgumentException::emptyField($field);
        }

        if (!is_numeric($url[$field])) {
            throw SeoInvalidArgumentException::emptyField($field);
        }

        return (float) $url[$field];
    }

    /**
     * @param array<mixed> $url
     */
    private function nullableArrayInt(array $url, string $field): ?int
    {
        if (!array_key_exists($field, $url) || $url[$field] === null || $url[$field] === '') {
            return null;
        }

        if (!is_int($url[$field]) && !is_string($url[$field])) {
            throw SeoInvalidArgumentException::emptyField($field);
        }

        if (filter_var($url[$field], FILTER_VALIDATE_INT) === false) {
            throw SeoInvalidArgumentException::emptyField($field);
        }

        return (int) $url[$field];
    }

    private function nullableNonEmptyString(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return trim($value);
    }

    /**
     * @param array{loc: string, lastmod: ?string, changefreq: ?string, priority: ?float, alternates: list<array{hreflang: string, url: string}>, images: list<array{loc: string, title: ?string, caption: ?string, geoLocation: ?string, license: ?string}>, videos: list<array{thumbnailLoc: string, title: string, description: string, contentLoc: ?string, playerLoc: ?string, duration: ?int, publicationDate: ?string}>, news: list<array{publicationName: string, publicationLanguage: string, publicationDate: string, title: string, access: ?string, genres: ?string, keywords: ?string, stockTickers: ?string}>} $url
     */
    private function writeUrlEntry(XMLWriter $writer, array $url, bool $declareAlternateNamespace): void
    {
        $writer->startElement('url');
        if ($declareAlternateNamespace && $url['alternates'] !== []) {
            $writer->writeAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');
        }
        if ($declareAlternateNamespace && $url['images'] !== []) {
            $writer->writeAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');
        }
        if ($declareAlternateNamespace && $url['videos'] !== []) {
            $writer->writeAttribute('xmlns:video', 'http://www.google.com/schemas/sitemap-video/1.1');
        }
        if ($declareAlternateNamespace && $url['news'] !== []) {
            $writer->writeAttribute('xmlns:news', 'http://www.google.com/schemas/sitemap-news/0.9');
        }
        $writer->writeElement('loc', $url['loc']);

        if ($url['lastmod'] !== null) {
            $writer->writeElement('lastmod', $url['lastmod']);
        }
        if ($url['changefreq'] !== null) {
            $writer->writeElement('changefreq', $url['changefreq']);
        }
        if ($url['priority'] !== null) {
            $writer->writeElement('priority', number_format($url['priority'], 1, '.', ''));
        }
        foreach ($url['alternates'] as $alternate) {
            $writer->startElement('xhtml:link');
            $writer->writeAttribute('rel', 'alternate');
            $writer->writeAttribute('hreflang', $alternate['hreflang']);
            $writer->writeAttribute('href', $alternate['url']);
            $writer->endElement();
        }
        foreach ($url['images'] as $image) {
            $writer->startElement('image:image');
            $writer->writeElement('image:loc', $image['loc']);
            if ($image['title'] !== null) {
                $writer->writeElement('image:title', $image['title']);
            }
            if ($image['caption'] !== null) {
                $writer->writeElement('image:caption', $image['caption']);
            }
            if ($image['geoLocation'] !== null) {
                $writer->writeElement('image:geo_location', $image['geoLocation']);
            }
            if ($image['license'] !== null) {
                $writer->writeElement('image:license', $image['license']);
            }
            $writer->endElement();
        }
        foreach ($url['videos'] as $video) {
            $writer->startElement('video:video');
            $writer->writeElement('video:thumbnail_loc', $video['thumbnailLoc']);
            $writer->writeElement('video:title', $video['title']);
            $writer->writeElement('video:description', $video['description']);
            if ($video['contentLoc'] !== null) {
                $writer->writeElement('video:content_loc', $video['contentLoc']);
            }
            if ($video['playerLoc'] !== null) {
                $writer->writeElement('video:player_loc', $video['playerLoc']);
            }
            if ($video['duration'] !== null) {
                $writer->writeElement('video:duration', (string) $video['duration']);
            }
            if ($video['publicationDate'] !== null) {
                $writer->writeElement('video:publication_date', $video['publicationDate']);
            }
            $writer->endElement();
        }
        foreach ($url['news'] as $newsEntry) {
            $writer->startElement('news:news');
            $writer->startElement('news:publication');
            $writer->writeElement('news:name', $newsEntry['publicationName']);
            $writer->writeElement('news:language', $newsEntry['publicationLanguage']);
            $writer->endElement();
            $writer->writeElement('news:publication_date', $newsEntry['publicationDate']);
            $writer->writeElement('news:title', $newsEntry['title']);
            if ($newsEntry['access'] !== null) {
                $writer->writeElement('news:access', $newsEntry['access']);
            }
            if ($newsEntry['genres'] !== null) {
                $writer->writeElement('news:genres', $newsEntry['genres']);
            }
            if ($newsEntry['keywords'] !== null) {
                $writer->writeElement('news:keywords', $newsEntry['keywords']);
            }
            if ($newsEntry['stockTickers'] !== null) {
                $writer->writeElement('news:stock_tickers', $newsEntry['stockTickers']);
            }
            $writer->endElement();
        }

        $writer->endElement();
    }

    private function createWriter(): XMLWriter
    {
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->startDocument('1.0', 'UTF-8');

        return $writer;
    }

    private function flushWriter(XMLWriter $writer): string
    {
        $xml = $writer->outputMemory();
        if ($xml === '') {
            throw SeoInvalidArgumentException::emptyField('xml');
        }

        return $xml;
    }
}
