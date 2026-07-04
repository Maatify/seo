<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Page;

use Maatify\Seo\Exception\SeoInvalidArgumentException;
use Maatify\Seo\Shared\DTO\Schema\JsonLdSchemaDTO;
use Maatify\Seo\Web\Builder\FluentSeoBuilder;
use Maatify\Seo\Web\Indexing\CanonicalUrlBuilder;
use Maatify\Seo\Web\JsonLd\Builder\ArticleJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\BreadcrumbJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\ItemListJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\ProductJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\WebPageJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\WebSiteJsonLdBuilder;
use Maatify\Seo\Web\Render\SeoHeadHtmlRenderer;
use Maatify\Seo\Web\Robots\MetaRobotsBuilder;
use Maatify\Seo\Web\Social\SocialPreviewBuilder;

final class SeoPagePresetFactory
{
    /** @param array<string, mixed> $options */
    public static function generic(string $title, ?string $description = null, array $options = []): SeoPagePresetOutputDTO
    {
        self::assertTitle($title);
        $canonical = self::canonical($options);
        $schemas = [self::webPageSchema($title, $description, $canonical, $options)];

        return self::build($title, $description, $canonical, self::robots($options), $schemas, $options, 'website');
    }

    /** @param array<string, mixed> $product @param array<string, mixed> $options */
    public static function product(string $title, ?string $description, array $product, array $options = []): SeoPagePresetOutputDTO
    {
        self::assertTitle($title);
        $canonical = self::canonical($options);
        $name = self::requireString($product, 'name', 'product.name');
        $builder = (new ProductJsonLdBuilder())->setName($name);
        if ($description !== null && $description !== '') { $builder->setDescription($description); }
        if ($canonical !== null && $canonical !== '') { $builder->setUrl($canonical); }
        foreach (['sku', 'brand', 'category', 'currency', 'availability', 'condition'] as $key) {
            if (isset($product[$key])) {
                $value = self::expectString($product[$key], 'product.' . $key);
                match ($key) {
                    'sku' => $builder->setSku($value),
                    'brand' => $builder->setBrand($value),
                    'category' => $builder->setCategory($value),
                    'currency' => $builder->setCurrency($value),
                    'availability' => $builder->setAvailability($value),
                    'condition' => $builder->setCondition($value),
                };
            }
        }
        if (isset($product['price'])) {
            if (!is_string($product['price']) && !is_int($product['price']) && !is_float($product['price'])) {
                throw SeoInvalidArgumentException::invalidValue('product.price', 'Expected string, integer, or float price.');
            }
            $builder->setPrice($product['price']);
        }
        if (isset($product['image'])) {
            if (!is_string($product['image']) && !is_array($product['image'])) {
                throw SeoInvalidArgumentException::invalidValue('product.image', 'Expected image URL string or list of image URL strings.');
            }
            $builder->setImage($product['image']);
        }

        return self::build($title, $description, $canonical, self::robots($options), [new JsonLdSchemaDTO($builder->toArray())], $options, 'product');
    }

    /** @param list<array<string, mixed>|string> $items @param array<string, mixed> $options */
    public static function category(string $title, ?string $description = null, array $items = [], array $options = []): SeoPagePresetOutputDTO
    {
        self::assertTitle($title);
        $canonical = self::canonical($options);
        $builder = (new ItemListJsonLdBuilder())->setName($title);
        if ($description !== null && $description !== '') { $builder->setDescription($description); }
        foreach ($items as $item) {
            if (is_string($item)) { $builder->addItem($item); continue; }
            if (!is_array($item)) { throw SeoInvalidArgumentException::invalidValue('items', 'Each category item must be a URL string or associative array.'); }
            $url = $item['url'] ?? $item['item'] ?? null;
            if (!is_string($url) || $url === '') { throw SeoInvalidArgumentException::emptyField('items.url'); }
            $name = isset($item['name']) && is_string($item['name']) ? $item['name'] : null;
            $builder->addItem($url, $name);
        }

        return self::build($title, $description, $canonical, self::robots($options), [new JsonLdSchemaDTO($builder->toArray())], $options, 'website');
    }

