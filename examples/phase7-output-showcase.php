<?php

declare(strict_types=1);

$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
} else {
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
}

use Maatify\Seo\Shared\DTO\MetaTagsDTO;
use Maatify\Seo\Shared\DTO\Schema\JsonLdSchemaDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapAlternateUrlDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapUrlDTO;
use Maatify\Seo\Shared\Service\SitemapGeneratorService;
use Maatify\Seo\Web\Builder\FluentSeoBuilder;
use Maatify\Seo\Web\Render\JsonLdScriptRenderer;
use Maatify\Seo\Web\Render\MetaTagsHtmlRenderer;
use Maatify\Seo\Web\Render\OpenGraphHtmlRenderer;
use Maatify\Seo\Web\Render\SeoHeadHtmlRenderer;
use Maatify\Seo\Web\Render\TwitterCardHtmlRenderer;
use Maatify\Seo\Web\Schema\SpatieSchemaAdapter;
use Maatify\Seo\Web\SeoRender\DTO\SeoPagePayloadDTO;
use Maatify\Seo\Web\Sitemap\SitemapXmlStringRenderer;

function printSection(string $title, mixed $output): void
{
    echo "\n==============================\n";
    echo $title . "\n";
    echo "==============================\n";
    if (is_bool($output)) {
        echo ($output ? 'true' : 'false') . "\n";
        return;
    }
    if (is_array($output) || $output instanceof JsonSerializable) {
        echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
        return;
    }
    echo (string) $output . "\n";
}

final class Phase7ShowcaseToArraySchema
{
    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['@context' => 'https://schema.org', '@type' => 'Article', 'headline' => 'Fake local toArray schema'];
    }
}

final class Phase7ShowcaseJsonSerializeSchema implements JsonSerializable
{
    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return ['@context' => 'https://schema.org', '@type' => 'Product', 'name' => 'Fake local jsonSerialize schema'];
    }
}

final class Phase7ShowcaseScriptSchema
{
    public function toScript(): string
    {
        return '<script type="application/ld+json">{"@context":"https://schema.org","@type":"WebPage","name":"Fake local toScript schema"}</script>';
    }
}

$metaTags = new MetaTagsDTO(
    title: 'Phase 7 <Output> Showcase',
    description: 'Inspect rendered SEO helpers & DTO output.',
    canonicalUrl: 'https://example.test/phase-7?source=showcase&name=A&B',
    robots: 'index,follow,max-image-preview:large',
    openGraphTitle: 'OpenGraph <Title> & Showcase',
    openGraphDescription: 'OpenGraph description with "quotes" & symbols.',
    openGraphUrl: 'https://example.test/phase-7/og?ref=A&B',
    twitterTitle: 'Twitter Card <Title>',
    twitterDescription: 'Twitter description with "quotes" & symbols.',
    openGraphType: 'article',
    openGraphImage: 'https://cdn.example.test/images/phase-7.png?size=1200x630&v=1',
    twitterCard: 'summary_large_image',
    twitterImage: 'https://cdn.example.test/images/twitter-phase-7.png?size=1200x600&v=1',
);

