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
use Maatify\Seo\Web\JsonLd\Builder\AbstractJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\JsonLdBuildException;
use Maatify\Seo\Web\JsonLd\Builder\JsonLdBuilderInterface;

final class Phase13ATestBuilder extends AbstractJsonLdBuilder
{
}

function assertSameValue13A(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertTrueValue13A(string $label, bool $actual): void
{
    if (!$actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertThrowsJsonLdBuildException13A(string $label, callable $callback): void
{
    try {
        $callback();
    } catch (JsonLdBuildException $exception) {
        assertTrueValue13A($label . ' implements module exception interface', $exception instanceof SeoExceptionInterface);
        return;
    }

    fwrite(STDERR, "Assertion failed: {$label}\nExpected JSON-LD build exception.\n");
    exit(1);
}

$builder = new Phase13ATestBuilder(['@context' => 'https://schema.org']);

assertTrueValue13A('builder implements interface', $builder instanceof JsonLdBuilderInterface);
assertSameValue13A('constructor seeds schema', ['@context' => 'https://schema.org'], $builder->toArray());
assertSameValue13A('set is fluent', $builder, $builder->set('@type', 'Thing'));
assertTrueValue13A('has detects null values after set', $builder->set('name', null)->has('name'));
assertSameValue13A('get returns null value', null, $builder->get('name'));
assertSameValue13A('get returns missing value as null', null, $builder->get('missing'));
assertSameValue13A('remove is fluent', $builder, $builder->remove('name'));
assertSameValue13A('remove deletes key', false, $builder->has('name'));
assertSameValue13A(
    'toJson returns encoded schema with flags',
    '{"@context":"https:\/\/schema.org","@type":"Thing"}',
    $builder->toJson()
);
assertSameValue13A(
    'toJson honors caller flags',
    '{"@context":"https://schema.org","@type":"Thing"}',
    $builder->toJson(JSON_UNESCAPED_SLASHES)
);

$resource = fopen('php://memory', 'rb');
assertThrowsJsonLdBuildException13A('encoding failure throws library exception', static function () use ($resource): void {
    (new Phase13ATestBuilder(['bad' => $resource]))->toJson();
});
if (is_resource($resource)) {
    fclose($resource);
}

echo "Phase 13A JSON-LD builder foundation tests passed.\n";
