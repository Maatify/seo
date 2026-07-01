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
use Maatify\Seo\Shared\DTO\Sitemap\SitemapUrlDTO;
use Maatify\Seo\Web\Sitemap\SitemapXmlStringRenderer;

function assertSameValue10B(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertTrueValue10B(string $label, bool $actual): void
{
    if (!$actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertThrowsSeoException10B(string $label, callable $callback): void
{
    try {
        $callback();
    } catch (SeoExceptionInterface $exception) {
        assertTrueValue10B($label . ' uses invalid argument exception', $exception instanceof SeoInvalidArgumentException);
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
    loc: 'https://example.com/en/product',
    alternates: [
        new SitemapAlternateUrlDTO('en', 'https://example.com/en/product'),
        new SitemapAlternateUrlDTO('ar', 'https://example.com/ar/product'),
    ],
);

$arrayUrl = [
    'loc' => 'https://example.com/en/category',
    'alternates' => [
        ['hreflang' => 'en', 'url' => 'https://example.com/en/category'],
        ['hreflang' => 'ar', 'url' => 'https://example.com/ar/category'],
    ],
];

assertSameValue10B(
    'DTO URL entry renders hreflang alternate links with local namespace',
    $xmlHeader
    . '<url xmlns:xhtml="http://www.w3.org/1999/xhtml"><loc>https://example.com/en/product</loc><xhtml:link rel="alternate" hreflang="en" href="https://example.com/en/product"/><xhtml:link rel="alternate" hreflang="ar" href="https://example.com/ar/product"/></url>' . "\n",
    $renderer->renderUrlEntry($dtoUrl),
);

assertSameValue10B(
    'array URL entry renders hreflang alternate links with local namespace',
    $xmlHeader
    . '<url xmlns:xhtml="http://www.w3.org/1999/xhtml"><loc>https://example.com/en/category</loc><xhtml:link rel="alternate" hreflang="en" href="https://example.com/en/category"/><xhtml:link rel="alternate" hreflang="ar" href="https://example.com/ar/category"/></url>' . "\n",
    $renderer->renderUrlEntry($arrayUrl),
);

assertSameValue10B(
    'full URL set with alternates declares xhtml namespace once on urlset',
    $xmlHeader
    . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml"><url><loc>https://example.com/en/product</loc><xhtml:link rel="alternate" hreflang="en" href="https://example.com/en/product"/><xhtml:link rel="alternate" hreflang="ar" href="https://example.com/ar/product"/></url><url><loc>https://example.com/en/category</loc><xhtml:link rel="alternate" hreflang="en" href="https://example.com/en/category"/><xhtml:link rel="alternate" hreflang="ar" href="https://example.com/ar/category"/></url></urlset>' . "\n",
    $renderer->renderUrlSet([$dtoUrl, $arrayUrl]),
);

assertSameValue10B(
    'full URL set without alternates remains unchanged and omits xhtml namespace',
    $xmlHeader
    . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><url><loc>https://example.com/plain</loc></url></urlset>' . "\n",
    $renderer->renderUrlSet([['loc' => 'https://example.com/plain']]),
);

assertSameValue10B(
    'XML special characters in alternate URL attributes are escaped safely',
    $xmlHeader
    . '<url xmlns:xhtml="http://www.w3.org/1999/xhtml"><loc>https://example.com/en/search</loc><xhtml:link rel="alternate" hreflang="en" href="https://example.com/en/search?q=seo&amp;name=&lt;tag&gt;&quot;quote&quot;"/></url>' . "\n",
    $renderer->renderUrlEntry([
        'loc' => 'https://example.com/en/search',
        'alternates' => [
            ['hreflang' => 'en', 'url' => 'https://example.com/en/search?q=seo&name=<tag>"quote"'],
        ],
    ]),
);

assertThrowsSeoException10B('empty hreflang throws module exception', static fn() => $renderer->renderUrlEntry([
    'loc' => 'https://example.com/en/product',
    'alternates' => [['hreflang' => ' ', 'url' => 'https://example.com/en/product']],
]));
assertThrowsSeoException10B('invalid hreflang format throws module exception', static fn() => $renderer->renderUrlEntry([
    'loc' => 'https://example.com/en/product',
    'alternates' => [['hreflang' => 'english language', 'url' => 'https://example.com/en/product']],
]));
assertThrowsSeoException10B('empty alternate URL throws module exception', static fn() => $renderer->renderUrlEntry([
    'loc' => 'https://example.com/en/product',
    'alternates' => [['hreflang' => 'en', 'url' => ' ']],
]));
assertThrowsSeoException10B('invalid alternate URL format throws module exception', static fn() => $renderer->renderUrlEntry([
    'loc' => 'https://example.com/en/product',
    'alternates' => [['hreflang' => 'en', 'url' => 'not-a-url']],
]));
assertThrowsSeoException10B('non-list alternates throws module exception', static fn() => $renderer->renderUrlEntry([
    'loc' => 'https://example.com/en/product',
    'alternates' => ['en' => ['hreflang' => 'en', 'url' => 'https://example.com/en/product']],
]));
assertThrowsSeoException10B('invalid alternate entry shape throws module exception', static fn() => $renderer->renderUrlEntry([
    'loc' => 'https://example.com/en/product',
    'alternates' => [['en', 'https://example.com/en/product']],
]));

assertTrueValue10B(
    'renderer returns XML string only',
    is_string($renderer->renderUrlSet([$dtoUrl])) && str_starts_with($renderer->renderUrlSet([$dtoUrl]), $xmlHeader),
);

echo "Phase 10B sitemap hreflang XML string renderer tests passed.\n";