$schemaArray = ['@context' => 'https://schema.org', '@type' => 'WebPage', 'name' => 'Phase 7 Showcase'];
$schemaDto = new JsonLdSchemaDTO(['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => 'Maatify SEO']);
$schemas = [$schemaArray, $schemaDto];

$metaRenderer = new MetaTagsHtmlRenderer();
printSection('MetaTagsHtmlRenderer::render() title / description / canonical / robots', $metaRenderer->render($metaTags));

$openGraphRenderer = new OpenGraphHtmlRenderer();
printSection('OpenGraphHtmlRenderer::render() og:title / og:description / og:type / og:url / og:image', $openGraphRenderer->render($metaTags));

$twitterRenderer = new TwitterCardHtmlRenderer();
printSection('TwitterCardHtmlRenderer::render() twitter:card / twitter:title / twitter:description / twitter:image', $twitterRenderer->render($metaTags));

$jsonLdRenderer = new JsonLdScriptRenderer();
printSection('JsonLdScriptRenderer::render() from array', $jsonLdRenderer->render($schemaArray));
printSection('JsonLdScriptRenderer::render() from JsonLdSchemaDTO', $jsonLdRenderer->render($schemaDto));

$headRenderer = new SeoHeadHtmlRenderer();
printSection('SeoHeadHtmlRenderer::render()', $headRenderer->render($metaTags, $schemas));
$headDto = $headRenderer->renderDto($metaTags, $schemas);
printSection('SeoHeadHtmlRenderer::renderDto()', $headDto);
$payload = new SeoPagePayloadDTO($metaTags, [$schemaDto]);
if (method_exists($headRenderer, 'renderPayload')) {
    printSection('SeoHeadHtmlRenderer::renderPayload()', $headRenderer->renderPayload($payload));
}
if (method_exists($headRenderer, 'renderPayloadDto')) {
    printSection('SeoHeadHtmlRenderer::renderPayloadDto()', $headRenderer->renderPayloadDto($payload));
}

printSection('SeoHeadHtmlDTO::$metaHtml', $headDto->metaHtml);
printSection('SeoHeadHtmlDTO::$openGraphHtml', $headDto->openGraphHtml);
printSection('SeoHeadHtmlDTO::$twitterCardHtml', $headDto->twitterCardHtml);
printSection('SeoHeadHtmlDTO::$jsonLdHtml', $headDto->jsonLdHtml);
printSection('SeoHeadHtmlDTO::$fullHtml', $headDto->fullHtml);
printSection('SeoHeadHtmlDTO::jsonSerialize() pretty JSON', $headDto->jsonSerialize());

$builder = (new FluentSeoBuilder())
    ->title('Builder Phase 7 Showcase')
    ->description('Built through FluentSeoBuilder.')
    ->canonical('https://example.test/builder')
    ->robots('index,follow')
    ->openGraphTitle('Builder OG title')
    ->openGraphDescription('Builder OG description')
    ->openGraphType('website')
    ->openGraphUrl('https://example.test/builder')
    ->openGraphImage('https://cdn.example.test/builder-og.png')
    ->twitterCard('summary_large_image')
    ->twitterTitle('Builder Twitter title')
    ->twitterDescription('Builder Twitter description')
    ->twitterImage('https://cdn.example.test/builder-twitter.png');

printSection('FluentSeoBuilder::schema()', $builder->schema($schemaDto)->render());
printSection('FluentSeoBuilder::schemas()', $builder->schemas([['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'name' => 'Builder breadcrumbs']])->render());
printSection('FluentSeoBuilder::buildMetaTags()', $builder->buildMetaTags());
printSection('FluentSeoBuilder::render()', $builder->render());
printSection('FluentSeoBuilder::renderDto()', $builder->renderDto());
printSection('FluentSeoBuilder::clearSchemas()', $builder->clearSchemas()->render());

$spatieAdapter = new SpatieSchemaAdapter();
if (method_exists($builder, 'spatieSchema')) {
    printSection('FluentSeoBuilder::spatieSchema()', $builder->spatieSchema(new Phase7ShowcaseToArraySchema(), $spatieAdapter)->render());
}

$fakeToArray = new Phase7ShowcaseToArraySchema();
$fakeJsonSerialize = new Phase7ShowcaseJsonSerializeSchema();
$fakeScript = new Phase7ShowcaseScriptSchema();
printSection('SpatieSchemaAdapter::supports() toArray fake', $spatieAdapter->supports($fakeToArray));
printSection('SpatieSchemaAdapter::supports() jsonSerialize fake', $spatieAdapter->supports($fakeJsonSerialize));
printSection('SpatieSchemaAdapter::supports() toScript fake', $spatieAdapter->supports($fakeScript));
printSection('SpatieSchemaAdapter::toJsonLdSchemaDTO() toArray fake', $spatieAdapter->toJsonLdSchemaDTO($fakeToArray));
printSection('SpatieSchemaAdapter::toJsonLdSchemaDTO() jsonSerialize fake', $spatieAdapter->toJsonLdSchemaDTO($fakeJsonSerialize));
printSection('SpatieSchemaAdapter::toJsonLdSchemaDTO() toScript fake', $spatieAdapter->toJsonLdSchemaDTO($fakeScript));
printSection('SpatieSchemaAdapter::toJsonLdSchemaDTOs() all fake objects', $spatieAdapter->toJsonLdSchemaDTOs([$fakeToArray, $fakeJsonSerialize, $fakeScript]));

$sitemapRenderer = new SitemapXmlStringRenderer();
$sitemapDto = new SitemapUrlDTO('https://example.test/products/phase-7', '2026-07-01', 'daily', 0.8);
$arrayEntry = ['loc' => 'https://example.test/blog/phase-7', 'lastmod' => '2026-07-01T12:00:00+00:00', 'changefreq' => 'weekly', 'priority' => '0.6'];
$specialCharsEntry = ['loc' => 'https://example.test/search?q=seo&name=A<B"C', 'lastmod' => null, 'changefreq' => '', 'priority' => null];
$minimalEntry = ['loc' => 'https://example.test/minimal', 'lastmod' => '', 'changefreq' => null, 'priority' => ''];
printSection('SitemapXmlStringRenderer::renderUrlEntry() with SitemapUrlDTO', $sitemapRenderer->renderUrlEntry($sitemapDto));
printSection('SitemapXmlStringRenderer::renderUrlEntry() with array URL entry', $sitemapRenderer->renderUrlEntry($arrayEntry));
printSection('SitemapXmlStringRenderer::renderUrlEntry() with XML special characters escaped', $sitemapRenderer->renderUrlEntry($specialCharsEntry));
printSection('SitemapXmlStringRenderer::renderUrlEntry() with null/empty optional fields omitted', $sitemapRenderer->renderUrlEntry($minimalEntry));
printSection('SitemapXmlStringRenderer::renderUrlSet()', $sitemapRenderer->renderUrlSet([$sitemapDto, $arrayEntry, $specialCharsEntry, $minimalEntry]));

$generatedSitemap = (new SitemapGeneratorService())->generateUrlSitemap([
    new SitemapUrlDTO(
        'https://example.test/generated',
        '2026-07-01',
        'daily',
        1.0,
        [new SitemapAlternateUrlDTO('en', 'https://example.test/en/generated')],
    ),
]);
printSection('SitemapGeneratorService::generateUrlSitemap() comparison DTO', $generatedSitemap);
