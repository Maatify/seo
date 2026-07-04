<?php

declare(strict_types=1);

namespace Maatify\Seo\Admin\Preview;

use Maatify\Seo\Admin\DTO\SerpPreviewDTO;
use Maatify\Seo\Shared\DTO\MetaTagsDTO;
use Maatify\Seo\Web\Page\SeoPagePresetOutputDTO;

final class SerpPreviewFactory
{
    public static function fromPreset(SeoPagePresetOutputDTO $preset): SerpPreviewDTO
    {
        return self::fromMetaTags($preset->metaTags, $preset->canonicalUrl, $preset->robots);
    }

    public static function fromMetaTags(MetaTagsDTO $metaTags, ?string $url = null, ?string $robots = null): SerpPreviewDTO
    {
        $previewUrl = $url ?? $metaTags->canonicalUrl;
        return new SerpPreviewDTO(
            $metaTags->title,
            $metaTags->description,
            $previewUrl,
            self::displayUrl($previewUrl),
            $robots ?? $metaTags->robots,
            self::warnings($metaTags->title, $metaTags->description, $previewUrl)
        );
    }

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): SerpPreviewDTO
    {
        $title = isset($input['title']) && is_string($input['title']) ? $input['title'] : '';
        $description = isset($input['description']) && is_string($input['description']) ? $input['description'] : null;
        $url = isset($input['url']) && is_string($input['url']) ? $input['url'] : (isset($input['canonical_url']) && is_string($input['canonical_url']) ? $input['canonical_url'] : null);
        $robots = isset($input['robots']) && is_string($input['robots']) ? $input['robots'] : 'index,follow';
        $warnings = self::warnings($title, $description, $url);

        return new SerpPreviewDTO($title, $description, $url, self::displayUrl($url), $robots, $warnings);
    }

    private static function displayUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return $url;
        }

        $host = $parts['host'] ?? null;
        if ($host === null) {
            return $url;
        }

        return $host . ($parts['path'] ?? '');
    }

    /** @return list<string> */
    private static function warnings(string $title, ?string $description, ?string $url): array
    {
        $warnings = [];
        if (trim($title) === '') {
            $warnings[] = 'Missing title.';
        }
        if ($description === null || trim($description) === '') {
            $warnings[] = 'Missing description.';
        }
        if ($url === null || trim($url) === '') {
            $warnings[] = 'Missing canonical URL.';
        }
        return $warnings;
    }
}
