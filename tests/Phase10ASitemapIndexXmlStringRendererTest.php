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
use Maatify\Seo\Shared\DTO\Sitemap\SitemapUrlDTO;
use Maatify\Seo\Shared\Service\SitemapGeneratorService;
use Maatify\Seo\Web\Sitemap\DTO\SitemapIndexEntryDTO;
use Maatify\Seo\Web\Sitemap\SitemapIndexXmlStringRenderer;
use Maatify\Seo\Web\Sitemap\SitemapXmlStringRenderer;

function assertSameValue10A(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertTrueValue10A(string $label, bool $actual): void
{
    if (!$actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertThrowsSeoException10A(string $label, callable $callback): void
{
    try {
        $callback();
    } catch (SeoExceptionInterface $exception) {
        assertTrueValue10A($label . ' uses invalid argument exception', $exception instanceof SeoInvalidArgumentException);
        return;
    } catch (RuntimeException $exception) {
        fwrite(STDERR, "Assertion failed: {$label}\nUnexpected raw RuntimeException: {$exception->getMessage()}\n");
        exit(1);
    }

    fwrite(STDERR, "Assertion failed: {$label}\nExpected SEO module exception.\n");
    exit(1);
}

$renderer = new SitemapIndexXmlStringRenderer();
$xmlHeader = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

$dto = new SitemapIndexEntryDTO(
    loc: 'https://example.com/sitemap-products.xml',
    lastmod: '2026-07-01',
);
$arrayEntry = [
    'loc' => 'https://example.com/sitemap-categories.xml',
    'lastmod' => '2026-07-02T10:00:00+00:00',
];

assertSameValue10A(
    'single sitemap index entry renders from DTO',
    $xmlHeader
    . '<sitemap><loc>https://example.com/sitemap-products.xml</loc><lastmod>2026-07-01</lastmod></sitemap>' . "\n",
    $renderer->renderEntry($dto),
);

assertSameValue10A(
    'single sitemap index entry renders from associative array',
    $xmlHeader
    . '<sitemap><loc>https://example.com/sitemap-categories.xml</loc><lastmod>2026-07-02T10:00:00+00:00</lastmod></sitemap>' . "\n",
    $renderer->renderEntry($arrayEntry),
);

assertSameValue10A(
    'full sitemap index renders multiple entries in order',
    $xmlHeader
    . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><sitemap><loc>https://example.com/sitemap-products.xml</loc><lastmod>2026-07-01</lastmod></sitemap><sitemap><loc>https://example.com/sitemap-categories.xml</loc><lastmod>2026-07-02T10:00:00+00:00</lastmod></sitemap></sitemapindex>' . "\n",
    $renderer->renderIndex([$dto, $arrayEntry]),
);

assertSameValue10A(
    'optional lastmod is omitted',
    $xmlHeader
    . '<sitemap><loc>https://example.com/sitemap-minimal.xml</loc></sitemap>' . "\n",
    $renderer->renderEntry(['loc' => 'https://example.com/sitemap-minimal.xml']),
);

assertSameValue10A(
    'XML special characters are escaped safely',
    $xmlHeader
    . '<sitemap><loc>https://example.com/sitemap-search.xml?q=seo&amp;page=1</loc><lastmod>2026-07-01</lastmod></sitemap>' . "\n",
    $renderer->renderEntry([
        'loc' => 'https://example.com/sitemap-search.xml?q=seo&page=1',
        'lastmod' => '2026-07-01',
    ]),
);

assertTrueValue10A(
    'renderer returns XML string only',
    is_string($renderer->renderIndex([$dto])) && str_starts_with($renderer->renderIndex([$dto]), $xmlHeader),
);

assertThrowsSeoException10A('empty loc throws module exception', static fn() => new SitemapIndexEntryDTO('   '));
assertThrowsSeoException10A('invalid URL throws module exception', static fn() => $renderer->renderEntry(['loc' => 'not-a-url']));
assertThrowsSeoException10A('invalid lastmod throws module exception', static fn() => $renderer->renderEntry(['loc' => 'https://example.com/sitemap.xml', 'lastmod' => '2026-02-31']));
assertThrowsSeoException10A('non-array non-DTO input throws module exception', static fn() => $renderer->renderEntry('https://example.com/sitemap.xml'));
assertThrowsSeoException10A('list array entry throws module exception', static fn() => $renderer->renderEntry(['https://example.com/sitemap.xml']));

assertSameValue10A(
    'existing SitemapXmlStringRenderer behavior remains unchanged',
    $xmlHeader
    . '<url><loc>https://example.com/articles/one</loc><lastmod>2026-07-01</lastmod><changefreq>daily</changefreq><priority>0.8</priority></url>' . "\n",
    (new SitemapXmlStringRenderer())->renderUrlEntry(new SitemapUrlDTO('https://example.com/articles/one', '2026-07-01', 'daily', 0.8)),
);

assertSameValue10A(
    'existing SitemapGeneratorService sitemap index behavior remains unchanged',
    $xmlHeader
    . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><sitemap><loc>https://example.com/generated-index.xml</loc><lastmod>2026-07-01</lastmod></sitemap></sitemapindex>' . "\n",
    (new SitemapGeneratorService())->generateSitemapIndex([
        new Maatify\Seo\Shared\DTO\Sitemap\SitemapIndexEntryDTO('https://example.com/generated-index.xml', '2026-07-01'),
    ])->xml,
);

echo "Phase 10A sitemap index XML string renderer tests passed.\n";
