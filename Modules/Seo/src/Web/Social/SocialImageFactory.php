<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Social;

final class SocialImageFactory
{
    private function __construct()
    {
    }

    public static function fromUrl(string $url): SocialImage
    {
        return new SocialImage($url);
    }

    public static function fromUrlWithAlt(string $url, string $alt): SocialImage
    {
        return self::fromUrl($url)->setAlt($alt);
    }

    public static function openGraph(string $url, ?string $alt = null): SocialImage
    {
        return self::withOptionalAlt(self::fromUrl($url), $alt);
    }

    public static function twitterLargeImage(string $url, ?string $alt = null): SocialImage
    {
        return self::withOptionalAlt(self::fromUrl($url), $alt);
    }

    public static function jpeg(string $url, int $width, int $height, ?string $alt = null): SocialImage
    {
        return self::typedImage($url, 'image/jpeg', $width, $height, $alt);
    }

    public static function png(string $url, int $width, int $height, ?string $alt = null): SocialImage
    {
        return self::typedImage($url, 'image/png', $width, $height, $alt);
    }

    public static function webp(string $url, int $width, int $height, ?string $alt = null): SocialImage
    {
        return self::typedImage($url, 'image/webp', $width, $height, $alt);
    }

    public static function withDimensions(string $url, int $width, int $height, ?string $alt = null): SocialImage
    {
        return self::withOptionalAlt(
            self::fromUrl($url)
                ->setWidth($width)
                ->setHeight($height),
            $alt
        );
    }

    public static function withSecureUrl(string $url, string $secureUrl, ?string $alt = null): SocialImage
    {
        return self::withOptionalAlt(
            self::fromUrl($url)->setSecureUrl($secureUrl),
            $alt
        );
    }

    private static function typedImage(string $url, string $type, int $width, int $height, ?string $alt): SocialImage
    {
        return self::withOptionalAlt(
            self::fromUrl($url)
                ->setType($type)
                ->setWidth($width)
                ->setHeight($height),
            $alt
        );
    }

    private static function withOptionalAlt(SocialImage $image, ?string $alt): SocialImage
    {
        if ($alt !== null) {
            $image->setAlt($alt);
        }

        return $image;
    }
}