    /** @param array<string, mixed> $article @param array<string, mixed> $options */
    public static function article(string $title, ?string $description, array $article, array $options = []): SeoPagePresetOutputDTO
    {
        self::assertTitle($title);
        $author = self::requireString($article, 'author', 'article.author');
        $datePublished = self::requireString($article, 'datePublished', 'article.datePublished');
        $canonical = self::canonical($options);
        $type = isset($article['type']) && is_string($article['type']) ? $article['type'] : 'Article';
        $builder = (new ArticleJsonLdBuilder($type))->setHeadline($title)->setAuthor($author)->setDatePublished($datePublished);
        if ($description !== null && $description !== '') { $builder->setDescription($description); }
        if ($canonical !== null && $canonical !== '') { $builder->setUrl($canonical)->setMainEntityOfPage($canonical); }
        if (isset($article['publisher'])) { $builder->setPublisher($article['publisher']); }
        if (isset($article['dateModified']) && is_string($article['dateModified'])) { $builder->setDateModified($article['dateModified']); }
        if (isset($article['image'])) { $builder->setImage($article['image']); }
        if (isset($article['section']) && is_string($article['section'])) { $builder->setArticleSection($article['section']); }

        return self::build($title, $description, $canonical, self::robots($options), [new JsonLdSchemaDTO($builder->toArray())], $options, 'article');
    }

    /** @param array<string, mixed> $options */
    public static function home(string $title, ?string $description = null, array $options = []): SeoPagePresetOutputDTO
    {
        self::assertTitle($title);
        $canonical = self::canonical($options);
        $site = (new WebSiteJsonLdBuilder())->setName(isset($options['siteName']) && is_string($options['siteName']) ? $options['siteName'] : $title);
        if ($canonical !== null && $canonical !== '') { $site->setUrl($canonical); }
        if ($description !== null && $description !== '') { $site->setDescription($description); }

        return self::build($title, $description, $canonical, self::robots($options), [new JsonLdSchemaDTO($site->toArray())], $options, 'website');
    }

    /** @param list<array{name: string, url: string}> $breadcrumbs @param array<string, mixed> $options */
    public static function breadcrumb(string $title, ?string $description, array $breadcrumbs, array $options = []): SeoPagePresetOutputDTO
    {
        $options['breadcrumbs'] = $breadcrumbs;
        return self::generic($title, $description, $options);
    }

    /** @param list<JsonLdSchemaDTO|array<string, mixed>> $baseSchemas @param array<string, mixed> $options */
    private static function build(string $title, ?string $description, ?string $canonical, string $robots, array $baseSchemas, array $options, string $defaultOgType): SeoPagePresetOutputDTO
    {
        $schemas = array_merge($baseSchemas, self::breadcrumbSchemas($options), self::extraSchemas($options));
        $social = self::social($title, $description, $canonical, $options, $defaultOgType);
        $meta = (new FluentSeoBuilder())->title($title)->description($description)->canonical($canonical)->robots($robots)
            ->openGraphTitle($title)->openGraphDescription($description)->openGraphUrl($canonical)->openGraphType(self::stringOption($options, 'openGraphType') ?? $defaultOgType)
            ->openGraphImage(self::stringOption($options, 'imageUrl'))->twitterTitle($title)->twitterDescription($description)->twitterCard(self::stringOption($options, 'twitterCard') ?? 'summary_large_image')->twitterImage(self::stringOption($options, 'imageUrl'))->buildMetaTags();
        return new SeoPagePresetOutputDTO($meta, $canonical, $robots, $social->toArray(), $social->toHtml(), $schemas, (new SeoHeadHtmlRenderer())->render($meta, $schemas));
    }

    /** @param array<string, mixed> $options */
    private static function canonical(array $options): ?string
    {
        if (isset($options['canonicalUrl'])) { return self::expectString($options['canonicalUrl'], 'canonicalUrl'); }
        if (!isset($options['canonicalBaseUrl']) && !isset($options['canonicalPath'])) { return null; }
        $builder = new CanonicalUrlBuilder(isset($options['canonicalBaseUrl']) ? self::expectString($options['canonicalBaseUrl'], 'canonicalBaseUrl') : null);
        if (isset($options['canonicalPath'])) { $builder->setPath(self::expectString($options['canonicalPath'], 'canonicalPath')); }
        if (isset($options['queryParams'])) { if (!is_array($options['queryParams'])) { throw SeoInvalidArgumentException::invalidValue('queryParams', 'Expected associative query parameter array.'); } $builder->setQueryParams($options['queryParams']); }
        if (isset($options['allowedQueryParams'])) { if (!is_array($options['allowedQueryParams']) || !array_is_list($options['allowedQueryParams'])) { throw SeoInvalidArgumentException::invalidValue('allowedQueryParams', 'Expected list of query parameter names.'); } $builder->preserveQueryParams($options['allowedQueryParams']); }
        return $builder->build();
    }

    /** @param array<string, mixed> $options */
    private static function robots(array $options): string
    {
        $robots = $options['robots'] ?? null;
        if ($robots instanceof MetaRobotsBuilder) { return $robots->build(); }
        if (is_array($robots)) { $builder = new MetaRobotsBuilder(); foreach ($robots as $directive) { if (!is_string($directive) || $directive === '') { throw SeoInvalidArgumentException::invalidValue('robots', 'Expected list of non-empty directive strings.'); } $builder->add($directive); } return $builder->build(); }
        if (is_string($robots) && $robots !== '') { return $robots; }
        return (new MetaRobotsBuilder())->index()->follow()->build();
    }

