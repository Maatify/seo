<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Sitemap;

use Maatify\Seo\Exception\SeoInvalidArgumentException;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapAlternateUrlDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapImageDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapUrlDTO;
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
        foreach ($urls as $url) {
            $normalizedUrl = $this->normalizeUrlEntry($url);
            if ($normalizedUrl['alternates'] !== []) {
                $hasAlternates = true;
            }
            if ($normalizedUrl['images'] !== []) {
                $hasImages = true;
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
     * @return array{loc: string, lastmod: ?string, changefreq: ?string, priority: ?float, alternates: list<array{hreflang: string, url: string}>, images: list<array{loc: string, title: ?string, caption: ?string, geoLocation: ?string, license: ?string}>}
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

    private function nullableNonEmptyString(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return trim($value);
    }

    /**
     * @param array{loc: string, lastmod: ?string, changefreq: ?string, priority: ?float, alternates: list<array{hreflang: string, url: string}>, images: list<array{loc: string, title: ?string, caption: ?string, geoLocation: ?string, license: ?string}>} $url
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
