<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'Maatify\\Seo\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $path = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

use Maatify\Seo\Exception\SeoExceptionInterface;
use Maatify\Seo\Exception\SeoInvalidArgumentException;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapAlternateUrlDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapImageDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapUrlDTO;
use Maatify\Seo\Web\Sitemap\SitemapXmlStringRenderer;

function assertSameValue10C(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertTrueValue10C(string $label, bool $actual): void
{
    if (!$actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertThrowsSeoException10C(string $label, callable $callback): void
{
    try {
        $callback();
    } catch (SeoExceptionInterface $exception) {
        assertTrueValue10C($label . ' uses invalid argument exception', $exception instanceof SeoInvalidArgumentException);
        return;
    } catch (RuntimeException $exception) {
        fwrite(STDERR, "Assertion failed: {$label}\nUnexpected raw RuntimeException: {$exception->getMessage()}\n");
        exit(1);
    }

    fwrite(STDERR, "Assertion failed: {$label}\nExpected SEO module exception.\n");
    exit(1);
}

$renderer = new SitemapXmlStringRenderer();
$xmlHeader = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

$dtoUrl = new SitemapUrlDTO(
    loc: 'https://example.com/product',
    images: [
        new SitemapImageDTO(
            loc: 'https://cdn.example.com/products/product-1.jpg',
            title: 'Product 1',
            caption: 'Product 1 image',
            geoLocation: 'Cairo, Egypt',
            license: 'https://example.com/license',
        ),
    ],
);

$arrayUrl = [
    'loc' => 'https://example.com/category',
    'images' => [
        [
            'loc' => 'https://cdn.example.com/categories/category-1.jpg',
            'title' => 'Category 1',
            'caption' => 'Category 1 image',
        ],
    ],
];

assertSameValue10C(
    'DTO URL entry renders image sitemap tags with local namespace',
    $xmlHeader
    . '<url xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"><loc>https://example.com/product</loc><image:image><image:loc>https://cdn.example.com/products/product-1.jpg</image:loc><image:title>Product 1</image:title><image:caption>Product 1 image</image:caption><image:geo_location>Cairo, Egypt</image:geo_location><image:license>https://example.com/license</image:license></image:image></url>' . "\n",
    $renderer->renderUrlEntry($dtoUrl),
);

assertSameValue10C(
    'array URL entry renders image sitemap tags with local namespace',
    $xmlHeader
    . '<url xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"><loc>https://example.com/category</loc><image:image><image:loc>https://cdn.example.com/categories/category-1.jpg</image:loc><image:title>Category 1</image:title><image:caption>Category 1 image</image:caption></image:image></url>' . "\n",
    $renderer->renderUrlEntry($arrayUrl),
);

assertSameValue10C(
    'full URL set with images declares image namespace once on urlset',
    $xmlHeader
    . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"><url><loc>https://example.com/product</loc><image:image><image:loc>https://cdn.example.com/products/product-1.jpg</image:loc><image:title>Product 1</image:title><image:caption>Product 1 image</image:caption><image:geo_location>Cairo, Egypt</image:geo_location><image:license>https://example.com/license</image:license></image:image></url><url><loc>https://example.com/category</loc><image:image><image:loc>https://cdn.example.com/categories/category-1.jpg</image:loc><image:title>Category 1</image:title><image:caption>Category 1 image</image:caption></image:image></url></urlset>' . "\n",
    $renderer->renderUrlSet([$dtoUrl, $arrayUrl]),
);

assertSameValue10C(
    'full URL set without images remains unchanged and omits image namespace',
    $xmlHeader
    . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><url><loc>https://example.com/plain</loc></url></urlset>' . "\n",
    $renderer->renderUrlSet([['loc' => 'https://example.com/plain']]),
);

$alternateImageUrl = new SitemapUrlDTO(
    loc: 'https://example.com/en/product',
    alternates: [new SitemapAlternateUrlDTO('en', 'https://example.com/en/product')],
    images: [new SitemapImageDTO('https://cdn.example.com/products/product-1.jpg', 'Product 1')],
);

assertSameValue10C(
    'URL entry with both alternates and images declares both local namespaces',
    $xmlHeader
    . '<url xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"><loc>https://example.com/en/product</loc><xhtml:link rel="alternate" hreflang="en" href="https://example.com/en/product"/><image:image><image:loc>https://cdn.example.com/products/product-1.jpg</image:loc><image:title>Product 1</image:title></image:image></url>' . "\n",
    $renderer->renderUrlEntry($alternateImageUrl),
);

assertSameValue10C(
    'URL set with both alternates and images declares xhtml and image namespaces',
    $xmlHeader
    . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"><url><loc>https://example.com/en/product</loc><xhtml:link rel="alternate" hreflang="en" href="https://example.com/en/product"/><image:image><image:loc>https://cdn.example.com/products/product-1.jpg</image:loc><image:title>Product 1</image:title></image:image></url></urlset>' . "\n",
    $renderer->renderUrlSet([$alternateImageUrl]),
);

assertSameValue10C(
    'XML special characters in image fields are escaped safely',
    $xmlHeader
    . '<url xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"><loc>https://example.com/search</loc><image:image><image:loc>https://cdn.example.com/search.jpg?x=1&amp;y=2</image:loc><image:title>Title &amp; &lt;tag&gt;</image:title><image:caption>Caption &quot;quoted&quot; &amp; safe</image:caption><image:geo_location>Cairo &amp; Giza</image:geo_location><image:license>https://example.com/license?x=1&amp;y=2</image:license></image:image></url>' . "\n",
    $renderer->renderUrlEntry([
        'loc' => 'https://example.com/search',
        'images' => [[
            'loc' => 'https://cdn.example.com/search.jpg?x=1&y=2',
            'title' => 'Title & <tag>',
            'caption' => 'Caption "quoted" & safe',
            'geoLocation' => 'Cairo & Giza',
            'license' => 'https://example.com/license?x=1&y=2',
        ]],
    ]),
);

assertThrowsSeoException10C('empty image loc throws module exception', static fn() => new SitemapImageDTO(' '));
assertThrowsSeoException10C('invalid image URL format throws module exception', static fn() => new SitemapImageDTO('not-a-url'));
assertThrowsSeoException10C('invalid image license URL throws module exception', static fn() => new SitemapImageDTO('https://cdn.example.com/image.jpg', license: 'not-a-url'));
assertThrowsSeoException10C('non-list images throws module exception', static fn() => $renderer->renderUrlEntry([
    'loc' => 'https://example.com/product',
    'images' => ['first' => ['loc' => 'https://cdn.example.com/image.jpg']],
]));
assertThrowsSeoException10C('invalid image entry shape throws module exception', static fn() => $renderer->renderUrlEntry([
    'loc' => 'https://example.com/product',
    'images' => [['https://cdn.example.com/image.jpg']],
]));
assertThrowsSeoException10C('array image with empty loc throws module exception', static fn() => $renderer->renderUrlEntry([
    'loc' => 'https://example.com/product',
    'images' => [['loc' => ' ']],
]));
assertThrowsSeoException10C('array image with invalid loc URL throws module exception', static fn() => $renderer->renderUrlEntry([
    'loc' => 'https://example.com/product',
    'images' => [['loc' => 'not-a-url']],
]));
assertThrowsSeoException10C('array image with invalid license URL throws module exception', static fn() => $renderer->renderUrlEntry([
    'loc' => 'https://example.com/product',
    'images' => [['loc' => 'https://cdn.example.com/image.jpg', 'license' => 'not-a-url']],
]));

assertSameValue10C(
    'empty optional image strings normalize away',
    $xmlHeader
    . '<url xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"><loc>https://example.com/product</loc><image:image><image:loc>https://cdn.example.com/image.jpg</image:loc></image:image></url>' . "\n",
    $renderer->renderUrlEntry([
        'loc' => 'https://example.com/product',
        'images' => [['loc' => 'https://cdn.example.com/image.jpg', 'title' => ' ', 'caption' => '']],
    ]),
);

assertTrueValue10C(
    'renderer returns XML string only',
    is_string($renderer->renderUrlSet([$dtoUrl])) && str_starts_with($renderer->renderUrlSet([$dtoUrl]), $xmlHeader),
);

echo "Phase 10C image sitemap XML string renderer tests passed.\n";
