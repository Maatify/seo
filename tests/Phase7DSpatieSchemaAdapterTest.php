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
use Maatify\Seo\Shared\DTO\Schema\JsonLdSchemaDTO;
use Maatify\Seo\Web\Builder\FluentSeoBuilder;
use Maatify\Seo\Web\Render\SeoHeadHtmlRenderer;
use Maatify\Seo\Web\Schema\SpatieSchemaAdapter;

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

function assertFalseValue(string $label, bool $actual): void
{
    if ($actual) {
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

final class FakeToArraySchema
{
    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['@type' => 'Article', 'headline' => 'Array schema'];
    }
}

final class FakeJsonSerializeSchema
{
    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return ['@type' => 'Product', 'name' => 'Serialized schema'];
    }
}

final class FakeScriptSchema
{
    public function toScript(): string
    {
        return '<script type="application/ld+json">{"@type":"WebPage","name":"Script schema"}</script>';
    }
}

final class FakeInvalidSchema
{
}

final class FakeEmptyArraySchema
{
    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [];
    }
}

final class FakeListArraySchema
{
    /** @return list<string> */
    public function toArray(): array
    {
        return ['not associative'];
    }
}

$adapter = new SpatieSchemaAdapter();

$arrayDto = $adapter->toJsonLdSchemaDTO(new FakeToArraySchema());
assertTrueValue('toArray conversion returns JsonLdSchemaDTO', $arrayDto instanceof JsonLdSchemaDTO);
assertSameValue('toArray schema maps to DTO', ['@type' => 'Article', 'headline' => 'Array schema'], $arrayDto->jsonSerialize());
assertTrueValue('supports returns true for toArray schema', $adapter->supports(new FakeToArraySchema()));

$jsonSerializeDto = $adapter->toJsonLdSchemaDTO(new FakeJsonSerializeSchema());
assertSameValue('jsonSerialize schema maps to DTO', ['@type' => 'Product', 'name' => 'Serialized schema'], $jsonSerializeDto->jsonSerialize());
assertTrueValue('supports returns true for jsonSerialize schema', $adapter->supports(new FakeJsonSerializeSchema()));

$scriptDto = $adapter->toJsonLdSchemaDTO(new FakeScriptSchema());
assertSameValue('toScript schema maps to DTO', ['@type' => 'WebPage', 'name' => 'Script schema'], $scriptDto->jsonSerialize());
assertTrueValue('supports returns true for toScript schema', $adapter->supports(new FakeScriptSchema()));

$multipleSchemas = $adapter->toJsonLdSchemaDTOs([
    new FakeToArraySchema(),
    new FakeJsonSerializeSchema(),
    new FakeScriptSchema(),
]);
assertSameValue('multiple schema conversion count', 3, count($multipleSchemas));
assertSameValue('multiple schema conversion preserves order', ['@type' => 'Product', 'name' => 'Serialized schema'], $multipleSchemas[1]->jsonSerialize());

assertFalseValue('supports returns false for invalid object', $adapter->supports(new FakeInvalidSchema()));
assertFalseValue('supports returns false for empty array output', $adapter->supports(new FakeEmptyArraySchema()));
assertFalseValue('supports returns false for list array output', $adapter->supports(new FakeListArraySchema()));

assertThrowsSeoException('invalid object throws module exception', static function () use ($adapter): void {
    $adapter->toJsonLdSchemaDTO(new FakeInvalidSchema());
});

assertThrowsSeoException('empty array output throws module exception', static function () use ($adapter): void {
    $adapter->toJsonLdSchemaDTO(new FakeEmptyArraySchema());
});

assertThrowsSeoException('list array output throws module exception', static function () use ($adapter): void {
    $adapter->toJsonLdSchemaDTO(new FakeListArraySchema());
});

$builderOutput = (new FluentSeoBuilder())
    ->title('Spatie builder')
    ->spatieSchema(new FakeToArraySchema(), $adapter)
    ->render(new SeoHeadHtmlRenderer());

assertSameValue(
    'FluentSeoBuilder spatieSchema renders adapter DTO',
    '<title>Spatie builder</title>' . "\n"
    . '<meta name="robots" content="index,follow">' . "\n"
    . '<script type="application/ld+json">{"@type":"Article","headline":"Array schema"}</script>',
    $builderOutput,
);

$existingBuilderOutput = (new FluentSeoBuilder())
    ->title('Existing schema')
    ->schema(['@type' => 'Organization'])
    ->render(new SeoHeadHtmlRenderer());

assertSameValue(
    'Existing Phase 7C schema behavior remains unchanged',
    '<title>Existing schema</title>' . "\n"
    . '<meta name="robots" content="index,follow">' . "\n"
    . '<script type="application/ld+json">{"@type":"Organization"}</script>',
    $existingBuilderOutput,
);

assertSameValue(
    'Existing Phase 7A renderer behavior remains unchanged',
    '<script type="application/ld+json">{"@type":"WebPage"}</script>',
    (new Maatify\Seo\Web\Render\JsonLdScriptRenderer())->render(new JsonLdSchemaDTO(['@type' => 'WebPage'])),
);

echo "Phase 7D Spatie schema adapter tests passed.\n";
