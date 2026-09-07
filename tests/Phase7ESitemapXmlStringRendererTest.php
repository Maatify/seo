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
use Maatify\Seo\Web\Builder\FluentSeoBuilder;
use Maatify\Seo\Web\Render\JsonLdScriptRenderer;
use Maatify\Seo\Web\Render\SeoHeadHtmlRenderer;
use Maatify\Seo\Web\Schema\SpatieSchemaAdapter;
use Maatify\Seo\Web\Sitemap\SitemapXmlStringRenderer;

function assertSameValue(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertTrueValue(string $label, bool $actual): void
{
    if (!$actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertThrowsSeoException(string $label, callable $callback): void
{
    try {
        $callback();
    } catch (SeoExceptionInterface $exception) {
        assertTrueValue($label . ' uses invalid argument exception', $exception instanceof SeoInvalidArgumentException);
        return;
    } catch (RuntimeException $exception) {
        fwrite(STDERR, "Assertion failed: {$label}\nUnexpected raw RuntimeException: {$exception->getMessage()}\n");
        exit(1);
    }

    fwrite(STDERR, "Assertion failed: {$label}\nExpected SEO module exception.\n");
    exit(1);
}

final class Phase7EFakeSpatieSchema
{
    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['@type' => 'Article', 'headline' => 'Phase 7E unchanged'];
    }
}

$renderer = new SitemapXmlStringRenderer();
$xmlHeader = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

$firstUrl = new SitemapUrlDTO(
    loc: 'https://example.com/articles/one',
    lastmod: '2026-07-01',
    changefreq: 'daily',
    priority: 0.8,
);
$secondUrl = [
    'loc' => 'https://example.com/articles/two',
    'lastmod' => '2026-07-02T10:00:00+00:00',
    'changefreq' => 'weekly',
    'priority' => '0.5',
];

assertSameValue(
    'full sitemap XML output supports DTO and array entries',
    $xmlHeader
    . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><url><loc>https://example.com/articles/one</loc><lastmod>2026-07-01</lastmod><changefreq>daily</changefreq><priority>0.8</priority></url><url><loc>https://example.com/articles/two</loc><lastmod>2026-07-02T10:00:00+00:00</lastmod><changefreq>weekly</changefreq><priority>0.5</priority></url></urlset>' . "\n",
    $renderer->renderUrlSet([$firstUrl, $secondUrl]),
);

assertSameValue(
    'single URL entry renders as plain XML string',
    $xmlHeader
    . '<url><loc>https://example.com/articles/one</loc><lastmod>2026-07-01</lastmod><changefreq>daily</changefreq><priority>0.8</priority></url>' . "\n",
    $renderer->renderUrlEntry($firstUrl),
);

assertSameValue(
    'multiple URL entries preserve input ordering deterministically',
    $xmlHeader
    . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><url><loc>https://example.com/b</loc></url><url><loc>https://example.com/a</loc></url></urlset>' . "\n",
    $renderer->renderUrlSet([
        ['loc' => 'https://example.com/b'],
        ['loc' => 'https://example.com/a'],
    ]),
);

assertSameValue(
    'XML special characters in a valid URL are escaped safely',
    $xmlHeader
    . '<url><loc>https://example.com/search?q=seo&amp;name=tag</loc><lastmod>2026-07-01T10:00:00+00:00</lastmod><changefreq>daily</changefreq><priority>0.7</priority></url>' . "\n",
    $renderer->renderUrlEntry([
        'loc' => 'https://example.com/search?q=seo&name=tag',
        'lastmod' => '2026-07-01T10:00:00+00:00',
        'changefreq' => 'daily',
        'priority' => 0.7,
    ]),
);

assertSameValue(
    'null and empty optional fields are omitted safely',
    $xmlHeader
    . '<url><loc>https://example.com/minimal</loc></url>' . "\n",
    $renderer->renderUrlEntry([
        'loc' => 'https://example.com/minimal',
        'lastmod' => null,
        'changefreq' => '',
        'priority' => '',
    ]),
);

assertSameValue(
    'raw array priority lower boundary remains valid',
    $xmlHeader
    . '<url><loc>https://example.com/priority-zero</loc><priority>0.0</priority></url>' . "\n",
    $renderer->renderUrlEntry([
        'loc' => 'https://example.com/priority-zero',
        'priority' => 0.0,
    ]),
);

assertSameValue(
    'raw array priority upper boundary remains valid',
    $xmlHeader
    . '<url><loc>https://example.com/priority-one</loc><priority>1.0</priority></url>' . "\n",
    $renderer->renderUrlEntry([
        'loc' => 'https://example.com/priority-one',
        'priority' => 1.0,
    ]),
);

foreach (SitemapUrlDTO::allowedChangefreqValues() as $changefreq) {
    assertTrueValue(
        'raw array allowed changefreq remains valid: ' . $changefreq,
        str_contains(
            $renderer->renderUrlEntry([
                'loc' => 'https://example.com/changefreq-' . $changefreq,
                'changefreq' => $changefreq,
            ]),
            '<changefreq>' . $changefreq . '</changefreq>',
        ),
    );
}

assertTrueValue(
    'raw array entry with all child collections remains compatible',
    (static function () use ($renderer): bool {
        $xml = $renderer->renderUrlEntry([
            'loc' => 'https://example.com/extended',
            'alternates' => [['hreflang' => 'en', 'url' => 'https://example.com/en/extended']],
            'images' => [['loc' => 'https://cdn.example.com/extended.jpg']],
            'videos' => [[
                'thumbnailLoc' => 'https://cdn.example.com/extended-thumb.jpg',
                'title' => 'Extended video',
                'description' => 'Extended video description',
                'contentLoc' => 'https://cdn.example.com/extended.mp4',
            ]],
            'news' => [[
                'publicationName' => 'Example Daily',
                'publicationLanguage' => 'en',
                'publicationDate' => '2026-07-01',
                'title' => 'Extended news',
            ]],
        ]);

        return str_contains($xml, 'xmlns:xhtml="http://www.w3.org/1999/xhtml"')
            && str_contains($xml, 'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"')
            && str_contains($xml, 'xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"')
            && str_contains($xml, 'xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"')
            && str_contains($xml, '<xhtml:link')
            && str_contains($xml, '<image:image>')
            && str_contains($xml, '<video:video>')
            && str_contains($xml, '<news:news>');
    })(),
);

assertSameValue(
    'empty URL set renders safely',
    $xmlHeader
    . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>' . "\n",
    $renderer->renderUrlSet([]),
);

assertThrowsSeoException('invalid URL entry throws existing module exception style', static function () use ($renderer): void {
    $renderer->renderUrlEntry(['lastmod' => '2026-07-01']);
});

assertThrowsSeoException('invalid raw array loc throws existing module exception style', static function () use ($renderer): void {
    $renderer->renderUrlEntry(['loc' => 'not-a-url']);
});

assertThrowsSeoException('invalid raw array date-only lastmod throws module exception', static function () use ($renderer): void {
    $renderer->renderUrlEntry([
        'loc' => 'https://example.com/invalid-date',
        'lastmod' => '2026-02-31',
    ]);
});

assertThrowsSeoException('invalid raw array calendar ATOM lastmod throws module exception', static function () use ($renderer): void {
    $renderer->renderUrlEntry([
        'loc' => 'https://example.com/invalid-atom-date',
        'lastmod' => '2026-02-31T10:00:00+00:00',
    ]);
});

assertThrowsSeoException('invalid raw array changefreq throws module exception', static function () use ($renderer): void {
    $renderer->renderUrlEntry([
        'loc' => 'https://example.com/invalid-changefreq',
        'changefreq' => 'invalid',
    ]);
});

assertThrowsSeoException('negative raw array priority throws module exception', static function () use ($renderer): void {
    $renderer->renderUrlEntry([
        'loc' => 'https://example.com/negative-priority',
        'priority' => -0.1,
    ]);
});

assertThrowsSeoException('raw array priority above one throws module exception', static function () use ($renderer): void {
    $renderer->renderUrlEntry([
        'loc' => 'https://example.com/high-priority',
        'priority' => 1.1,
    ]);
});

assertThrowsSeoException('raw array priority above one integer throws module exception', static function () use ($renderer): void {
    $renderer->renderUrlEntry([
        'loc' => 'https://example.com/high-integer-priority',
        'priority' => 2,
    ]);
});

assertThrowsSeoException('non-numeric raw array priority throws module exception', static function () use ($renderer): void {
    $renderer->renderUrlEntry([
        'loc' => 'https://example.com/non-numeric-priority',
        'priority' => 'not-a-number',
    ]);
});

assertThrowsSeoException('non-array non-DTO URL entry throws existing module exception style', static function () use ($renderer): void {
    $renderer->renderUrlEntry('https://example.com/raw-string');
});

$generatorOutput = (new SitemapGeneratorService())->generateUrlSitemap([$firstUrl])->xml;
assertSameValue(
    'existing sitemap generator behavior remains unchanged',
    $xmlHeader
    . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><url><loc>https://example.com/articles/one</loc><lastmod>2026-07-01</lastmod><changefreq>daily</changefreq><priority>0.8</priority></url></urlset>' . "\n",
    $generatorOutput,
);

assertSameValue(
    'existing Phase 7A renderer behavior remains unchanged',
    '<script type="application/ld+json">{"@type":"WebPage"}</script>',
    (new JsonLdScriptRenderer())->render(['@type' => 'WebPage']),
);

assertSameValue(
    'existing Phase 7C fluent builder behavior remains unchanged',
    '<title>Phase 7E check</title>' . "\n"
    . '<meta name="robots" content="index,follow">' . "\n"
    . '<script type="application/ld+json">{"@type":"WebPage"}</script>',
    (new FluentSeoBuilder())
        ->title('Phase 7E check')
        ->schema(['@type' => 'WebPage'])
        ->render(new SeoHeadHtmlRenderer()),
);

assertSameValue(
    'existing Phase 7D Spatie adapter behavior remains unchanged',
    ['@type' => 'Article', 'headline' => 'Phase 7E unchanged'],
    (new SpatieSchemaAdapter())->toJsonLdSchemaDTO(new Phase7EFakeSpatieSchema())->jsonSerialize(),
);

echo "Phase 7E sitemap XML string renderer tests passed.\n";
