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
use Maatify\Seo\Web\JsonLd\Builder\ProductGroupJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\ProductJsonLdBuilder;
use Maatify\Seo\Web\Validation\DTO\SeoValidationResultDTO;
use Maatify\Seo\Web\Validation\SeoMetaValidator;

function assertSameValue13PAggregateProductGroup(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertFalseValue13PAggregateProductGroup(string $label, bool $actual): void
{
    if ($actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertIssue13PAggregateProductGroup(string $label, SeoValidationResultDTO $result, string $code, string $field): void
{
    foreach ($result->issues as $issue) {
        if ($issue->code === $code && $issue->field === $field) {
            return;
        }
    }

    fwrite(STDERR, "Assertion failed: {$label}\nMissing issue [{$code}] at [{$field}].\nActual:\n" . var_export($result->toArray(), true) . "\n");
    exit(1);
}

function assertNoIssues13PAggregateProductGroup(string $label, SeoValidationResultDTO $result): void
{
    assertSameValue13PAggregateProductGroup($label . ' has no issues', [], $result->issues);
}

/** @return array<string, mixed> */
function validMeta13PAggregateProductGroup(mixed $jsonLd): array
{
    return [
        'title' => 'A valid aggregate and product group title',
        'description' => 'This description is long enough for AggregateOffer and ProductGroup semantic validation tests.',
        'jsonLd' => $jsonLd,
    ];
}

$validAggregateOffer = [
    '@context' => 'https://schema.org',
    '@type' => 'https://schema.org/AggregateOffer',
    'lowPrice' => '10.00',
    'highPrice' => 50,
    'priceCurrency' => 'USD',
    'offerCount' => 5,
    'availability' => 'https://schema.org/InStock',
    'offers' => [
        [
            '@type' => 'Demand',
            'price' => [],
        ],
        [
            '@type' => 'Offer',
            'price' => 19.99,
        ],
        [
            '@type' => 'AggregateOffer',
            'lowPrice' => 20,
            'highPrice' => '30.00',
            'priceCurrency' => 'USD',
            'offerCount' => 2,
        ],
        [
            '@type' => 'OfferForLease',
            'seller' => [],
        ],
        [
            '@type' => 'OfferForPurchase',
            'price' => [],
        ],
    ],
];
assertNoIssues13PAggregateProductGroup(
    'valid AggregateOffer catalog and offers relationships',
    SeoMetaValidator::validate(validMeta13PAggregateProductGroup($validAggregateOffer))
);

$validProductGroup = [
    '@context' => 'https://schema.org',
    '@type' => 'ProductGroup',
    'name' => 'T-Shirt Line',
    'description' => 'A line of t-shirts.',
    'brand' => [
        '@type' => 'Brand',
        'name' => 'Maatify',
    ],
    'url' => 'https://example.com/t-shirts',
    'productGroupID' => 'TSHIRT-BASE',
    'variesBy' => [
        'https://schema.org/color',
        [
            '@type' => 'DefinedTerm',
            'name' => 'size',
        ],
    ],
    'hasVariant' => [
        [
            '@type' => 'Product',
            'sku' => 'TS-RED-L',
            'color' => 'Red',
        ],
        [
            '@type' => 'Product',
            'sku' => 'TS-BLU-M',
            'color' => 'Blue',
        ],
    ],
];
assertNoIssues13PAggregateProductGroup(
    'valid ProductGroup catalog and variant relationships',
    SeoMetaValidator::validate(validMeta13PAggregateProductGroup($validProductGroup))
);

assertNoIssues13PAggregateProductGroup(
    'ProductGroup brand accepts Organization',
    SeoMetaValidator::validate(validMeta13PAggregateProductGroup([
        '@type' => 'ProductGroup',
        'brand' => [
            '@type' => 'Organization',
            'name' => 'Maatify',
        ],
    ]))
);

$aggregateInvalidCases = [
    'lowPrice' => true,
    'highPrice' => [],
    'priceCurrency' => 123,
    'offerCount' => 1.5,
    'availability' => '',
    'offers' => [],
];
foreach ($aggregateInvalidCases as $property => $value) {
    $result = SeoMetaValidator::validate(validMeta13PAggregateProductGroup([
        '@type' => 'AggregateOffer',
        $property => $value,
    ]));
    assertFalseValue13PAggregateProductGroup('invalid AggregateOffer property ' . $property . ' fails', $result->isValid);
    assertIssue13PAggregateProductGroup(
        'invalid AggregateOffer property ' . $property . ' has deterministic path',
        $result,
        'json_ld_invalid_property',
        'jsonLd.' . $property
    );
}

$invalidAggregateOfferRelationship = SeoMetaValidator::validate(validMeta13PAggregateProductGroup([
    '@type' => 'AggregateOffer',
    'offers' => [
        '@type' => 'Product',
    ],
]));
assertFalseValue13PAggregateProductGroup('invalid AggregateOffer offers relationship fails', $invalidAggregateOfferRelationship->isValid);
assertIssue13PAggregateProductGroup(
    'invalid AggregateOffer offers relationship has deterministic path',
    $invalidAggregateOfferRelationship,
    'json_ld_invalid_relationship',
    'jsonLd.offers'
);

$aggregateOfferListPath = SeoMetaValidator::validate(validMeta13PAggregateProductGroup([
    '@type' => 'AggregateOffer',
    'offers' => [
        [
            '@type' => 'Offer',
            'price' => 19.99,
        ],
        [
            '@type' => 'Product',
        ],
    ],
]));
assertFalseValue13PAggregateProductGroup('invalid AggregateOffer offers list item fails', $aggregateOfferListPath->isValid);
assertIssue13PAggregateProductGroup(
    'invalid AggregateOffer offers list item preserves order and path',
    $aggregateOfferListPath,
    'json_ld_invalid_relationship',
    'jsonLd.offers.1'
);

$aggregateOfferEmptyNode = SeoMetaValidator::validate(validMeta13PAggregateProductGroup([
    '@type' => 'AggregateOffer',
    'offers' => [
        [],
    ],
]));
assertFalseValue13PAggregateProductGroup('empty AggregateOffer offer node fails', $aggregateOfferEmptyNode->isValid);
assertIssue13PAggregateProductGroup(
    'empty AggregateOffer offer node has deterministic path',
    $aggregateOfferEmptyNode,
    'json_ld_invalid_property',
    'jsonLd.offers.0'
);

$productGroupInvalidCases = [
    'name' => 123,
    'description' => 123,
    'url' => 123,
    'productGroupID' => 123,
    'variesBy' => [],
    'hasVariant' => [],
];
foreach ($productGroupInvalidCases as $property => $value) {
    $result = SeoMetaValidator::validate(validMeta13PAggregateProductGroup([
        '@type' => 'ProductGroup',
        $property => $value,
    ]));
    assertFalseValue13PAggregateProductGroup('invalid ProductGroup property ' . $property . ' fails', $result->isValid);
    assertIssue13PAggregateProductGroup(
        'invalid ProductGroup property ' . $property . ' has deterministic path',
        $result,
        'json_ld_invalid_property',
        'jsonLd.' . $property
    );
}

$invalidProductGroupBrand = SeoMetaValidator::validate(validMeta13PAggregateProductGroup([
    '@type' => 'ProductGroup',
    'brand' => [
        '@type' => 'Thing',
    ],
]));
assertFalseValue13PAggregateProductGroup('invalid ProductGroup brand relationship fails', $invalidProductGroupBrand->isValid);
assertIssue13PAggregateProductGroup(
    'invalid ProductGroup brand relationship has deterministic path',
    $invalidProductGroupBrand,
    'json_ld_invalid_relationship',
    'jsonLd.brand'
);

$invalidProductGroupVariantRelationship = SeoMetaValidator::validate(validMeta13PAggregateProductGroup([
    '@type' => 'ProductGroup',
    'hasVariant' => [
        '@type' => 'Offer',
    ],
]));
assertFalseValue13PAggregateProductGroup('invalid ProductGroup hasVariant relationship fails', $invalidProductGroupVariantRelationship->isValid);
assertIssue13PAggregateProductGroup(
    'invalid ProductGroup hasVariant relationship has deterministic path',
    $invalidProductGroupVariantRelationship,
    'json_ld_invalid_relationship',
    'jsonLd.hasVariant'
);

$productGroupVariantListPath = SeoMetaValidator::validate(validMeta13PAggregateProductGroup([
    '@type' => 'ProductGroup',
    'hasVariant' => [
        [
            '@type' => 'Product',
            'sku' => 'TS-RED-L',
        ],
        [
            '@type' => 'Offer',
        ],
    ],
]));
assertFalseValue13PAggregateProductGroup('invalid ProductGroup variant list item fails', $productGroupVariantListPath->isValid);
assertIssue13PAggregateProductGroup(
    'invalid ProductGroup variant list item preserves order and path',
    $productGroupVariantListPath,
    'json_ld_invalid_relationship',
    'jsonLd.hasVariant.1'
);

$productGroupEmptyVariantNode = SeoMetaValidator::validate(validMeta13PAggregateProductGroup([
    '@type' => 'ProductGroup',
    'hasVariant' => [
        [],
    ],
]));
assertFalseValue13PAggregateProductGroup('empty ProductGroup variant node fails', $productGroupEmptyVariantNode->isValid);
assertIssue13PAggregateProductGroup(
    'empty ProductGroup variant node has deterministic path',
    $productGroupEmptyVariantNode,
    'json_ld_invalid_property',
    'jsonLd.hasVariant.0'
);

$nestedProductInvalid = SeoMetaValidator::validate(validMeta13PAggregateProductGroup([
    '@type' => 'ProductGroup',
    'hasVariant' => [
        '@type' => 'Product',
        'name' => 123,
    ],
]));
assertFalseValue13PAggregateProductGroup('nested Product in ProductGroup is recursively validated', $nestedProductInvalid->isValid);
assertIssue13PAggregateProductGroup(
    'nested Product issue keeps the ProductGroup field path',
    $nestedProductInvalid,
    'json_ld_invalid_property',
    'jsonLd.hasVariant.name'
);

$nestedAggregateInvalid = SeoMetaValidator::validate(validMeta13PAggregateProductGroup([
    '@type' => 'Product',
    'offers' => [
        '@type' => 'AggregateOffer',
        'offerCount' => 1.5,
    ],
]));
assertFalseValue13PAggregateProductGroup('nested AggregateOffer is recursively validated', $nestedAggregateInvalid->isValid);
assertIssue13PAggregateProductGroup(
    'nested AggregateOffer issue keeps the Product field path',
    $nestedAggregateInvalid,
    'json_ld_invalid_property',
    'jsonLd.offers.offerCount'
);

$exactProductAggregate = (new ProductJsonLdBuilder())
    ->setName('Widget Collection')
    ->setOffers(
        (new AggregateOfferJsonLdBuilder())
            ->setLowPrice('10.00')
            ->setHighPrice('50.00')
            ->setPriceCurrency('USD'),
    )
    ->toArray();
assertSameValue13PAggregateProductGroup('exact Product to AggregateOffer output remains unchanged', [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => 'Widget Collection',
    'offers' => [
        '@type' => 'AggregateOffer',
        'lowPrice' => '10.00',
        'highPrice' => '50.00',
        'priceCurrency' => 'USD',
    ],
], $exactProductAggregate);
assertNoIssues13PAggregateProductGroup(
    'exact Product to AggregateOffer scenario validates',
    SeoMetaValidator::validate(validMeta13PAggregateProductGroup($exactProductAggregate))
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
assertNoIssues13PAggregateProductGroup(
    'exact ProductGroup multi-variant scenario validates',
    SeoMetaValidator::validate(validMeta13PAggregateProductGroup($exactProductGroup))
);

$exactProductRelationship = (new ProductJsonLdBuilder())
    ->setSku('TS-RED-L')
    ->setColor('Red')
    ->setIsVariantOf('TSHIRT-BASE')
    ->toArray();
assertSameValue13PAggregateProductGroup('exact Product to ProductGroup output remains unchanged', [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'sku' => 'TS-RED-L',
    'color' => 'Red',
    'isVariantOf' => [
        '@type' => 'ProductGroup',
        'productGroupID' => 'TSHIRT-BASE',
    ],
], $exactProductRelationship);
assertNoIssues13PAggregateProductGroup(
    'exact Product to ProductGroup relationship scenario validates',
    SeoMetaValidator::validate(validMeta13PAggregateProductGroup($exactProductRelationship))
);

echo "Phase 13P AggregateOffer and ProductGroup semantic validation tests passed.\n";
