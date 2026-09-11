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

use Maatify\Seo\Exception\SeoInvalidArgumentException;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapAlternateUrlDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapImageDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapIndexEntryDTO as SharedSitemapIndexEntryDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapNewsDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapUrlDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapVideoDTO;
use Maatify\Seo\Shared\Service\SitemapGeneratorService;
use Maatify\Seo\Web\Sitemap\DTO\SitemapIndexEntryDTO as WebSitemapIndexEntryDTO;
use Maatify\Seo\Web\Sitemap\SitemapIndexXmlStringRenderer;
use Maatify\Seo\Web\Sitemap\SitemapXmlStringRenderer;

function stack3AssertSame(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        throw new RuntimeException("Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true));
    }
}

function stack3AssertTrue(string $label, bool $actual): void
{
    if (!$actual) {
        throw new RuntimeException("Assertion failed: {$label}");
    }
}

function stack3AssertThrows(string $label, callable $callback, ?string $message = null): void
{
    try {
        $callback();
    } catch (Throwable $exception) {
        stack3AssertSame($label . ' exception class', SeoInvalidArgumentException::class, get_class($exception));
        if ($message !== null) {
            stack3AssertSame($label . ' exception message', $message, $exception->getMessage());
        }

        return;
    }

    throw new RuntimeException("Assertion failed: {$label}\nExpected SeoInvalidArgumentException.");
}

function stack3AssertSignature(string $class, string $method, array $parameterTypes, string $returnType): void
{
    $reflection = new ReflectionMethod($class, $method);
    $parameters = $reflection->getParameters();
    stack3AssertSame($class . '::' . $method . ' parameter count', count($parameterTypes), count($parameters));
    foreach ($parameterTypes as $index => $expectedType) {
        stack3AssertSame(
            $class . '::' . $method . ' parameter ' . $index,
            $expectedType,
            $parameters[$index]->getType()?->getName(),
        );
    }

    stack3AssertSame(
        $class . '::' . $method . ' return type',
        $returnType,
        $reflection->getReturnType()?->getName(),
    );
}

$xmlHeader = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$urlRenderer = new SitemapXmlStringRenderer();
$indexRenderer = new SitemapIndexXmlStringRenderer();

stack3AssertSignature(SitemapGeneratorService::class, 'generateUrlSitemap', ['array'], 'Maatify\\Seo\\Shared\\DTO\\Sitemap\\SitemapGenerationResultDTO');
stack3AssertSignature(SitemapGeneratorService::class, 'generateSitemapIndex', ['array'], 'Maatify\\Seo\\Shared\\DTO\\Sitemap\\SitemapGenerationResultDTO');
stack3AssertSignature(SitemapXmlStringRenderer::class, 'renderUrlSet', ['array'], 'string');
stack3AssertSignature(SitemapXmlStringRenderer::class, 'renderUrlEntry', ['mixed'], 'string');
stack3AssertSignature(SitemapIndexXmlStringRenderer::class, 'renderIndex', ['array'], 'string');
stack3AssertSignature(SitemapIndexXmlStringRenderer::class, 'renderEntry', ['mixed'], 'string');

$publicFacadePaths = [
    __DIR__ . '/../src/Shared/Service/SitemapGeneratorService.php',
    __DIR__ . '/../src/Web/Sitemap/SitemapXmlStringRenderer.php',
    __DIR__ . '/../src/Web/Sitemap/SitemapIndexXmlStringRenderer.php',
];
foreach ($publicFacadePaths as $path) {
    $source = file_get_contents($path);
    stack3AssertTrue('public facade source is readable: ' . basename($path), is_string($source));
    stack3AssertTrue('public facade has no XMLWriter implementation: ' . basename($path), !str_contains((string) $source, 'XMLWriter'));
}
$generatorSource = file_get_contents(__DIR__ . '/../src/Shared/Service/SitemapGeneratorService.php');
stack3AssertTrue('SitemapGeneratorService has no Shared to Web dependency', is_string($generatorSource) && !str_contains($generatorSource, 'Maatify\\Seo\\Web\\'));
$canonicalWriterSource = file_get_contents(__DIR__ . '/../src/Shared/Service/Internal/SitemapCanonicalXmlWriter.php');
stack3AssertTrue('canonical writer uses the Shared internal namespace', is_string($canonicalWriterSource) && str_contains($canonicalWriterSource, 'namespace Maatify\\Seo\\Shared\\Service\\Internal;'));
stack3AssertTrue('Shared canonical writer has no Web dependency', is_string($canonicalWriterSource) && !str_contains($canonicalWriterSource, 'Maatify\\Seo\\Web\\'));
$xmlWriterImplementationFiles = [];
$sourceIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../src'));
foreach ($sourceIterator as $sourceFile) {
    if (!$sourceFile->isFile() || $sourceFile->getExtension() !== 'php') {
        continue;
    }

    $source = file_get_contents($sourceFile->getPathname());
    if (is_string($source) && str_contains($source, 'use XMLWriter;')) {
        $xmlWriterImplementationFiles[] = $sourceFile->getPathname();
    }
}
stack3AssertSame(
    'canonical internal writer is the sole XMLWriter implementation file',
    [__DIR__ . '/../src/Shared/Service/Internal/SitemapCanonicalXmlWriter.php'],
    $xmlWriterImplementationFiles,
);

