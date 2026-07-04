<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Page;

final class ContentSeoPresetFactory
{
    /** @param array<string, mixed> $article @param array<string, mixed> $options */
    public static function article(string $title, ?string $description, array $article, array $options = []): SeoPagePresetOutputDTO
    {
        self::assertPublicationData($article, 'article');

        return SeoPagePresetFactory::article($title, $description, $article + ['type' => 'Article'], $options);
    }

    /** @param array<string, mixed> $post @param array<string, mixed> $options */
    public static function blogPost(string $title, ?string $description, array $post, array $options = []): SeoPagePresetOutputDTO
    {
        self::assertPublicationData($post, 'blogPost');

        return SeoPagePresetFactory::article($title, $description, $post + ['type' => 'BlogPosting'], $options);
    }

    /** @param array<string, mixed> $news @param array<string, mixed> $options */
    public static function newsArticle(string $title, ?string $description, array $news, array $options = []): SeoPagePresetOutputDTO
    {
        self::assertPublicationData($news, 'newsArticle');

        return SeoPagePresetFactory::article($title, $description, $news + ['type' => 'NewsArticle'], $options);
    }

    /** @param array<string, mixed> $author @param array<string, mixed> $options */
    public static function authorPage(string $title, ?string $description = null, array $author = [], array $options = []): SeoPagePresetOutputDTO
    {
        if (array_key_exists('name', $author)) {
            DomainSeoPresetFactoryHelper::expectString($author['name'], 'author.name');
        }

        return SeoPagePresetFactory::generic($title, $description, $options);
    }

    /** @param list<array<string, mixed>|string> $items @param array<string, mixed> $options */
    public static function tagPage(string $title, ?string $description = null, array $items = [], array $options = []): SeoPagePresetOutputDTO
    {
        return SeoPagePresetFactory::category($title, $description, $items, $options);
    }

    /** @param array<string, mixed> $data */
    private static function assertPublicationData(array $data, string $prefix): void
    {
        DomainSeoPresetFactoryHelper::requireString($data, 'author', $prefix . '.author');
        DomainSeoPresetFactoryHelper::requireString($data, 'datePublished', $prefix . '.datePublished');
    }
}
