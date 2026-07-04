<?php

declare(strict_types=1);

namespace Maatify\Seo\Admin\Preview;

use Maatify\Seo\Admin\DTO\SocialPreviewDTO;
use Maatify\Seo\Shared\DTO\MetaTagsDTO;
use Maatify\Seo\Web\Page\SeoPagePresetOutputDTO;

final class SocialPreviewFactory
{
    public static function fromPreset(SeoPagePresetOutputDTO $preset, ?string $siteName = null): SocialPreviewDTO
    {
        return self::fromMetaTags($preset->metaTags, $siteName);
    }

    public static function fromMetaTags(MetaTagsDTO $metaTags, ?string $siteName = null): SocialPreviewDTO
    {
        $title = $metaTags->openGraphTitle ?? $metaTags->twitterTitle ?? $metaTags->title;
        $description = $metaTags->openGraphDescription ?? $metaTags->twitterDescription ?? $metaTags->description;
        $imageUrl = $metaTags->openGraphImage ?? $metaTags->twitterImage;
        $url = $metaTags->openGraphUrl ?? $metaTags->canonicalUrl;

        return new SocialPreviewDTO($title, $description, $imageUrl, $url, $metaTags->openGraphType ?? 'website', $siteName, $metaTags->twitterCard, self::warnings($title, $description, $imageUrl, $url));
    }

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): SocialPreviewDTO
    {
        $title = isset($input['title']) && is_string($input['title']) ? $input['title'] : '';
        $description = isset($input['description']) && is_string($input['description']) ? $input['description'] : null;
        $imageUrl = isset($input['image_url']) && is_string($input['image_url']) ? $input['image_url'] : null;
        $url = isset($input['url']) && is_string($input['url']) ? $input['url'] : null;
        $type = isset($input['type']) && is_string($input['type']) ? $input['type'] : 'website';
        $siteName = isset($input['site_name']) && is_string($input['site_name']) ? $input['site_name'] : null;
        $twitterCard = isset($input['twitter_card']) && is_string($input['twitter_card']) ? $input['twitter_card'] : null;

        return new SocialPreviewDTO($title, $description, $imageUrl, $url, $type, $siteName, $twitterCard, self::warnings($title, $description, $imageUrl, $url));
    }

    /** @return list<string> */
    private static function warnings(string $title, ?string $description, ?string $imageUrl, ?string $url): array
    {
        $warnings = [];
        if (trim($title) === '') $warnings[] = 'Missing title.';
        if ($description === null || trim($description) === '') $warnings[] = 'Missing description.';
        if ($imageUrl === null || trim($imageUrl) === '') $warnings[] = 'Missing image URL.';
        if ($url === null || trim($url) === '') $warnings[] = 'Missing URL.';
        return $warnings;
    }
}