$extendedUrl = new SitemapUrlDTO(
    loc: 'https://example.com/articles/extended',
    lastmod: '2026-07-01T10:00:00+00:00',
    changefreq: 'daily',
    priority: 0.7,
    alternates: [
        new SitemapAlternateUrlDTO('en', 'https://example.com/en/articles/extended'),
        new SitemapAlternateUrlDTO('x-default', 'https://example.com/articles/extended'),
    ],
    images: [
        new SitemapImageDTO(
            'https://cdn.example.com/extended.jpg',
            'صورة & <featured>',
            'Caption "with" markup',
            'Cairo',
            'https://example.com/license',
        ),
    ],
    videos: [
        new SitemapVideoDTO(
            'https://cdn.example.com/extended-thumb.jpg',
            'Video <main>',
            'Description & details',
            'https://cdn.example.com/extended.mp4',
            'https://example.com/player?id=1',
            120,
            '2026-07-01T11:00:00+00:00',
        ),
    ],
    news: [
        new SitemapNewsDTO('Example Daily', 'en', '2026-07-01', 'First story & more'),
        new SitemapNewsDTO('Example Tribune', 'ar', '2026-07-02', 'ثاني خبر <مميز>'),
    ],
);

$rendererXml = $urlRenderer->renderUrlSet([$extendedUrl]);
$generatorResult = (new SitemapGeneratorService())->generateUrlSitemap([$extendedUrl]);
stack3AssertSame('Generator and Web renderer share exact extended URL XML', $rendererXml, $generatorResult->xml);
stack3AssertSame('Generator keeps URL entry count and result type', ['xml' => $rendererXml, 'entry_count' => 1, 'type' => 'urlset'], $generatorResult->jsonSerialize());
stack3AssertTrue('extended URL output has all conditional namespaces in canonical order', str_contains(
    $rendererXml,
    '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">',
));
foreach (['<xhtml:link', '<image:image>', '<video:video>', '<news:news>'] as $element) {
    stack3AssertTrue('extended URL output contains ' . $element, str_contains($rendererXml, $element));
}
stack3AssertTrue('canonical child order is alternates, images, videos, news',
    strpos($rendererXml, '<xhtml:link') < strpos($rendererXml, '<image:image')
    && strpos($rendererXml, '<image:image') < strpos($rendererXml, '<video:video')
    && strpos($rendererXml, '<video:video') < strpos($rendererXml, '<news:news'),
);
stack3AssertTrue('multiple News DTOs preserve deterministic order',
    strpos($rendererXml, '<news:title>First story &amp; more</news:title>') < strpos($rendererXml, '<news:title>ثاني خبر &lt;مميز&gt;</news:title>'),
);
stack3AssertTrue('XMLWriter escapes Unicode and XML special characters',
    str_contains($rendererXml, '<image:title>صورة &amp; &lt;featured&gt;</image:title>')
    && str_contains($rendererXml, '<video:title>Video &lt;main&gt;</video:title>')
    && str_contains($rendererXml, '<video:player_loc>https://example.com/player?id=1</video:player_loc>'),
);

