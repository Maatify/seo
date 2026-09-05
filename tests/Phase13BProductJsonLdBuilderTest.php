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
use Maatify\Seo\Web\JsonLd\Builder\JsonLdBuildException;
use Maatify\Seo\Web\JsonLd\Builder\OfferJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\ProductJsonLdBuilder;

function assertSameValue13B(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertTrueValue13B(string $label, bool $actual): void
{
    if (!$actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertThrowsJsonLdBuildException13B(string $label, callable $callback): void
{
    try {
        $callback();
    } catch (JsonLdBuildException) {
        return;
    }

    fwrite(STDERR, "Assertion failed: {$label}\nExpected JsonLdBuildException.\n");
    exit(1);
}

$builder = new ProductJsonLdBuilder();
assertTrueValue13B('product builder implements builder interface', $builder instanceof JsonLdBuilderInterface);
assertSameValue13B('product builder seeds schema.org product defaults', [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
], $builder->toArray());
assertSameValue13B('setName is fluent', $builder, $builder->setName('Maatify Demo Product'));

$schema = $builder
    ->setDescription('A demo product for JSON-LD output.')
    ->setSku('SKU-13B')
    ->setBrand('Maatify')
    ->setImage([
        'https://example.com/images/product-front.jpg',
        'https://example.com/images/product-side.jpg',
    ])
    ->setCategory('Software')
    ->setUrl('https://example.com/products/demo')
    ->setCurrency('USD')
    ->setPrice('19.99')
    ->setAvailability('https://schema.org/InStock')
    ->setCondition('https://schema.org/NewCondition')
    ->setOfferUrl('https://example.com/products/demo?purchase=1')
    ->setAggregateRating(4.8, 27)
    ->addReview('Jane Doe', 5, 'Excellent product.')
    ->addReview('John Doe', 4.5, 'Useful and well documented.')
    ->toArray();

assertSameValue13B('full product schema', [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => 'Maatify Demo Product',
    'description' => 'A demo product for JSON-LD output.',
    'sku' => 'SKU-13B',
    'brand' => [
        '@type' => 'Brand',
        'name' => 'Maatify',
    ],
    'image' => [
        'https://example.com/images/product-front.jpg',
        'https://example.com/images/product-side.jpg',
    ],
    'category' => 'Software',
    'url' => 'https://example.com/products/demo',
    'offers' => [
        '@type' => 'Offer',
        'priceCurrency' => 'USD',
        'price' => '19.99',
        'availability' => 'https://schema.org/InStock',
        'itemCondition' => 'https://schema.org/NewCondition',
        'url' => 'https://example.com/products/demo?purchase=1',
    ],
    'aggregateRating' => [
        '@type' => 'AggregateRating',
        'ratingValue' => 4.8,
        'reviewCount' => 27,
    ],
    'review' => [
        [
            '@type' => 'Review',
            'author' => [
                '@type' => 'Person',
                'name' => 'Jane Doe',
            ],
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => 5,
            ],
            'reviewBody' => 'Excellent product.',
        ],
        [
            '@type' => 'Review',
            'author' => [
                '@type' => 'Person',
                'name' => 'John Doe',
            ],
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => 4.5,
            ],
            'reviewBody' => 'Useful and well documented.',
        ],
    ],
], $schema);

assertSameValue13B(
    'single image remains a string',
    'https://example.com/image.jpg',
    (new ProductJsonLdBuilder())->setImage('https://example.com/image.jpg')->get('image')
);
assertSameValue13B(
    'toJson can encode product schema',
    '{"@context":"https://schema.org","@type":"Product","name":"Maatify Demo Product"}',
    (new ProductJsonLdBuilder())->setName('Maatify Demo Product')->toJson(JSON_UNESCAPED_SLASHES)
);

$productFields = (new ProductJsonLdBuilder())
    ->setGtin('00012345678905')
    ->setMpn('MPN-13O')
    ->setColor('Red')
    ->setSize('Large')
    ->setMaterial('Cotton')
    ->setPattern('Striped')
    ->toArray();
assertSameValue13B('product completeness fields', [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'gtin' => '00012345678905',
    'mpn' => 'MPN-13O',
    'color' => 'Red',
    'size' => 'Large',
    'material' => 'Cotton',
    'pattern' => 'Striped',
], $productFields);

$typedOffer = (new OfferJsonLdBuilder())->setPrice('19.99')->setPriceCurrency('USD');
assertSameValue13B(
    'setOffers accepts a single builder',
    [
        '@type' => 'Offer',
        'price' => '19.99',
        'priceCurrency' => 'USD',
    ],
    (new ProductJsonLdBuilder())->setOffers($typedOffer)->toArray()['offers']
);
assertSameValue13B(
    'setOffers accepts a single raw associative array',
    [
        '@type' => 'Offer',
        'price' => '29.99',
    ],
    (new ProductJsonLdBuilder())->setOffers([
        '@type' => 'Offer',
        'price' => '29.99',
    ])->toArray()['offers']
);
assertSameValue13B(
    'setOffers flattens a numeric list argument',
    [
        [
            '@type' => 'Offer',
            'price' => '10.00',
        ],
        [
            '@type' => 'Offer',
            'price' => '20.00',
        ],
    ],
    (new ProductJsonLdBuilder())->setOffers([
        [
            '@type' => 'Offer',
            'price' => '10.00',
        ],
        [
            '@type' => 'Offer',
            'price' => '20.00',
        ],
    ])->toArray()['offers']
);

$builder1 = (new OfferJsonLdBuilder())->setPrice('31.00');
$builder2 = (new OfferJsonLdBuilder())->setPrice('32.00');
assertTrueValue13B('numeric list regression uses OfferJsonLdBuilder instances', $builder1 instanceof OfferJsonLdBuilder && $builder2 instanceof OfferJsonLdBuilder);
assertSameValue13B(
    'setOffers resolves a numeric list of Offer builders in order',
    [
        [
            '@type' => 'Offer',
            'price' => '31.00',
        ],
        [
            '@type' => 'Offer',
            'price' => '32.00',
        ],
    ],
    (new ProductJsonLdBuilder())->setOffers([$builder1, $builder2])->toArray()['offers']
);

$variadicOffers = (new ProductJsonLdBuilder())->setOffers(
    (new OfferJsonLdBuilder())->setPrice(10),
    (new OfferJsonLdBuilder())->setPrice(20),
)->toArray()['offers'];
assertSameValue13B('setOffers accepts variadic builders', [
    [
        '@type' => 'Offer',
        'price' => 10,
    ],
    [
        '@type' => 'Offer',
        'price' => 20,
    ],
], $variadicOffers);

$mixedOffers = (new ProductJsonLdBuilder())->setOffers(
    (new OfferJsonLdBuilder())->setPrice(30),
    [
        '@type' => 'Offer',
        'price' => 40,
    ],
)->toArray()['offers'];
assertSameValue13B('setOffers accepts mixed builder and raw nodes', [
    [
        '@type' => 'Offer',
        'price' => 30,
    ],
    [
        '@type' => 'Offer',
        'price' => 40,
    ],
], $mixedOffers);

$noArgumentOffers = new ProductJsonLdBuilder();
$noArgumentBefore = $noArgumentOffers->toArray();
$noArgumentOffers->setOffers();
assertSameValue13B('setOffers with no arguments is a schema no-op', $noArgumentBefore, $noArgumentOffers->toArray());
$noArgumentOffers->setPrice('9.99');
assertSameValue13B('setOffers with no arguments keeps legacy state', [
    '@type' => 'Offer',
    'price' => '9.99',
], $noArgumentOffers->toArray()['offers']);

$emptyListOffers = new ProductJsonLdBuilder();
$emptyListBefore = $emptyListOffers->toArray();
$emptyListOffers->setOffers([]);
assertSameValue13B('setOffers with an empty list is a schema no-op', $emptyListBefore, $emptyListOffers->toArray());
$emptyListOffers->setPrice('8.99');
assertSameValue13B('setOffers with an empty list keeps legacy state', [
    '@type' => 'Offer',
    'price' => '8.99',
], $emptyListOffers->toArray()['offers']);

assertSameValue13B(
    'addOffer stores an object when offers are absent',
    [
        '@type' => 'Offer',
        'price' => '5.00',
    ],
    (new ProductJsonLdBuilder())->addOffer([
        '@type' => 'Offer',
        'price' => '5.00',
    ])->toArray()['offers']
);
assertSameValue13B(
    'addOffer converts an object to a list',
    [
        [
            '@type' => 'Offer',
            'price' => '5.00',
        ],
        [
            '@type' => 'Offer',
            'price' => '6.00',
        ],
    ],
    (new ProductJsonLdBuilder())
        ->addOffer(['@type' => 'Offer', 'price' => '5.00'])
        ->addOffer(['@type' => 'Offer', 'price' => '6.00'])
        ->toArray()['offers']
);
assertSameValue13B(
    'addOffer appends to an existing list',
    [
        [
            '@type' => 'Offer',
            'price' => '5.00',
        ],
        [
            '@type' => 'Offer',
            'price' => '6.00',
        ],
        [
            '@type' => 'Offer',
            'price' => '7.00',
        ],
    ],
    (new ProductJsonLdBuilder())
        ->setOffers([
            ['@type' => 'Offer', 'price' => '5.00'],
            ['@type' => 'Offer', 'price' => '6.00'],
        ])
        ->addOffer(['@type' => 'Offer', 'price' => '7.00'])
        ->toArray()['offers']
);

$legacyChain = (new ProductJsonLdBuilder())
    ->setCurrency('USD')
    ->setPrice('14.99')
    ->setAvailability('https://schema.org/InStock')
    ->setCondition('https://schema.org/NewCondition')
    ->setOfferUrl('https://example.com/product')
    ->toArray()['offers'];
assertSameValue13B('full legacy offer chain remains unchanged', [
    '@type' => 'Offer',
    'priceCurrency' => 'USD',
    'price' => '14.99',
    'availability' => 'https://schema.org/InStock',
    'itemCondition' => 'https://schema.org/NewCondition',
    'url' => 'https://example.com/product',
], $legacyChain);

assertSameValue13B(
    'setOffers replaces a legacy implicit offer',
    [
        '@type' => 'Offer',
        'price' => '99.99',
    ],
    (new ProductJsonLdBuilder())
        ->setCurrency('USD')
        ->setPrice('10.00')
        ->setOffers(['@type' => 'Offer', 'price' => '99.99'])
        ->toArray()['offers']
);
assertSameValue13B(
    'addOffer replaces a legacy implicit offer',
    [
        '@type' => 'Offer',
        'price' => '88.88',
    ],
    (new ProductJsonLdBuilder())
        ->setCurrency('USD')
        ->setPrice('10.00')
        ->addOffer(['@type' => 'Offer', 'price' => '88.88'])
        ->toArray()['offers']
);

$setOffersThenLegacy = (new ProductJsonLdBuilder())->setOffers(['@type' => 'Offer']);
assertThrowsJsonLdBuildException13B('legacy setter after setOffers throws', static function () use ($setOffersThenLegacy): void {
    $setOffersThenLegacy->setPrice(10);
});

$addOfferThenLegacy = (new ProductJsonLdBuilder())->addOffer(['@type' => 'Offer']);
assertThrowsJsonLdBuildException13B('legacy setter after addOffer throws', static function () use ($addOfferThenLegacy): void {
    $addOfferThenLegacy->setCurrency('USD');
});

assertSameValue13B(
    'generic offers setter keeps legacy state behavior',
    [
        '@type' => 'Offer',
        'priceCurrency' => 'USD',
        'price' => '19.99',
    ],
    (new ProductJsonLdBuilder())
        ->set('offers', ['@type' => 'Offer', 'priceCurrency' => 'USD'])
        ->setPrice('19.99')
        ->toArray()['offers']
);
assertSameValue13B(
    'remove offers resets explicit state',
    [
        '@type' => 'Offer',
        'price' => 10,
    ],
    (new ProductJsonLdBuilder())
        ->setOffers(['@type' => 'Offer', 'price' => 99])
        ->remove('offers')
        ->setPrice(10)
        ->toArray()['offers']
);

echo "Phase 13B product JSON-LD builder tests passed.\n";
