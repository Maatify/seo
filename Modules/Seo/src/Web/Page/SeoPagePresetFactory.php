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

    /**
     * @param array<string, mixed> $product
     * @param array<string, mixed> $options
     */
    public static function product(string $title, ?string $description, array $product, array $options = []): SeoPagePresetOutputDTO
    {
        self::assertTitle($title);

        $canonical = self::canonical($options);
        $builder = (new ProductJsonLdBuilder())->setName(self::requireString($product, 'name', 'product.name'));

        if ($description !== null && $description !== '') {
            $builder->setDescription($description);
        }

        if ($canonical !== null && $canonical !== '') {
            $builder->setUrl($canonical);
        }

        if (array_key_exists('sku', $product)) {
            $builder->setSku(self::expectString($product['sku'], 'product.sku'));
        }

        if (array_key_exists('brand', $product)) {
            $builder->setBrand(self::expectString($product['brand'], 'product.brand'));
        }

        if (array_key_exists('category', $product)) {
            $builder->setCategory(self::expectString($product['category'], 'product.category'));
        }

        if (array_key_exists('currency', $product)) {
            $builder->setCurrency(self::expectString($product['currency'], 'product.currency'));
        }

        if (array_key_exists('price', $product)) {
            $builder->setPrice(self::normalizePrice($product['price'], 'product.price'));
        }

        if (array_key_exists('availability', $product)) {
            $builder->setAvailability(self::expectString($product['availability'], 'product.availability'));
        }

        if (array_key_exists('condition', $product)) {
            $builder->setCondition(self::expectString($product['condition'], 'product.condition'));
        }

        if (array_key_exists('image', $product)) {
            $builder->setImage(self::normalizeImageValue($product['image'], 'product.image'));
        }

        return self::build(
            $title,
            $description,
            $canonical,
            self::robots($options),
            [new JsonLdSchemaDTO($builder->toArray())],
            $options,
            'product'
        );
    }

    /**
     * @param list<array<string, mixed>|string> $items
     * @param array<string, mixed> $options
     */
    public static function category(string $title, ?string $description = null, array $items = [], array $options = []): SeoPagePresetOutputDTO
    {
        self::assertTitle($title);

        $canonical = self::canonical($options);
        $builder = (new ItemListJsonLdBuilder())->setName($title);

        if ($description !== null && $description !== '') {
            $builder->setDescription($description);
        }

        foreach ($items as $item) {
            if (is_string($item)) {
                $builder->addItem($item);
                continue;
            }

            $url = $item['url'] ?? $item['item'] ?? null;
            if (!is_string($url) || $url === '') {
                throw SeoInvalidArgumentException::emptyField('items.url');
            }

            $name = isset($item['name']) && is_string($item['name']) ? $item['name'] : null;
            $builder->addItem($url, $name);
        }

        return self::build(
            $title,
            $description,
            $canonical,
            self::robots($options),
            [new JsonLdSchemaDTO($builder->toArray())],
            $options,
            'website'
        );
    }

    /**
     * @param array<string, mixed> $article
     * @param array<string, mixed> $options
     */
    public static function article(string $title, ?string $description, array $article, array $options = []): SeoPagePresetOutputDTO
    {
        self::assertTitle($title);

        $author = self::requireString($article, 'author', 'article.author');
        $datePublished = self::requireString($article, 'datePublished', 'article.datePublished');
        $canonical = self::canonical($options);
        $type = array_key_exists('type', $article)
            ? self::expectString($article['type'], 'article.type')
            : 'Article';

        $builder = (new ArticleJsonLdBuilder($type))
            ->setHeadline($title)
            ->setAuthor($author)
            ->setDatePublished($datePublished);

        if ($description !== null && $description !== '') {
            $builder->setDescription($description);
        }

        if ($canonical !== null && $canonical !== '') {
            $builder->setUrl($canonical)->setMainEntityOfPage($canonical);
        }

        if (array_key_exists('publisher', $article)) {
            $builder->setPublisher(self::normalizeStringOrAssociativeArray($article['publisher'], 'article.publisher'));
        }

        if (array_key_exists('dateModified', $article)) {
            $builder->setDateModified(self::expectString($article['dateModified'], 'article.dateModified'));
        }

        if (array_key_exists('image', $article)) {
            $builder->setImage(self::normalizeImageValue($article['image'], 'article.image'));
        }

        if (array_key_exists('section', $article)) {
            $builder->setArticleSection(self::expectString($article['section'], 'article.section'));
        }

        return self::build(
            $title,
            $description,
            $canonical,
            self::robots($options),
            [new JsonLdSchemaDTO($builder->toArray())],
            $options,
            'article'
        );
    }

    /** @param array<string, mixed> $options */
    public static function home(string $title, ?string $description = null, array $options = []): SeoPagePresetOutputDTO
    {
        self::assertTitle($title);

        $canonical = self::canonical($options);
        $siteName = self::stringOption($options, 'siteName') ?? $title;
        $builder = (new WebSiteJsonLdBuilder())->setName($siteName);

        if ($canonical !== null && $canonical !== '') {
            $builder->setUrl($canonical);
        }

        if ($description !== null && $description !== '') {
            $builder->setDescription($description);
        }

        return self::build(
            $title,
            $description,
            $canonical,
            self::robots($options),
            [new JsonLdSchemaDTO($builder->toArray())],
            $options,
            'website'
        );
    }

    /**
     * @param list<array{name: string, url: string}> $breadcrumbs
     * @param array<string, mixed> $options
     */
    public static function breadcrumb(string $title, ?string $description, array $breadcrumbs, array $options = []): SeoPagePresetOutputDTO
    {
        $options['breadcrumbs'] = $breadcrumbs;

        return self::generic($title, $description, $options);
    }

    /**
     * @param list<JsonLdSchemaDTO> $baseSchemas
     * @param array<string, mixed> $options
     */
    private static function build(
        string $title,
        ?string $description,
        ?string $canonical,
        string $robots,
        array $baseSchemas,
        array $options,
        string $defaultOgType,
    ): SeoPagePresetOutputDTO {
        $schemas = array_merge($baseSchemas, self::breadcrumbSchemas($options), self::extraSchemas($options));
        $social = self::social($title, $description, $canonical, $options, $defaultOgType);
        $imageUrl = self::stringOption($options, 'imageUrl');
        $twitterCard = self::stringOption($options, 'twitterCard') ?? 'summary_large_image';
        $openGraphType = self::stringOption($options, 'openGraphType') ?? $defaultOgType;

        $meta = (new FluentSeoBuilder())
            ->title($title)
            ->description($description)
            ->canonical($canonical)
            ->robots($robots)
            ->openGraphTitle($title)
            ->openGraphDescription($description)
            ->openGraphUrl($canonical)
            ->openGraphType($openGraphType)
            ->openGraphImage($imageUrl)
            ->twitterTitle($title)
            ->twitterDescription($description)
            ->twitterCard($twitterCard)
            ->twitterImage($imageUrl)
            ->buildMetaTags();

        return new SeoPagePresetOutputDTO(
            metaTags: $meta,
            canonicalUrl: $canonical,
            robots: $robots,
            socialTags: $social->toArray(),
            socialHtml: $social->toHtml(),
            schemas: $schemas,
            html: (new SeoHeadHtmlRenderer())->render($meta, $schemas),
        );
    }

    /** @param array<string, mixed> $options */
    private static function canonical(array $options): ?string
    {
        if (array_key_exists('canonicalUrl', $options)) {
            return self::expectString($options['canonicalUrl'], 'canonicalUrl');
        }

        if (!array_key_exists('canonicalBaseUrl', $options) && !array_key_exists('canonicalPath', $options)) {
            return null;
        }

        $baseUrl = array_key_exists('canonicalBaseUrl', $options)
            ? self::expectString($options['canonicalBaseUrl'], 'canonicalBaseUrl')
            : null;

        $builder = new CanonicalUrlBuilder($baseUrl);

        if (array_key_exists('canonicalPath', $options)) {
            $builder->setPath(self::expectString($options['canonicalPath'], 'canonicalPath'));
        }

        if (array_key_exists('queryParams', $options)) {
            $builder->setQueryParams(self::normalizeQueryParams($options['queryParams']));
        }

        if (array_key_exists('allowedQueryParams', $options)) {
            $builder->preserveQueryParams(self::normalizeStringList($options['allowedQueryParams'], 'allowedQueryParams'));
        }

        return $builder->build();
    }

    /** @param array<string, mixed> $options */
    private static function robots(array $options): string
    {
        $robots = $options['robots'] ?? null;

        if ($robots instanceof MetaRobotsBuilder) {
            return $robots->build();
        }

        if (is_array($robots)) {
            $builder = new MetaRobotsBuilder();
            foreach (self::normalizeStringList($robots, 'robots') as $directive) {
                $builder->add($directive);
            }

            return $builder->build();
        }

        if (is_string($robots) && $robots !== '') {
            return $robots;
        }

        return (new MetaRobotsBuilder())->index()->follow()->build();
    }

    /** @param array<string, mixed> $options */
    private static function social(string $title, ?string $description, ?string $canonical, array $options, string $type): SocialPreviewBuilder
    {
        $social = (new SocialPreviewBuilder())
            ->setTitle($title)
            ->setTwitterCard(self::stringOption($options, 'twitterCard') ?? 'summary_large_image');

        if ($description !== null && $description !== '') {
            $social->setDescription($description);
        }

        if ($canonical !== null && $canonical !== '') {
            $social->setUrl($canonical);
        }

        $imageUrl = self::stringOption($options, 'imageUrl');
        if ($imageUrl !== null) {
            $social->setImage($imageUrl);
        }

        $siteName = self::stringOption($options, 'siteName');
        if ($siteName !== null) {
            $social->setSiteName($siteName);
        }

        $locale = self::stringOption($options, 'locale');
        if ($locale !== null) {
            $social->setLocale($locale);
        }

        $twitterSite = self::stringOption($options, 'twitterSite');
        if ($twitterSite !== null) {
            $social->setTwitterSite($twitterSite);
        }

        $twitterCreator = self::stringOption($options, 'twitterCreator');
        if ($twitterCreator !== null) {
            $social->setTwitterCreator($twitterCreator);
        }

        $social->openGraph()->setType(self::stringOption($options, 'openGraphType') ?? $type);

        return $social;
    }

    /** @param array<string, mixed> $options */
    private static function webPageSchema(string $title, ?string $description, ?string $canonical, array $options): JsonLdSchemaDTO
    {
        $builder = (new WebPageJsonLdBuilder())->setName($title);

        if ($description !== null && $description !== '') {
            $builder->setDescription($description);
        }

        if ($canonical !== null && $canonical !== '') {
            $builder->setUrl($canonical);
        }

        $imageUrl = self::stringOption($options, 'imageUrl');
        if ($imageUrl !== null) {
            $builder->setPrimaryImageOfPage($imageUrl);
        }

        return new JsonLdSchemaDTO($builder->toArray());
    }

    /**
     * @param array<string, mixed> $options
     * @return list<JsonLdSchemaDTO>
     */
    private static function breadcrumbSchemas(array $options): array
    {
        if (!array_key_exists('breadcrumbs', $options)) {
            return [];
        }

        if (!is_array($options['breadcrumbs']) || !array_is_list($options['breadcrumbs'])) {
            throw SeoInvalidArgumentException::invalidValue('breadcrumbs', 'Expected list of arrays with name and url.');
        }

        $builder = new BreadcrumbJsonLdBuilder();
        foreach ($options['breadcrumbs'] as $item) {
            if (!is_array($item)) {
                throw SeoInvalidArgumentException::invalidValue('breadcrumbs', 'Each breadcrumb must contain non-empty string name and url.');
            }

            $name = $item['name'] ?? null;
            $url = $item['url'] ?? null;
            if (!is_string($name) || $name === '' || !is_string($url) || $url === '') {
                throw SeoInvalidArgumentException::invalidValue('breadcrumbs', 'Each breadcrumb must contain non-empty string name and url.');
            }

            $builder->addItem($name, $url);
        }

        return [new JsonLdSchemaDTO($builder->toArray())];
    }

    /**
     * @param array<string, mixed> $options
     * @return list<JsonLdSchemaDTO>
     */
    private static function extraSchemas(array $options): array
    {
        if (!array_key_exists('extraSchemas', $options)) {
            return [];
        }

        if (!is_array($options['extraSchemas']) || !array_is_list($options['extraSchemas'])) {
            throw SeoInvalidArgumentException::invalidSchemaEntry('extraSchemas');
        }

        $schemas = [];
        foreach ($options['extraSchemas'] as $index => $schema) {
            if ($schema instanceof JsonLdSchemaDTO) {
                $schemas[] = $schema;
                continue;
            }

            if (!is_array($schema) || $schema === [] || array_is_list($schema)) {
                throw SeoInvalidArgumentException::invalidSchemaEntry('extraSchemas[' . $index . ']');
            }

            $schemas[] = new JsonLdSchemaDTO(self::normalizeAssociativeArray($schema, 'extraSchemas[' . $index . ']'));
        }

        return $schemas;
    }

    private static function assertTitle(string $title): void
    {
        if ($title === '') {
            throw SeoInvalidArgumentException::emptyField('title');
        }
    }

    /** @param array<string, mixed> $data */
    private static function requireString(array $data, string $key, string $field): string
    {
        if (!array_key_exists($key, $data)) {
            throw SeoInvalidArgumentException::emptyField($field);
        }

        return self::expectString($data[$key], $field);
    }

    private static function expectString(mixed $value, string $field): string
    {
        if (!is_string($value) || $value === '') {
            throw SeoInvalidArgumentException::emptyField($field);
        }

        return $value;
    }

    /** @param array<string, mixed> $options */
    private static function stringOption(array $options, string $key): ?string
    {
        if (!array_key_exists($key, $options)) {
            return null;
        }

        return self::expectString($options[$key], $key);
    }

    private static function normalizePrice(mixed $value, string $field): string|int|float
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            throw SeoInvalidArgumentException::invalidValue($field, 'Expected string, integer, or float price.');
        }

        if (is_string($value) && $value === '') {
            throw SeoInvalidArgumentException::emptyField($field);
        }

        return $value;
    }

    /** @return string|list<string> */
    private static function normalizeImageValue(mixed $value, string $field): string|array
    {
        if (is_string($value)) {
            if ($value === '') {
                throw SeoInvalidArgumentException::emptyField($field);
            }

            return $value;
        }

        $images = self::normalizeStringList($value, $field);
        if ($images === []) {
            throw SeoInvalidArgumentException::invalidValue($field, 'Expected at least one image URL.');
        }

        return $images;
    }

    /** @return string|array<string, mixed> */
    private static function normalizeStringOrAssociativeArray(mixed $value, string $field): string|array
    {
        if (is_string($value)) {
            if ($value === '') {
                throw SeoInvalidArgumentException::emptyField($field);
            }

            return $value;
        }

        if (!is_array($value) || $value === [] || array_is_list($value)) {
            throw SeoInvalidArgumentException::invalidValue($field, 'Expected non-empty string or associative array.');
        }

        return self::normalizeAssociativeArray($value, $field);
    }

    /** @return array<string, mixed> */
    private static function normalizeAssociativeArray(mixed $value, string $field): array
    {
        if (!is_array($value) || $value === [] || array_is_list($value)) {
            throw SeoInvalidArgumentException::invalidSchemaEntry($field);
        }

        $normalized = [];
        foreach ($value as $key => $item) {
            if (!is_string($key) || $key === '') {
                throw SeoInvalidArgumentException::invalidValue($field, 'Expected associative array with non-empty string keys.');
            }

            $normalized[$key] = $item;
        }

        return $normalized;
    }

    /** @return list<string> */
    private static function normalizeStringList(mixed $value, string $field): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw SeoInvalidArgumentException::invalidValue($field, 'Expected a list of strings.');
        }

        $normalized = [];
        foreach ($value as $item) {
            if (!is_string($item) || $item === '') {
                throw SeoInvalidArgumentException::invalidValue($field, 'Expected a list of non-empty strings.');
            }

            $normalized[] = $item;
        }

        return $normalized;
    }

    /** @return array<string, string|int|float|bool|null> */
    private static function normalizeQueryParams(mixed $value): array
    {
        if (!is_array($value)) {
            throw SeoInvalidArgumentException::invalidValue('queryParams', 'Expected associative query parameter array.');
        }

        if ($value === []) {
            return [];
        }

        if (array_is_list($value)) {
            throw SeoInvalidArgumentException::invalidValue('queryParams', 'Expected associative query parameter array.');
        }

        $normalized = [];
        foreach ($value as $key => $item) {
            if (!is_string($key) || $key === '') {
                throw SeoInvalidArgumentException::invalidValue('queryParams', 'Expected non-empty string query parameter names.');
            }

            if (!is_string($item) && !is_int($item) && !is_float($item) && !is_bool($item) && $item !== null) {
                throw SeoInvalidArgumentException::invalidValue('queryParams', 'Expected scalar string, integer, float, boolean, or null values.');
            }

            $normalized[$key] = $item;
        }

        return $normalized;
    }
}
