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

use Maatify\Seo\Web\JsonLd\Builder\AggregateOfferJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\JsonLdBuilderInterface;
use Maatify\Seo\Web\JsonLd\Builder\OfferJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\ProductJsonLdBuilder;

function assertSameValue13OAggregateOffer(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertTrueValue13OAggregateOffer(string $label, bool $actual): void
{
    if (!$actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

$constructor = new AggregateOfferJsonLdBuilder();
assertTrueValue13OAggregateOffer('aggregate offer implements builder interface', $constructor instanceof JsonLdBuilderInterface);
assertSameValue13OAggregateOffer('aggregate offer constructor seeds schema.org defaults', [
    '@context' => 'https://schema.org',
    '@type' => 'AggregateOffer',
], $constructor->toArray());

$fields = (new AggregateOfferJsonLdBuilder())
    ->setLowPrice('10.00')
    ->setHighPrice(50)
    ->setPriceCurrency('USD')
    ->setOfferCount(7)
    ->setAvailability('https://schema.org/InStock')
    ->toArray();
assertSameValue13OAggregateOffer('aggregate offer scalar fields', [
    '@context' => 'https://schema.org',
    '@type' => 'AggregateOffer',
    'lowPrice' => '10.00',
    'highPrice' => 50,
    'priceCurrency' => 'USD',
    'offerCount' => 7,
    'availability' => 'https://schema.org/InStock',
], $fields);

assertSameValue13OAggregateOffer(
    'setOffers stores a single raw offer as an object',
    [
        '@type' => 'Offer',
        'price' => '19.99',
    ],
    (new AggregateOfferJsonLdBuilder())->setOffers([
        '@type' => 'Offer',
        'price' => '19.99',
    ])->toArray()['offers']
);

$rawOfferWithContext = [
    '@type' => 'Offer',
    'price' => '18.99',
    '@context' => 'https://example.com/raw-offer-context',
];
assertSameValue13OAggregateOffer(
    'setOffers preserves raw offer arrays',
    $rawOfferWithContext,
    (new AggregateOfferJsonLdBuilder())->setOffers($rawOfferWithContext)->toArray()['offers']
);

$singleOfferBuilder = (new OfferJsonLdBuilder())->setPrice('17.99')->setPriceCurrency('USD');
assertSameValue13OAggregateOffer(
    'setOffers resolves a single builder without nested context',
    [
        '@type' => 'Offer',
        'price' => '17.99',
        'priceCurrency' => 'USD',
    ],
    (new AggregateOfferJsonLdBuilder())->setOffers($singleOfferBuilder)->toArray()['offers']
);

assertSameValue13OAggregateOffer(
    'setOffers flattens a single-item numeric list',
    [
        '@type' => 'Offer',
        'price' => '16.99',
    ],
    (new AggregateOfferJsonLdBuilder())->setOffers([
        ['@type' => 'Offer', 'price' => '16.99'],
    ])->toArray()['offers']
);

assertSameValue13OAggregateOffer(
    'setOffers flattens a numeric raw list and preserves order',
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
    (new AggregateOfferJsonLdBuilder())->setOffers([
        ['@type' => 'Offer', 'price' => '10.00'],
        ['@type' => 'Offer', 'price' => '20.00'],
    ])->toArray()['offers']
);

$builderOne = (new OfferJsonLdBuilder())->setPrice('11.00');
$builderTwo = (new OfferJsonLdBuilder())->setPrice('22.00');
assertSameValue13OAggregateOffer(
    'setOffers flattens a numeric builder list and strips nested contexts',
    [
        [
            '@type' => 'Offer',
            'price' => '11.00',
        ],
        [
            '@type' => 'Offer',
            'price' => '22.00',
        ],
    ],
    (new AggregateOfferJsonLdBuilder())->setOffers([$builderOne, $builderTwo])->toArray()['offers']
);

$variadicOne = (new OfferJsonLdBuilder())->setPrice('12.00');
$variadicTwo = (new OfferJsonLdBuilder())->setPrice('24.00');
assertSameValue13OAggregateOffer(
    'setOffers accepts variadic builders',
    [
        [
            '@type' => 'Offer',
            'price' => '12.00',
        ],
        [
            '@type' => 'Offer',
            'price' => '24.00',
        ],
    ],
    (new AggregateOfferJsonLdBuilder())->setOffers($variadicOne, $variadicTwo)->toArray()['offers']
);

$mixedBuilder = (new OfferJsonLdBuilder())->setPrice('13.00');
assertSameValue13OAggregateOffer(
    'setOffers accepts mixed builder and raw nodes',
    [
        [
            '@type' => 'Offer',
            'price' => '13.00',
        ],
        [
            '@type' => 'Offer',
            'price' => '26.00',
        ],
    ],
    (new AggregateOfferJsonLdBuilder())->setOffers(
        $mixedBuilder,
        ['@type' => 'Offer', 'price' => '26.00'],
    )->toArray()['offers']
);

$emptyOffers = new AggregateOfferJsonLdBuilder();
$emptyOffersBefore = $emptyOffers->toArray();
$emptyOffers->setOffers();
assertSameValue13OAggregateOffer('setOffers with no arguments is a no-op', $emptyOffersBefore, $emptyOffers->toArray());
$emptyOffers->setOffers([]);
assertSameValue13OAggregateOffer('setOffers with an empty list is a no-op', $emptyOffersBefore, $emptyOffers->toArray());

$addOfferLifecycle = new AggregateOfferJsonLdBuilder();
$addOfferLifecycle->addOffer(['@type' => 'Offer', 'price' => '5.00']);
assertSameValue13OAggregateOffer('addOffer stores an object when offers are absent', [
    '@type' => 'Offer',
    'price' => '5.00',
], $addOfferLifecycle->toArray()['offers']);
$addOfferLifecycle->addOffer(['@type' => 'Offer', 'price' => '6.00']);
assertSameValue13OAggregateOffer('addOffer converts an object to a list', [
    [
        '@type' => 'Offer',
        'price' => '5.00',
    ],
    [
        '@type' => 'Offer',
        'price' => '6.00',
    ],
], $addOfferLifecycle->toArray()['offers']);
$addOfferLifecycle->addOffer(['@type' => 'Offer', 'price' => '7.00']);
assertSameValue13OAggregateOffer('addOffer appends to a list in order', [
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
], $addOfferLifecycle->toArray()['offers']);

$nestedOffer = (new OfferJsonLdBuilder())->setPrice('8.00');
assertSameValue13OAggregateOffer(
    'addOffer resolves a builder without nested context',
    [
        '@type' => 'Offer',
        'price' => '8.00',
    ],
    (new AggregateOfferJsonLdBuilder())->addOffer($nestedOffer)->toArray()['offers']
);

$exactProduct = (new ProductJsonLdBuilder())
    ->setName('Widget Collection')
    ->setOffers(
        (new AggregateOfferJsonLdBuilder())
            ->setLowPrice('10.00')
            ->setHighPrice('50.00')
            ->setPriceCurrency('USD'),
    )
    ->toArray();
assertSameValue13OAggregateOffer('exact Product with AggregateOffer scenario', [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => 'Widget Collection',
    'offers' => [
        '@type' => 'AggregateOffer',
        'lowPrice' => '10.00',
        'highPrice' => '50.00',
        'priceCurrency' => 'USD',
    ],
], $exactProduct);

$aggregateWithNestedOffers = (new AggregateOfferJsonLdBuilder())
    ->setLowPrice('10.00')
    ->setHighPrice('50.00')
    ->setPriceCurrency('USD')
    ->setOffers([
        (new OfferJsonLdBuilder())->setPrice('10.00'),
        (new OfferJsonLdBuilder())->setPrice('50.00'),
    ])
    ->toArray();
assertSameValue13OAggregateOffer('aggregate offer keeps properties with nested offer list', [
    '@context' => 'https://schema.org',
    '@type' => 'AggregateOffer',
    'lowPrice' => '10.00',
    'highPrice' => '50.00',
    'priceCurrency' => 'USD',
    'offers' => [
        [
            '@type' => 'Offer',
            'price' => '10.00',
        ],
        [
            '@type' => 'Offer',
            'price' => '50.00',
        ],
    ],
], $aggregateWithNestedOffers);

assertSameValue13OAggregateOffer(
    'aggregate offer toJson preserves the root context contract',
    '{"@context":"https://schema.org","@type":"AggregateOffer","lowPrice":"10.00"}',
    (new AggregateOfferJsonLdBuilder())->setLowPrice('10.00')->toJson(JSON_UNESCAPED_SLASHES)
);

echo "Phase 13O AggregateOffer JSON-LD builder tests passed.\n";
