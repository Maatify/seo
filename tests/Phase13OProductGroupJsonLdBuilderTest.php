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

use Maatify\Seo\Web\JsonLd\Builder\JsonLdBuilderInterface;
use Maatify\Seo\Web\JsonLd\Builder\OrganizationJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\ProductGroupJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\ProductJsonLdBuilder;

function assertSameValue13OProductGroup(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertTrueValue13OProductGroup(string $label, bool $actual): void
{
    if (!$actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

$constructor = new ProductGroupJsonLdBuilder();
assertTrueValue13OProductGroup('product group implements builder interface', $constructor instanceof JsonLdBuilderInterface);
assertSameValue13OProductGroup('product group constructor seeds schema.org defaults', [
    '@context' => 'https://schema.org',
    '@type' => 'ProductGroup',
], $constructor->toArray());

$fields = (new ProductGroupJsonLdBuilder())
    ->setName('T-Shirt Line')
    ->setDescription('A line of t-shirts.')
    ->setUrl('https://example.com/t-shirts')
    ->setProductGroupID('TSHIRT-BASE')
    ->toArray();
assertSameValue13OProductGroup('product group scalar fields', [
    '@context' => 'https://schema.org',
    '@type' => 'ProductGroup',
    'name' => 'T-Shirt Line',
    'description' => 'A line of t-shirts.',
    'url' => 'https://example.com/t-shirts',
    'productGroupID' => 'TSHIRT-BASE',
], $fields);

assertSameValue13OProductGroup(
    'string brand is normalized to Brand',
    [
        '@type' => 'Brand',
        'name' => 'Maatify',
    ],
    (new ProductGroupJsonLdBuilder())->setBrand('Maatify')->toArray()['brand']
);

$rawBrand = [
    'name' => 'Raw Brand',
    '@context' => 'https://example.com/raw-brand-context',
];
assertSameValue13OProductGroup(
    'raw brand is preserved without type injection',
    $rawBrand,
    (new ProductGroupJsonLdBuilder())->setBrand($rawBrand)->toArray()['brand']
);

$brandBuilder = (new OrganizationJsonLdBuilder())->setName('Builder Brand');
assertSameValue13OProductGroup(
    'builder brand is composed without nested context',
    [
        '@type' => 'Organization',
        'name' => 'Builder Brand',
    ],
    (new ProductGroupJsonLdBuilder())->setBrand($brandBuilder)->toArray()['brand']
);

assertSameValue13OProductGroup(
    'variesBy preserves values and order',
    [
        'https://schema.org/color',
        'https://schema.org/size',
        'https://schema.org/material',
    ],
    (new ProductGroupJsonLdBuilder())->setVariesBy([
        'https://schema.org/color',
        'https://schema.org/size',
        'https://schema.org/material',
    ])->toArray()['variesBy']
);

$emptyVariants = new ProductGroupJsonLdBuilder();
$emptyVariantsBefore = $emptyVariants->toArray();
$emptyVariants->setHasVariant();
assertSameValue13OProductGroup('setHasVariant with no arguments is a no-op', $emptyVariantsBefore, $emptyVariants->toArray());
$emptyVariants->setHasVariant([]);
assertSameValue13OProductGroup('setHasVariant with an empty list is a no-op', $emptyVariantsBefore, $emptyVariants->toArray());

assertSameValue13OProductGroup(
    'setHasVariant stores a single raw variant as an object',
    [
        '@type' => 'Product',
        'sku' => 'TS-RAW-1',
    ],
    (new ProductGroupJsonLdBuilder())->setHasVariant([
        '@type' => 'Product',
        'sku' => 'TS-RAW-1',
    ])->toArray()['hasVariant']
);

$singleVariantBuilder = (new ProductJsonLdBuilder())->setSku('TS-BUILDER-1');
assertSameValue13OProductGroup(
    'setHasVariant composes a single builder without nested context',
    [
        '@type' => 'Product',
        'sku' => 'TS-BUILDER-1',
    ],
    (new ProductGroupJsonLdBuilder())->setHasVariant($singleVariantBuilder)->toArray()['hasVariant']
);

assertSameValue13OProductGroup(
    'setHasVariant flattens a numeric raw list',
    [
        [
            '@type' => 'Product',
            'sku' => 'TS-RAW-1',
        ],
        [
            '@type' => 'Product',
            'sku' => 'TS-RAW-2',
        ],
    ],
    (new ProductGroupJsonLdBuilder())->setHasVariant([
        ['@type' => 'Product', 'sku' => 'TS-RAW-1'],
        ['@type' => 'Product', 'sku' => 'TS-RAW-2'],
    ])->toArray()['hasVariant']
);

$numericBuilderOne = (new ProductJsonLdBuilder())->setSku('TS-BUILDER-1');
$numericBuilderTwo = (new ProductJsonLdBuilder())->setSku('TS-BUILDER-2');
assertSameValue13OProductGroup(
    'setHasVariant flattens a numeric builder list and preserves order',
    [
        [
            '@type' => 'Product',
            'sku' => 'TS-BUILDER-1',
        ],
        [
            '@type' => 'Product',
            'sku' => 'TS-BUILDER-2',
        ],
    ],
    (new ProductGroupJsonLdBuilder())->setHasVariant([
        $numericBuilderOne,
        $numericBuilderTwo,
    ])->toArray()['hasVariant']
);

$variadicBuilderOne = (new ProductJsonLdBuilder())->setSku('TS-VARIADIC-1');
$variadicBuilderTwo = (new ProductJsonLdBuilder())->setSku('TS-VARIADIC-2');
assertSameValue13OProductGroup(
    'setHasVariant accepts variadic builders',
    [
        [
            '@type' => 'Product',
            'sku' => 'TS-VARIADIC-1',
        ],
        [
            '@type' => 'Product',
            'sku' => 'TS-VARIADIC-2',
        ],
    ],
    (new ProductGroupJsonLdBuilder())->setHasVariant($variadicBuilderOne, $variadicBuilderTwo)->toArray()['hasVariant']
);

$mixedBuilder = (new ProductJsonLdBuilder())->setSku('TS-MIXED-BUILDER');
assertSameValue13OProductGroup(
    'setHasVariant accepts mixed raw and builder nodes',
    [
        [
            '@type' => 'Product',
            'sku' => 'TS-MIXED-BUILDER',
        ],
        [
            '@type' => 'Product',
            'sku' => 'TS-MIXED-RAW',
        ],
    ],
    (new ProductGroupJsonLdBuilder())->setHasVariant(
        $mixedBuilder,
        ['@type' => 'Product', 'sku' => 'TS-MIXED-RAW'],
    )->toArray()['hasVariant']
);

$addVariantLifecycle = new ProductGroupJsonLdBuilder();
$addVariantLifecycle->addVariant(['@type' => 'Product', 'sku' => 'TS-ADD-1']);
assertSameValue13OProductGroup('addVariant stores an object when absent', [
    '@type' => 'Product',
    'sku' => 'TS-ADD-1',
], $addVariantLifecycle->toArray()['hasVariant']);
$addVariantLifecycle->addVariant(['@type' => 'Product', 'sku' => 'TS-ADD-2']);
assertSameValue13OProductGroup('addVariant converts an object to a list', [
    [
        '@type' => 'Product',
        'sku' => 'TS-ADD-1',
    ],
    [
        '@type' => 'Product',
        'sku' => 'TS-ADD-2',
    ],
], $addVariantLifecycle->toArray()['hasVariant']);
$addVariantLifecycle->addVariant(['@type' => 'Product', 'sku' => 'TS-ADD-3']);
assertSameValue13OProductGroup('addVariant appends to a list in order', [
    [
        '@type' => 'Product',
        'sku' => 'TS-ADD-1',
    ],
    [
        '@type' => 'Product',
        'sku' => 'TS-ADD-2',
    ],
    [
        '@type' => 'Product',
        'sku' => 'TS-ADD-3',
    ],
], $addVariantLifecycle->toArray()['hasVariant']);

$addBuilderVariant = (new ProductJsonLdBuilder())->setSku('TS-ADD-BUILDER');
assertSameValue13OProductGroup(
    'addVariant composes a builder without nested context',
    [
        '@type' => 'Product',
        'sku' => 'TS-ADD-BUILDER',
    ],
    (new ProductGroupJsonLdBuilder())->addVariant($addBuilderVariant)->toArray()['hasVariant']
);

assertSameValue13OProductGroup(
    'setIsVariantOf casts a string to ProductGroup',
    [
        '@type' => 'ProductGroup',
        'productGroupID' => 'TSHIRT-BASE',
    ],
    (new ProductJsonLdBuilder())->setIsVariantOf('TSHIRT-BASE')->toArray()['isVariantOf']
);

$rawProductGroup = [
    '@type' => 'ProductGroup',
    'productGroupID' => 'RAW-GROUP',
    '@context' => 'https://example.com/raw-group-context',
];
assertSameValue13OProductGroup(
    'setIsVariantOf preserves a raw array exactly',
    $rawProductGroup,
    (new ProductJsonLdBuilder())->setIsVariantOf($rawProductGroup)->toArray()['isVariantOf']
);

$productGroupBuilder = (new ProductGroupJsonLdBuilder())->setProductGroupID('BUILDER-GROUP');
assertSameValue13OProductGroup(
    'setIsVariantOf composes a ProductGroup builder without nested context',
    [
        '@type' => 'ProductGroup',
        'productGroupID' => 'BUILDER-GROUP',
    ],
    (new ProductJsonLdBuilder())->setIsVariantOf($productGroupBuilder)->toArray()['isVariantOf']
);

assertSameValue13OProductGroup(
    'setInProductGroupWithID stores the relationship identifier',
    'TSHIRT-BASE',
    (new ProductJsonLdBuilder())->setInProductGroupWithID('TSHIRT-BASE')->toArray()['inProductGroupWithID']
);

$exactProductGroup = (new ProductGroupJsonLdBuilder())
    ->setName('T-Shirt Line')
    ->setProductGroupID('TSHIRT-BASE')
    ->setVariesBy([
        'https://schema.org/color',
        'https://schema.org/size',
    ])
    ->setHasVariant(
        (new ProductJsonLdBuilder())
            ->setSku('TS-RED-L')
            ->setColor('Red')
            ->setSize('L'),
        (new ProductJsonLdBuilder())
            ->setSku('TS-BLU-M')
            ->setColor('Blue')
            ->setSize('M'),
    )
    ->toArray();
assertSameValue13OProductGroup('exact ProductGroup multi-variant scenario', [
    '@context' => 'https://schema.org',
    '@type' => 'ProductGroup',
    'name' => 'T-Shirt Line',
    'productGroupID' => 'TSHIRT-BASE',
    'variesBy' => [
        'https://schema.org/color',
        'https://schema.org/size',
    ],
    'hasVariant' => [
        [
            '@type' => 'Product',
            'sku' => 'TS-RED-L',
            'color' => 'Red',
            'size' => 'L',
        ],
        [
            '@type' => 'Product',
            'sku' => 'TS-BLU-M',
            'color' => 'Blue',
            'size' => 'M',
        ],
    ],
], $exactProductGroup);

$exactProductRelationship = (new ProductJsonLdBuilder())
    ->setSku('TS-RED-L')
    ->setColor('Red')
    ->setSize('L')
    ->setIsVariantOf('TSHIRT-BASE')
    ->toArray();
assertSameValue13OProductGroup('exact Product to ProductGroup relationship scenario', [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'sku' => 'TS-RED-L',
    'color' => 'Red',
    'size' => 'L',
    'isVariantOf' => [
        '@type' => 'ProductGroup',
        'productGroupID' => 'TSHIRT-BASE',
    ],
], $exactProductRelationship);

echo "Phase 13O ProductGroup JSON-LD builder tests passed.\n";
