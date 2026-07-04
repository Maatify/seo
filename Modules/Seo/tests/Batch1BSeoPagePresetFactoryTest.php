<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'Maatify\\Seo\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $path = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

use Maatify\Seo\Exception\SeoInvalidArgumentException;
use Maatify\Seo\Shared\DTO\MetaTagsDTO;
use Maatify\Seo\Shared\DTO\Schema\JsonLdSchemaDTO;
use Maatify\Seo\Web\Page\SeoPagePresetFactory;
use Maatify\Seo\Web\Robots\MetaRobotsBuilder;

$failures = 0;

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    global $failures;
    if ($expected !== $actual) {
        $failures++;
        echo "FAIL: $message\n";
        echo '  Expected: ' . print_r($expected, true) . "\n";
        echo '  Actual:   ' . print_r($actual, true) . "\n";
    }
}

function assertTrueValue(bool $actual, string $message): void
{
    assertSameValue(true, $actual, $message);
}

function assertContainsValue(string $needle, string $haystack, string $message): void
{
    assertTrueValue(str_contains($haystack, $needle), $message);
}

function assertThrowsSeoInvalidArgument(callable $callback, string $message): void
{
    global $failures;
    try {
        $callback();
    } catch (SeoInvalidArgumentException) {
        return;
    }

    $failures++;
    echo "FAIL: $message\n  Expected SeoInvalidArgumentException.\n";
}

echo "Running Batch 1B SEO Page Preset Factory Tests...\n\n";

$generic = SeoPagePresetFactory::generic('About Us', 'About our company', [
    'canonicalBaseUrl' => 'https://example.com',
    'canonicalPath' => '/about',
    'queryParams' => ['utm_source' => 'x', 'page' => 2],
    'allowedQueryParams' => ['page'],
    'robots' => ['index', 'follow', 'max-image-preview:large'],
    'imageUrl' => 'https://example.com/about.jpg',
    'siteName' => 'Example',
    'locale' => 'en_US',
    'twitterSite' => '@example',
]);

assertTrueValue($generic->metaTags instanceof MetaTagsDTO, 'Generic preset builds MetaTagsDTO');
assertSameValue('https://example.com/about?page=2', $generic->canonicalUrl, 'Generic preset uses CanonicalUrlBuilder behavior');
assertSameValue('index, follow, max-image-preview:large', $generic->robots, 'Robots array uses MetaRobotsBuilder output');
assertContainsValue('property="og:title"', $generic->socialHtml, 'Social preview includes Open Graph fields');
assertContainsValue('name="twitter:card"', $generic->socialHtml, 'Social preview includes Twitter fields');
assertContainsValue('<link rel="canonical" href="https://example.com/about?page=2">', $generic->html, 'Full HTML includes canonical link');

$robotsBuilder = (new MetaRobotsBuilder())->noIndex()->noFollow();
$noIndex = SeoPagePresetFactory::generic('Private', null, ['robots' => $robotsBuilder]);
assertSameValue('noindex, nofollow', $noIndex->robots, 'Robots builder instance output is used');

$extra = new JsonLdSchemaDTO(['@context' => 'https://schema.org', '@type' => 'Thing', 'name' => 'Extra']);
$product = SeoPagePresetFactory::product('Blue Shirt', 'Cotton shirt', [
    'name' => 'Blue Shirt',
    'sku' => 'SKU-1',
    'brand' => 'Example Brand',
    'price' => '29.99',
    'currency' => 'USD',
], ['canonicalUrl' => 'https://example.com/products/blue-shirt', 'extraSchemas' => [$extra]]);
$productSchemas = $product->toArray()['schemas'];
assertSameValue('Product', $productSchemas[0]['@type'], 'Product preset includes Product JSON-LD schema');
assertSameValue('Thing', $productSchemas[1]['@type'], 'Extra schemas are preserved');

$category = SeoPagePresetFactory::category('Shirts', 'All shirts', [
    ['url' => 'https://example.com/products/blue-shirt', 'name' => 'Blue Shirt'],
]);
assertSameValue('ItemList', $category->toArray()['schemas'][0]['@type'], 'Category preset includes ItemList JSON-LD schema');

$article = SeoPagePresetFactory::article('Launch News', 'Product launch', [
    'author' => 'Jane Doe',
    'datePublished' => '2026-07-04',
    'publisher' => 'Example',
], ['canonicalUrl' => 'https://example.com/news/launch']);
assertSameValue('Article', $article->toArray()['schemas'][0]['@type'], 'Article preset includes Article JSON-LD schema');

$home = SeoPagePresetFactory::home('Example', 'Homepage', ['canonicalUrl' => 'https://example.com']);
assertSameValue('WebSite', $home->toArray()['schemas'][0]['@type'], 'Home preset includes WebSite JSON-LD schema');

$breadcrumb = SeoPagePresetFactory::breadcrumb('Blue Shirt', 'Cotton shirt', [
    ['name' => 'Home', 'url' => 'https://example.com'],
    ['name' => 'Shirts', 'url' => 'https://example.com/shirts'],
]);
assertSameValue('BreadcrumbList', $breadcrumb->toArray()['schemas'][1]['@type'], 'Breadcrumb-enabled preset includes BreadcrumbList JSON-LD schema');

assertThrowsSeoInvalidArgument(static fn () => SeoPagePresetFactory::generic('', 'Missing title'), 'Empty title is rejected');
assertThrowsSeoInvalidArgument(static fn () => SeoPagePresetFactory::product('Bad Product', null, []), 'Broken product required data is rejected');
assertThrowsSeoInvalidArgument(static fn () => SeoPagePresetFactory::article('Bad Article', null, ['author' => 'Jane']), 'Broken article required data is rejected');
assertThrowsSeoInvalidArgument(static fn () => SeoPagePresetFactory::breadcrumb('Bad Breadcrumb', null, [['name' => 'Home']]), 'Invalid breadcrumb shape is rejected');
assertThrowsSeoInvalidArgument(static fn () => SeoPagePresetFactory::generic('Bad Query', null, ['canonicalBaseUrl' => 'https://example.com', 'queryParams' => ['nested' => ['bad']]]), 'Invalid canonical query parameter values are rejected');
assertThrowsSeoInvalidArgument(static fn () => SeoPagePresetFactory::generic('Bad Allowed Query', null, ['canonicalBaseUrl' => 'https://example.com', 'allowedQueryParams' => ['page', 1]]), 'Invalid allowed query parameter lists are rejected');
assertThrowsSeoInvalidArgument(static fn () => SeoPagePresetFactory::product('Bad Image Product', null, ['name' => 'Product', 'image' => ['ok.jpg', 123]]), 'Invalid product image lists are rejected');
assertThrowsSeoInvalidArgument(static fn () => SeoPagePresetFactory::article('Bad Publisher', null, ['author' => 'Jane', 'datePublished' => '2026-07-04', 'publisher' => []]), 'Invalid article publisher data is rejected');
assertThrowsSeoInvalidArgument(static fn () => SeoPagePresetFactory::article('Bad Article Image', null, ['author' => 'Jane', 'datePublished' => '2026-07-04', 'image' => ['ok.jpg', 123]]), 'Invalid article image lists are rejected');

assertTrueValue(!str_contains($generic->html, 'Illuminate\\') && !str_contains($generic->html, 'Symfony\\') && !str_contains($generic->html, 'Response'), 'Preset output has no framework or HTTP coupling');

echo "\n";
if ($failures > 0) {
    echo "FAILED with $failures errors.\n";
    exit(1);
}

echo "SUCCESS: All tests passed.\n";
exit(0);
