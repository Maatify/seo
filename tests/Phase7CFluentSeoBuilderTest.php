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
use Maatify\Seo\Shared\DTO\MetaTagsDTO;
use Maatify\Seo\Shared\DTO\Schema\JsonLdSchemaDTO;
use Maatify\Seo\Web\Builder\FluentSeoBuilder;
use Maatify\Seo\Web\DTO\SeoHeadHtmlDTO;
use Maatify\Seo\Web\Render\SeoHeadHtmlRenderer;

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
    }

    fwrite(STDERR, "Assertion failed: {$label}\nExpected SEO module exception.\n");
    exit(1);
}

$metaTags = (new FluentSeoBuilder())
    ->title('Fluent Title')
    ->description('Fluent description')
    ->canonical('https://example.com/fluent')
    ->robots('noindex,nofollow')
    ->buildMetaTags();

assertTrueValue('buildMetaTags returns MetaTagsDTO', $metaTags instanceof MetaTagsDTO);
assertSameValue('title is mapped', 'Fluent Title', $metaTags->title);
assertSameValue('description is mapped', 'Fluent description', $metaTags->description);
assertSameValue('canonical is mapped', 'https://example.com/fluent', $metaTags->canonicalUrl);
assertSameValue('robots is mapped', 'noindex,nofollow', $metaTags->robots);

$defaultRobots = (new FluentSeoBuilder())->title('Default robots')->buildMetaTags();
assertSameValue('robots defaults to index,follow', 'index,follow', $defaultRobots->robots);

$socialMetaTags = (new FluentSeoBuilder())
    ->title('Social Title')
    ->openGraphTitle('OG Title')
    ->openGraphDescription('OG Description')
    ->openGraphType('article')
    ->openGraphUrl('https://example.com/og')
    ->openGraphImage('https://example.com/og.jpg')
    ->twitterCard('summary_large_image')
    ->twitterTitle('Twitter Title')
    ->twitterDescription('Twitter Description')
    ->twitterImage('https://example.com/twitter.jpg')
    ->buildMetaTags();

assertSameValue('OpenGraph title is mapped', 'OG Title', $socialMetaTags->openGraphTitle);
assertSameValue('OpenGraph description is mapped', 'OG Description', $socialMetaTags->openGraphDescription);
assertSameValue('OpenGraph type is mapped', 'article', $socialMetaTags->openGraphType);
assertSameValue('OpenGraph url is mapped', 'https://example.com/og', $socialMetaTags->openGraphUrl);
assertSameValue('OpenGraph image is mapped', 'https://example.com/og.jpg', $socialMetaTags->openGraphImage);
assertSameValue('Twitter card is mapped', 'summary_large_image', $socialMetaTags->twitterCard);
assertSameValue('Twitter title is mapped', 'Twitter Title', $socialMetaTags->twitterTitle);
assertSameValue('Twitter description is mapped', 'Twitter Description', $socialMetaTags->twitterDescription);
assertSameValue('Twitter image is mapped', 'https://example.com/twitter.jpg', $socialMetaTags->twitterImage);

$renderer = new SeoHeadHtmlRenderer();
$schemaDto = new JsonLdSchemaDTO(['@type' => 'WebPage', 'name' => 'DTO Schema']);
$builder = (new FluentSeoBuilder())
    ->title('Rendered Title')
    ->description('Rendered description')
    ->schema($schemaDto)
    ->schema(['@type' => 'Organization', 'name' => 'Array Schema']);

$expectedMetaTags = new MetaTagsDTO(
    title: 'Rendered Title',
    description: 'Rendered description',
    canonicalUrl: null,
    robots: 'index,follow',
);
$expectedSchemas = [
    $schemaDto,
    new JsonLdSchemaDTO(['@type' => 'Organization', 'name' => 'Array Schema']),
];

assertSameValue(
    'render returns the same output as SeoHeadHtmlRenderer',
    $renderer->render($expectedMetaTags, $expectedSchemas),
    $builder->render($renderer),
);

$renderDto = $builder->renderDto($renderer);
assertTrueValue('renderDto returns SeoHeadHtmlDTO', $renderDto instanceof SeoHeadHtmlDTO);
assertSameValue('renderDto fullHtml matches render', $builder->render($renderer), $renderDto->fullHtml);

$multipleSchemasOutput = (new FluentSeoBuilder())
    ->title('Multiple schemas')
    ->schemas([
        new JsonLdSchemaDTO(['@type' => 'WebPage']),
        ['@type' => 'Organization'],
    ])
    ->render($renderer);

assertSameValue(
    'multiple schemas render in order',
    '<title>Multiple schemas</title>' . "\n"
    . '<meta name="robots" content="index,follow">' . "\n"
    . '<script type="application/ld+json">{"@type":"WebPage"}</script>' . "\n"
    . '<script type="application/ld+json">{"@type":"Organization"}</script>',
    $multipleSchemasOutput,
);

$clearedSchemasOutput = (new FluentSeoBuilder())
    ->title('Cleared schemas')
    ->schema(['@type' => 'WebPage'])
    ->clearSchemas()
    ->render($renderer);

assertSameValue(
    'clearSchemas removes all schema output',
    '<title>Cleared schemas</title>' . "\n" . '<meta name="robots" content="index,follow">',
    $clearedSchemasOutput,
);

assertThrowsSeoException('missing title throws module exception', static function (): void {
    (new FluentSeoBuilder())->buildMetaTags();
});

assertThrowsSeoException('empty title throws module exception', static function (): void {
    (new FluentSeoBuilder())->title('');
});

assertThrowsSeoException('invalid schema input throws module exception', static function (): void {
    (new FluentSeoBuilder())->title('Invalid schema')->schemas([['not associative']]);
});

assertSameValue(
    'Phase 7A renderer behavior remains unchanged',
    '<title>Regression</title>' . "\n" . '<meta name="robots" content="index,follow">',
    $renderer->render(new MetaTagsDTO('Regression', null, null)),
);

echo "Phase 7C fluent SEO builder tests passed.\n";