$rawUrl = [
    'loc' => 'https://example.com/raw?q=seo&lang=ar',
    'lastmod' => '2026-07-03',
    'changefreq' => 'weekly',
    'priority' => '0.5',
    'alternates' => [['hreflang' => 'ar', 'url' => 'https://example.com/ar/raw']],
    'images' => [['loc' => 'https://cdn.example.com/raw.jpg', 'title' => 'Raw image']],
    'videos' => [[
        'thumbnailLoc' => 'https://cdn.example.com/raw-thumb.jpg',
        'title' => 'Raw video',
        'description' => 'Raw description',
        'contentLoc' => 'https://cdn.example.com/raw.mp4',
    ]],
    'news' => [
        ['publicationName' => 'Raw Daily', 'publicationLanguage' => 'en', 'publicationDate' => 'as-provided', 'title' => 'Raw news'],
    ],
];
$mixedXml = $urlRenderer->renderUrlSet([$extendedUrl, $rawUrl]);
stack3AssertTrue('Web renderer retains raw associative URL compatibility', str_contains($mixedXml, '<loc>https://example.com/raw?q=seo&amp;lang=ar</loc>'));
stack3AssertSame('empty Web URL set remains an empty urlset', $xmlHeader . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>' . "\n", $urlRenderer->renderUrlSet([]));
stack3AssertTrue('minimal standalone URL has no unused child namespaces', !str_contains($urlRenderer->renderUrlEntry(['loc' => 'https://example.com/minimal']), 'xmlns:xhtml'));
stack3AssertSame(
    'fractional second remains rejected by strict URL contract',
    false,
    SitemapUrlDTO::isValidLastmod('2026-07-01T10:00:00.123+00:00'),
);
stack3AssertThrows('Web renderer rejects list-shaped raw URL entry', static fn() => $urlRenderer->renderUrlEntry(['https://example.com/not-an-entry']));
stack3AssertThrows('Generator rejects raw-array URL entry', static fn() => (new SitemapGeneratorService())->generateUrlSitemap([['loc' => 'https://example.com/raw']]), 'Field [urls] must not be empty.');
stack3AssertThrows('Generator rejects empty URL input', static fn() => (new SitemapGeneratorService())->generateUrlSitemap([]), 'Field [urls] must not be empty.');

$webIndexEntry = new WebSitemapIndexEntryDTO('https://example.com/web.xml', '2026-07-01');
$sharedIndexEntry = new SharedSitemapIndexEntryDTO('https://example.com/shared.xml', '2026-07-01');
$webIndexXml = $indexRenderer->renderIndex([$webIndexEntry, ['loc' => 'https://example.com/raw-index.xml']]);
stack3AssertSame(
    'Web index DTO and raw entry retain exact index shape',
    $xmlHeader . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><sitemap><loc>https://example.com/web.xml</loc><lastmod>2026-07-01</lastmod></sitemap><sitemap><loc>https://example.com/raw-index.xml</loc></sitemap></sitemapindex>' . "\n",
    $webIndexXml,
);
stack3AssertSame('empty Web index remains an empty sitemapindex', $xmlHeader . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>' . "\n", $indexRenderer->renderIndex([]));
stack3AssertThrows('Web renderer keeps Shared index DTO out of its public boundary', static fn() => $indexRenderer->renderEntry($sharedIndexEntry), 'Field [sitemap] must not be empty.');
stack3AssertSame('Shared and Web index DTO serialization contracts remain distinct but stable', ['loc' => 'https://example.com/shared.xml', 'lastmod' => '2026-07-01'], $sharedIndexEntry->jsonSerialize());
stack3AssertSame('Web index DTO serialization remains stable', ['loc' => 'https://example.com/web.xml', 'lastmod' => '2026-07-01'], $webIndexEntry->jsonSerialize());
stack3AssertThrows('Shared index DTO keeps its empty-field URL exception', static fn() => new SharedSitemapIndexEntryDTO('not-a-url'), 'Field [loc] must not be empty.');
stack3AssertThrows('Web index DTO keeps its invalid-URL exception', static fn() => new WebSitemapIndexEntryDTO('not-a-url'), 'URL [not-a-url] is invalid.');
stack3AssertThrows('Shared index DTO rejects fractional seconds', static fn() => new SharedSitemapIndexEntryDTO('https://example.com/shared.xml', '2026-07-01T10:00:00.123+00:00'));
stack3AssertThrows('Web index DTO rejects fractional seconds', static fn() => new WebSitemapIndexEntryDTO('https://example.com/web.xml', '2026-07-01T10:00:00.123+00:00'));

$equivalentSharedIndexEntry = new SharedSitemapIndexEntryDTO('https://example.com/web.xml', '2026-07-01');
$generatorIndexResult = (new SitemapGeneratorService())->generateSitemapIndex([$equivalentSharedIndexEntry]);
stack3AssertSame('Generator shared index XML equals Web index XML for equivalent DTO values', $indexRenderer->renderIndex([$webIndexEntry]), $generatorIndexResult->xml);
stack3AssertSame('Generator index result preserves result shape and URL entry count', ['xml' => $generatorIndexResult->xml, 'entry_count' => 1, 'type' => 'sitemapindex'], $generatorIndexResult->jsonSerialize());
stack3AssertThrows('Generator rejects raw-array index entry', static fn() => (new SitemapGeneratorService())->generateSitemapIndex([['loc' => 'https://example.com/raw-index.xml']]), 'Field [entries] must not be empty.');
stack3AssertThrows('Generator rejects empty index input', static fn() => (new SitemapGeneratorService())->generateSitemapIndex([]), 'Field [entries] must not be empty.');

echo "Stack 3 sitemap core unification tests passed.\n";
