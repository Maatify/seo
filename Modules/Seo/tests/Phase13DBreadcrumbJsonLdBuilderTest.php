<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Exception/SeoExceptionInterface.php';
require_once __DIR__ . '/../src/Web/JsonLd/Builder/JsonLdBuildException.php';
require_once __DIR__ . '/../src/Web/JsonLd/Builder/JsonLdBuilderInterface.php';
require_once __DIR__ . '/../src/Web/JsonLd/Builder/JsonLdBuilderTrait.php';
require_once __DIR__ . '/../src/Web/JsonLd/Builder/AbstractJsonLdBuilder.php';
require_once __DIR__ . '/../src/Web/JsonLd/Builder/BreadcrumbJsonLdBuilder.php';

use Maatify\Seo\Web\JsonLd\Builder\BreadcrumbJsonLdBuilder;

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new \RuntimeException("Test Failed: $message\nExpected: " . print_r($expected, true) . "\nActual: " . print_r($actual, true));
    }
}

function testEmptyBreadcrumbs(): void
{
    $builder = new BreadcrumbJsonLdBuilder();
    $array = $builder->toArray();

    assertSameValue('https://schema.org', $array['@context'], 'Context should be schema.org');
    assertSameValue('BreadcrumbList', $array['@type'], 'Type should be BreadcrumbList');
    assertSameValue([], $array['itemListElement'], 'itemListElement should be empty initially');
}

function testAddSingleItem(): void
{
    $builder = new BreadcrumbJsonLdBuilder();
    $builder->addItem('Home', 'https://example.com');

    $array = $builder->toArray();
    assertSameValue(1, count($array['itemListElement']), 'Should have 1 item');
    assertSameValue('ListItem', $array['itemListElement'][0]['@type'], 'Item type should be ListItem');
    assertSameValue(1, $array['itemListElement'][0]['position'], 'Position should be 1');
    assertSameValue('Home', $array['itemListElement'][0]['name'], 'Name should be Home');
    assertSameValue('https://example.com', $array['itemListElement'][0]['item'], 'Item url should be correct');
}

function testAddMultipleItemsAndOrder(): void
{
    $builder = new BreadcrumbJsonLdBuilder();
    $builder->addItems([
        ['name' => 'Home', 'url' => 'https://example.com'],
        ['name' => 'Category', 'url' => 'https://example.com/category'],
    ]);
    $builder->addBreadcrumb('Product', 'https://example.com/category/product');

    $array = $builder->toArray();
    assertSameValue(3, count($array['itemListElement']), 'Should have 3 items');

    assertSameValue(1, $array['itemListElement'][0]['position'], 'Item 1 position should be 1');
    assertSameValue('Home', $array['itemListElement'][0]['name'], 'Item 1 name should be Home');

    assertSameValue(2, $array['itemListElement'][1]['position'], 'Item 2 position should be 2');
    assertSameValue('Category', $array['itemListElement'][1]['name'], 'Item 2 name should be Category');

    assertSameValue(3, $array['itemListElement'][2]['position'], 'Item 3 position should be 3');
    assertSameValue('Product', $array['itemListElement'][2]['name'], 'Item 3 name should be Product');
}

function testClearItems(): void
{
    $builder = new BreadcrumbJsonLdBuilder();
    $builder->addItem('Home', 'https://example.com');
    $builder->clearItems();

    $array = $builder->toArray();
    assertSameValue([], $array['itemListElement'], 'itemListElement should be empty after clear');
}

try {
    testEmptyBreadcrumbs();
    testAddSingleItem();
    testAddMultipleItemsAndOrder();
    testClearItems();
    echo "BreadcrumbJsonLdBuilderTest passed successfully.\n";
} catch (\Throwable $e) {
    echo $e->getMessage() . "\n";
    exit(1);
}
