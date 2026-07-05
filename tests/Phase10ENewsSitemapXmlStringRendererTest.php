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
use Maatify\Seo\Shared\DTO\Sitemap\SitemapNewsDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapUrlDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapVideoDTO;
use Maatify\Seo\Web\Sitemap\SitemapXmlStringRenderer;

function assertSameValue10E(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertTrueValue10E(string $label, bool $actual): void
{
    if (!$actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertThrowsSeoException10E(string $label, callable $callback): void
{
    try {
        $callback();
    } catch (SeoExceptionInterface $exception) {
        assertTrueValue10E($label . ' uses invalid argument exception', $exception instanceof SeoInvalidArgumentException);
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
    loc: 'https://example.com/news/story',
    news: [new SitemapNewsDTO('Example Daily', 'en', '2026-07-01', 'Breaking News')],
);

$arrayUrl = [
    'loc' => 'https://example.com/news/story-2',
    'news' => [[
        'publicationName' => 'Example Tribune',
        'publicationLanguage' => 'en',
        'publicationDate' => '2026-07-01T10:00:00+00:00',
        'title' => 'Market Update',
        'access' => 'Subscription',
        'genres' => 'PressRelease, Blog',
        'keywords' => 'markets, stocks',
        'stockTickers' => 'NASDAQ:EXM',
    ]],
];

assertSameValue10E(
    'DTO URL entry renders news sitemap tags with local namespace',
    $xmlHeader
    . '<url xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"><loc>https://example.com/news/story</loc><news:news><news:publication><news:name>Example Daily</news:name><news:language>en</news:language></news:publication><news:publication_date>2026-07-01</news:publication_date><news:title>Breaking News</news:title></news:news></url>' . "\n",
    $renderer->renderUrlEntry($dtoUrl),
);

assertSameValue10E(
    'array URL entry renders required and optional news sitemap tags with local namespace',
    $xmlHeader
    . '<url xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"><loc>https://example.com/news/story-2</loc><news:news><news:publication><news:name>Example Tribune</news:name><news:language>en</news:language></news:publication><news:publication_date>2026-07-01T10:00:00+00:00</news:publication_date><news:title>Market Update</news:title><news:access>Subscription</news:access><news:genres>PressRelease, Blog</news:genres><news:keywords>markets, stocks</news:keywords><news:stock_tickers>NASDAQ:EXM</news:stock_tickers></news:news></url>' . "\n",
    $renderer->renderUrlEntry($arrayUrl),
);

assertSameValue10E(
    'full URL set with news declares news namespace once on urlset',
    $xmlHeader
    . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"><url><loc>https://example.com/news/story</loc><news:news><news:publication><news:name>Example Daily</news:name><news:language>en</news:language></news:publication><news:publication_date>2026-07-01</news:publication_date><news:title>Breaking News</news:title></news:news></url><url><loc>https://example.com/news/story-2</loc><news:news><news:publication><news:name>Example Tribune</news:name><news:language>en</news:language></news:publication><news:publication_date>2026-07-01T10:00:00+00:00</news:publication_date><news:title>Market Update</news:title><news:access>Subscription</news:access><news:genres>PressRelease, Blog</news:genres><news:keywords>markets, stocks</news:keywords><news:stock_tickers>NASDAQ:EXM</news:stock_tickers></news:news></url></urlset>' . "\n",
    $renderer->renderUrlSet([$dtoUrl, $arrayUrl]),
);

assertSameValue10E(
    'full URL set without news remains unchanged and omits news namespace',
    $xmlHeader
    . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><url><loc>https://example.com/plain</loc></url></urlset>' . "\n",
    $renderer->renderUrlSet([['loc' => 'https://example.com/plain']]),
);

$combinedUrl = new SitemapUrlDTO(
    loc: 'https://example.com/en/news-product',
    alternates: [new SitemapAlternateUrlDTO('en', 'https://example.com/en/news-product')],
    images: [new SitemapImageDTO('https://cdn.example.com/products/product-1.jpg', 'Product 1')],
    videos: [new SitemapVideoDTO('https://cdn.example.com/videos/thumb.jpg', 'Video title', 'Video description', 'https://cdn.example.com/videos/video.mp4')],
    news: [new SitemapNewsDTO('Example Daily', 'en', '2026-07-01', 'Product Launch')],
);

assertSameValue10E(
    'URL set supports news with alternates images and videos without breaking namespaces',
    $xmlHeader
    . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"><url><loc>https://example.com/en/news-product</loc><xhtml:link rel="alternate" hreflang="en" href="https://example.com/en/news-product"/><image:image><image:loc>https://cdn.example.com/products/product-1.jpg</image:loc><image:title>Product 1</image:title></image:image><video:video><video:thumbnail_loc>https://cdn.example.com/videos/thumb.jpg</video:thumbnail_loc><video:title>Video title</video:title><video:description>Video description</video:description><video:content_loc>https://cdn.example.com/videos/video.mp4</video:content_loc></video:video><news:news><news:publication><news:name>Example Daily</news:name><news:language>en</news:language></news:publication><news:publication_date>2026-07-01</news:publication_date><news:title>Product Launch</news:title></news:news></url></urlset>' . "\n",
    $renderer->renderUrlSet([$combinedUrl]),
);

assertSameValue10E(
    'XML special characters in news fields are escaped safely',
    $xmlHeader
    . '<url xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"><loc>https://example.com/search</loc><news:news><news:publication><news:name>News &amp; &lt;Daily&gt;</news:name><news:language>en</news:language></news:publication><news:publication_date>2026-07-01T10:00:00+00:00</news:publication_date><news:title>Title &quot;quoted&quot; &amp; safe</news:title><news:access>Registration</news:access><news:genres>Opinion &amp; Blog</news:genres><news:keywords>one &amp; two</news:keywords><news:stock_tickers>NASDAQ:ABC &amp; NYSE:XYZ</news:stock_tickers></news:news></url>' . "\n",
    $renderer->renderUrlEntry([
        'loc' => 'https://example.com/search',
        'news' => [[
            'publicationName' => 'News & <Daily>',
            'publicationLanguage' => 'en',
            'publicationDate' => '2026-07-01T10:00:00+00:00',
            'title' => 'Title "quoted" & safe',
            'access' => 'Registration',
            'genres' => 'Opinion & Blog',
            'keywords' => 'one & two',
            'stockTickers' => 'NASDAQ:ABC & NYSE:XYZ',
        ]],
    ]),
);

assertThrowsSeoException10E('empty news publicationName throws module exception', static fn() => new SitemapNewsDTO(' ', 'en', '2026-07-01', 'Title'));
assertThrowsSeoException10E('empty news publicationLanguage throws module exception', static fn() => new SitemapNewsDTO('Example Daily', ' ', '2026-07-01', 'Title'));
assertThrowsSeoException10E('empty news publicationDate throws module exception', static fn() => new SitemapNewsDTO('Example Daily', 'en', ' ', 'Title'));
assertThrowsSeoException10E('empty news title throws module exception', static fn() => new SitemapNewsDTO('Example Daily', 'en', '2026-07-01', ' '));
assertThrowsSeoException10E('non-list news throws module exception', static fn() => $renderer->renderUrlEntry([
    'loc' => 'https://example.com/news',
    'news' => ['first' => ['publicationName' => 'Example Daily']],
]));
assertThrowsSeoException10E('invalid news entry shape throws module exception', static fn() => $renderer->renderUrlEntry([
    'loc' => 'https://example.com/news',
    'news' => [['Example Daily']],
]));
assertThrowsSeoException10E('array news with empty publicationName throws module exception', static fn() => $renderer->renderUrlEntry([
    'loc' => 'https://example.com/news',
    'news' => [['publicationName' => ' ', 'publicationLanguage' => 'en', 'publicationDate' => '2026-07-01', 'title' => 'Title']],
]));
assertThrowsSeoException10E('array news with empty publicationLanguage throws module exception', static fn() => $renderer->renderUrlEntry([
    'loc' => 'https://example.com/news',
    'news' => [['publicationName' => 'Example Daily', 'publicationLanguage' => ' ', 'publicationDate' => '2026-07-01', 'title' => 'Title']],
]));
assertThrowsSeoException10E('array news with empty publicationDate throws module exception', static fn() => $renderer->renderUrlEntry([
    'loc' => 'https://example.com/news',
    'news' => [['publicationName' => 'Example Daily', 'publicationLanguage' => 'en', 'publicationDate' => ' ', 'title' => 'Title']],
]));
assertThrowsSeoException10E('array news with empty title throws module exception', static fn() => $renderer->renderUrlEntry([
    'loc' => 'https://example.com/news',
    'news' => [['publicationName' => 'Example Daily', 'publicationLanguage' => 'en', 'publicationDate' => '2026-07-01', 'title' => ' ']],
]));

assertSameValue10E(
    'empty optional news strings normalize away',
    $xmlHeader
    . '<url xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"><loc>https://example.com/news</loc><news:news><news:publication><news:name>Example Daily</news:name><news:language>en</news:language></news:publication><news:publication_date>2026-07-01</news:publication_date><news:title>Title</news:title></news:news></url>' . "\n",
    $renderer->renderUrlEntry([
        'loc' => 'https://example.com/news',
        'news' => [['publicationName' => 'Example Daily', 'publicationLanguage' => 'en', 'publicationDate' => '2026-07-01', 'title' => 'Title', 'access' => ' ', 'genres' => '']],
    ]),
);

assertTrueValue10E(
    'renderer returns XML string only and has no HTTP framework behavior',
    is_string($renderer->renderUrlSet([$dtoUrl])) && str_starts_with($renderer->renderUrlSet([$dtoUrl]), $xmlHeader),
);

echo "Phase 10E news sitemap XML string renderer tests passed.\n";
