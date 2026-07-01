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
use Maatify\Seo\Shared\DTO\Sitemap\SitemapVideoDTO;
use Maatify\Seo\Web\Sitemap\SitemapXmlStringRenderer;

function assertSameValue10D(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertTrueValue10D(string $label, bool $actual): void
{
    if (!$actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertThrowsSeoException10D(string $label, callable $callback): void
{
    try {
        $callback();
    } catch (SeoExceptionInterface $exception) {
        assertTrueValue10D($label . ' uses invalid argument exception', $exception instanceof SeoInvalidArgumentException);
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
    loc: 'https://example.com/video-page',
    videos: [
        new SitemapVideoDTO(
            thumbnailLoc: 'https://cdn.example.com/videos/thumb.jpg',
            title: 'Video title',
            description: 'Video description',
            contentLoc: 'https://cdn.example.com/videos/video.mp4',
        ),
    ],
);

$arrayUrl = [
    'loc' => 'https://example.com/video-page-2',
    'videos' => [[
        'thumbnailLoc' => 'https://cdn.example.com/videos/thumb-2.jpg',
        'title' => 'Video title 2',
        'description' => 'Video description 2',
        'contentLoc' => null,
        'playerLoc' => 'https://player.example.com/videos/2',
        'duration' => 120,
        'publicationDate' => '2026-07-01',
    ]],
];

assertSameValue10D(
    'DTO URL entry renders video sitemap tags with local namespace',
    $xmlHeader
    . '<url xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"><loc>https://example.com/video-page</loc><video:video><video:thumbnail_loc>https://cdn.example.com/videos/thumb.jpg</video:thumbnail_loc><video:title>Video title</video:title><video:description>Video description</video:description><video:content_loc>https://cdn.example.com/videos/video.mp4</video:content_loc></video:video></url>' . "\n",
    $renderer->renderUrlEntry($dtoUrl),
);

assertSameValue10D(
    'array URL entry renders video sitemap tags with local namespace',
    $xmlHeader
    . '<url xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"><loc>https://example.com/video-page-2</loc><video:video><video:thumbnail_loc>https://cdn.example.com/videos/thumb-2.jpg</video:thumbnail_loc><video:title>Video title 2</video:title><video:description>Video description 2</video:description><video:player_loc>https://player.example.com/videos/2</video:player_loc><video:duration>120</video:duration><video:publication_date>2026-07-01</video:publication_date></video:video></url>' . "\n",
    $renderer->renderUrlEntry($arrayUrl),
);

assertSameValue10D(
    'full URL set with videos declares video namespace once on urlset',
    $xmlHeader
    . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"><url><loc>https://example.com/video-page</loc><video:video><video:thumbnail_loc>https://cdn.example.com/videos/thumb.jpg</video:thumbnail_loc><video:title>Video title</video:title><video:description>Video description</video:description><video:content_loc>https://cdn.example.com/videos/video.mp4</video:content_loc></video:video></url><url><loc>https://example.com/video-page-2</loc><video:video><video:thumbnail_loc>https://cdn.example.com/videos/thumb-2.jpg</video:thumbnail_loc><video:title>Video title 2</video:title><video:description>Video description 2</video:description><video:player_loc>https://player.example.com/videos/2</video:player_loc><video:duration>120</video:duration><video:publication_date>2026-07-01</video:publication_date></video:video></url></urlset>' . "\n",
    $renderer->renderUrlSet([$dtoUrl, $arrayUrl]),
);

assertSameValue10D(
    'full URL set without videos remains unchanged and omits video namespace',
    $xmlHeader
    . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><url><loc>https://example.com/plain</loc></url></urlset>' . "\n",
    $renderer->renderUrlSet([['loc' => 'https://example.com/plain']]),
);

$combinedUrl = new SitemapUrlDTO(
    loc: 'https://example.com/en/video-product',
    alternates: [new SitemapAlternateUrlDTO('en', 'https://example.com/en/video-product')],
    images: [new SitemapImageDTO('https://cdn.example.com/products/product-1.jpg', 'Product 1')],
    videos: [new SitemapVideoDTO('https://cdn.example.com/videos/thumb.jpg', 'Video title', 'Video description', 'https://cdn.example.com/videos/video.mp4')],
);

assertSameValue10D(
    'URL entry with alternates images and videos declares all local namespaces',
    $xmlHeader
    . '<url xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"><loc>https://example.com/en/video-product</loc><xhtml:link rel="alternate" hreflang="en" href="https://example.com/en/video-product"/><image:image><image:loc>https://cdn.example.com/products/product-1.jpg</image:loc><image:title>Product 1</image:title></image:image><video:video><video:thumbnail_loc>https://cdn.example.com/videos/thumb.jpg</video:thumbnail_loc><video:title>Video title</video:title><video:description>Video description</video:description><video:content_loc>https://cdn.example.com/videos/video.mp4</video:content_loc></video:video></url>' . "\n",
    $renderer->renderUrlEntry($combinedUrl),
);

assertSameValue10D(
    'URL set with alternates images and videos declares all namespaces',
    $xmlHeader
    . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"><url><loc>https://example.com/en/video-product</loc><xhtml:link rel="alternate" hreflang="en" href="https://example.com/en/video-product"/><image:image><image:loc>https://cdn.example.com/products/product-1.jpg</image:loc><image:title>Product 1</image:title></image:image><video:video><video:thumbnail_loc>https://cdn.example.com/videos/thumb.jpg</video:thumbnail_loc><video:title>Video title</video:title><video:description>Video description</video:description><video:content_loc>https://cdn.example.com/videos/video.mp4</video:content_loc></video:video></url></urlset>' . "\n",
    $renderer->renderUrlSet([$combinedUrl]),
);

assertSameValue10D(
    'XML special characters in video fields are escaped safely',
    $xmlHeader
    . '<url xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"><loc>https://example.com/search</loc><video:video><video:thumbnail_loc>https://cdn.example.com/thumb.jpg?x=1&amp;y=2</video:thumbnail_loc><video:title>Video &amp; &lt;tag&gt;</video:title><video:description>Description &quot;quoted&quot; &amp; safe</video:description><video:content_loc>https://cdn.example.com/video.mp4?x=1&amp;y=2</video:content_loc></video:video></url>' . "\n",
    $renderer->renderUrlEntry([
        'loc' => 'https://example.com/search',
        'videos' => [[
            'thumbnailLoc' => 'https://cdn.example.com/thumb.jpg?x=1&y=2',
            'title' => 'Video & <tag>',
            'description' => 'Description "quoted" & safe',
            'contentLoc' => 'https://cdn.example.com/video.mp4?x=1&y=2',
        ]],
    ]),
);

assertThrowsSeoException10D('empty video thumbnailLoc throws module exception', static fn() => new SitemapVideoDTO(' ', 'Title', 'Description', 'https://cdn.example.com/video.mp4'));
assertThrowsSeoException10D('invalid video thumbnail URL format throws module exception', static fn() => new SitemapVideoDTO('not-a-url', 'Title', 'Description', 'https://cdn.example.com/video.mp4'));
assertThrowsSeoException10D('empty video title throws module exception', static fn() => new SitemapVideoDTO('https://cdn.example.com/thumb.jpg', ' ', 'Description', 'https://cdn.example.com/video.mp4'));
assertThrowsSeoException10D('empty video description throws module exception', static fn() => new SitemapVideoDTO('https://cdn.example.com/thumb.jpg', 'Title', ' ', 'https://cdn.example.com/video.mp4'));
assertThrowsSeoException10D('missing contentLoc and playerLoc throws module exception', static fn() => new SitemapVideoDTO('https://cdn.example.com/thumb.jpg', 'Title', 'Description'));
assertThrowsSeoException10D('invalid contentLoc throws module exception', static fn() => new SitemapVideoDTO('https://cdn.example.com/thumb.jpg', 'Title', 'Description', 'not-a-url'));
assertThrowsSeoException10D('invalid playerLoc throws module exception', static fn() => new SitemapVideoDTO('https://cdn.example.com/thumb.jpg', 'Title', 'Description', null, 'not-a-url'));
assertThrowsSeoException10D('invalid duration throws module exception', static fn() => new SitemapVideoDTO('https://cdn.example.com/thumb.jpg', 'Title', 'Description', 'https://cdn.example.com/video.mp4', duration: 0));
assertThrowsSeoException10D('invalid publication date throws module exception', static fn() => new SitemapVideoDTO('https://cdn.example.com/thumb.jpg', 'Title', 'Description', 'https://cdn.example.com/video.mp4', publicationDate: '2026-02-31'));
assertThrowsSeoException10D('non-list videos throws module exception', static fn() => $renderer->renderUrlEntry([
    'loc' => 'https://example.com/video',
    'videos' => ['first' => ['thumbnailLoc' => 'https://cdn.example.com/thumb.jpg']],
]));
assertThrowsSeoException10D('invalid video entry shape throws module exception', static fn() => $renderer->renderUrlEntry([
    'loc' => 'https://example.com/video',
    'videos' => [['https://cdn.example.com/thumb.jpg']],
]));
assertThrowsSeoException10D('array video with invalid duration throws module exception', static fn() => $renderer->renderUrlEntry([
    'loc' => 'https://example.com/video',
    'videos' => [['thumbnailLoc' => 'https://cdn.example.com/thumb.jpg', 'title' => 'Title', 'description' => 'Description', 'contentLoc' => 'https://cdn.example.com/video.mp4', 'duration' => '1.5']],
]));

assertSameValue10D(
    'empty optional video strings normalize away',
    $xmlHeader
    . '<url xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"><loc>https://example.com/video</loc><video:video><video:thumbnail_loc>https://cdn.example.com/thumb.jpg</video:thumbnail_loc><video:title>Title</video:title><video:description>Description</video:description><video:content_loc>https://cdn.example.com/video.mp4</video:content_loc></video:video></url>' . "\n",
    $renderer->renderUrlEntry([
        'loc' => 'https://example.com/video',
        'videos' => [['thumbnailLoc' => 'https://cdn.example.com/thumb.jpg', 'title' => 'Title', 'description' => 'Description', 'contentLoc' => 'https://cdn.example.com/video.mp4', 'playerLoc' => '', 'publicationDate' => ' ']],
    ]),
);

assertTrueValue10D(
    'renderer returns XML string only',
    is_string($renderer->renderUrlSet([$dtoUrl])) && str_starts_with($renderer->renderUrlSet([$dtoUrl]), $xmlHeader),
);

echo "Phase 10D video sitemap XML string renderer tests passed.\n";