    /** @param array<string, mixed> $options */
    private static function social(string $title, ?string $description, ?string $canonical, array $options, string $type): SocialPreviewBuilder
    {
        $social = (new SocialPreviewBuilder())->setTitle($title)->setTwitterCard(self::stringOption($options, 'twitterCard') ?? 'summary_large_image');
        if ($description !== null && $description !== '') { $social->setDescription($description); }
        if ($canonical !== null && $canonical !== '') { $social->setUrl($canonical); }
        if (($image = self::stringOption($options, 'imageUrl')) !== null) { $social->setImage($image); }
        if (($site = self::stringOption($options, 'siteName')) !== null) { $social->setSiteName($site); }
        if (($locale = self::stringOption($options, 'locale')) !== null) { $social->setLocale($locale); }
        if (($twitterSite = self::stringOption($options, 'twitterSite')) !== null) { $social->setTwitterSite($twitterSite); }
        if (($twitterCreator = self::stringOption($options, 'twitterCreator')) !== null) { $social->setTwitterCreator($twitterCreator); }
        $social->openGraph()->setType(self::stringOption($options, 'openGraphType') ?? $type);
        return $social;
    }

    /** @param array<string, mixed> $options */
    private static function webPageSchema(string $title, ?string $description, ?string $canonical, array $options): JsonLdSchemaDTO
    {
        $builder = (new WebPageJsonLdBuilder())->setName($title);
        if ($description !== null && $description !== '') { $builder->setDescription($description); }
        if ($canonical !== null && $canonical !== '') { $builder->setUrl($canonical); }
        if (($image = self::stringOption($options, 'imageUrl')) !== null) { $builder->setPrimaryImageOfPage($image); }
        return new JsonLdSchemaDTO($builder->toArray());
    }

    /** @param array<string, mixed> $options @return list<JsonLdSchemaDTO> */
    private static function breadcrumbSchemas(array $options): array
    {
        if (!isset($options['breadcrumbs'])) { return []; }
        if (!is_array($options['breadcrumbs']) || !array_is_list($options['breadcrumbs'])) { throw SeoInvalidArgumentException::invalidValue('breadcrumbs', 'Expected list of arrays with name and url.'); }
        $builder = new BreadcrumbJsonLdBuilder();
        foreach ($options['breadcrumbs'] as $item) { if (!is_array($item) || !isset($item['name'], $item['url']) || !is_string($item['name']) || !is_string($item['url']) || $item['name'] === '' || $item['url'] === '') { throw SeoInvalidArgumentException::invalidValue('breadcrumbs', 'Each breadcrumb must contain non-empty string name and url.'); } $builder->addItem($item['name'], $item['url']); }
        return [new JsonLdSchemaDTO($builder->toArray())];
    }

    /** @param array<string, mixed> $options @return list<JsonLdSchemaDTO> */
    private static function extraSchemas(array $options): array
    {
        if (!isset($options['extraSchemas'])) { return []; }
        if (!is_array($options['extraSchemas']) || !array_is_list($options['extraSchemas'])) { throw SeoInvalidArgumentException::invalidSchemaEntry('extraSchemas'); }
        $schemas = [];
        foreach ($options['extraSchemas'] as $index => $schema) { if ($schema instanceof JsonLdSchemaDTO) { $schemas[] = $schema; continue; } if (!is_array($schema) || $schema === [] || array_is_list($schema)) { throw SeoInvalidArgumentException::invalidSchemaEntry('extraSchemas[' . $index . ']'); } $schemas[] = new JsonLdSchemaDTO($schema); }
        return $schemas;
    }

    private static function assertTitle(string $title): void { if ($title === '') { throw SeoInvalidArgumentException::emptyField('title'); } }
    /** @param array<string, mixed> $data */
    private static function requireString(array $data, string $key, string $field): string
    {
        if (!isset($data[$key]) || !is_string($data[$key]) || $data[$key] === '') {
            throw SeoInvalidArgumentException::emptyField($field);
        }

        return $data[$key];
    }
    private static function expectString(mixed $value, string $field): string { if (!is_string($value) || $value === '') { throw SeoInvalidArgumentException::emptyField($field); } return $value; }
    /** @param array<string, mixed> $options */ private static function stringOption(array $options, string $key): ?string { return isset($options[$key]) && is_string($options[$key]) && $options[$key] !== '' ? $options[$key] : null; }
}
