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

use Maatify\Seo\Web\JsonLd\Builder\OfferJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\ProductJsonLdBuilder;
use Maatify\Seo\Web\Validation\DTO\SeoValidationResultDTO;
use Maatify\Seo\Web\Validation\SeoMetaValidator;

function assertSameValue13PProductOffer(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertTrueValue13PProductOffer(string $label, bool $actual): void
{
    if (!$actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertFalseValue13PProductOffer(string $label, bool $actual): void
{
    if ($actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertIssue13PProductOffer(string $label, SeoValidationResultDTO $result, string $code, string $field): void
{
    foreach ($result->issues as $issue) {
        if ($issue->code === $code && $issue->field === $field) {
            return;
        }
    }

    fwrite(STDERR, "Assertion failed: {$label}\nMissing issue [{$code}] at [{$field}].\nActual:\n" . var_export($result->toArray(), true) . "\n");
    exit(1);
}

function assertNoIssues13PProductOffer(string $label, SeoValidationResultDTO $result): void
{
    assertSameValue13PProductOffer($label . ' has no issues', [], $result->issues);
}

/** @return array<string, mixed> */
function validMeta13PProductOffer(mixed $jsonLd): array
{
    return [
        'title' => 'A valid semantic validation title',
        'description' => 'This description is long enough for Product and Offer semantic validation tests.',
        'jsonLd' => $jsonLd,
    ];
}

$validProduct = [
    '@context' => 'https://schema.org',
    '@type' => 'https://schema.org/Product',
    'name' => 'Structured product',
    'description' => 'A product description.',
    'sku' => 'SKU-1',
    'gtin' => '000000000001',
    'mpn' => 'MPN-1',
    'brand' => [
        '@type' => 'Brand',
        'name' => 'Maatify',
    ],
    'image' => 'not-validated-as-a-url',
    'category' => 'Apparel',
    'url' => 'not-validated-as-a-url',
    'color' => 'Blue',
    'size' => [
        '@type' => 'DefinedTerm',
        'name' => 'Large',
    ],
    'material' => [
        '@type' => 'Product',
        'name' => 'Nested material product',
    ],
    'pattern' => [
        '@type' => 'DefinedTerm',
        'name' => 'Striped',
    ],
    'offers' => [
        [
            '@type' => 'Demand',
            'price' => ['not semantically validated'],
        ],
        [
            '@type' => 'Offer',
            'price' => 19.99,
            'seller' => [
                '@type' => 'Organization',
                'name' => 'Seller',
            ],
        ],
        [
            '@type' => 'AggregateOffer',
            'lowPrice' => ['not validated in WU2'],
        ],
        [
            '@type' => 'OfferForLease',
            'price' => ['not validated in WU2'],
        ],
        [
            '@type' => 'OfferForPurchase',
            'price' => ['not validated in WU2'],
        ],
    ],
    'aggregateRating' => [
        '@type' => 'AggregateRating',
        'ratingValue' => '5',
    ],
    'review' => [
        '@type' => 'Review',
        'reviewBody' => 'A review.',
    ],
    'isVariantOf' => [
        [
            '@type' => 'ProductGroup',
            'productGroupID' => 'GROUP-1',
        ],
        [
            '@type' => 'ProductModel',
            'name' => 'Model-1',
        ],
    ],
    'inProductGroupWithID' => 'GROUP-1',
    'customExtension' => 'unknown properties remain allowed',
];
assertNoIssues13PProductOffer('valid Product catalog and relationships', SeoMetaValidator::validate(validMeta13PProductOffer($validProduct)));

$validOffer = [
    '@context' => 'https://schema.org',
    '@type' => 'http://schema.org/Offer',
    'price' => '19.99',
    'priceCurrency' => 'USD',
    'availability' => 'anything',
    'url' => 'not-validated-as-a-url',
    'validFrom' => 'not-validated-as-a-date',
    'priceValidUntil' => 'not-validated-as-a-date',
    'itemCondition' => 'anything',
    'seller' => [
        '@type' => 'Person',
        'name' => 'Seller person',
    ],
];
assertNoIssues13PProductOffer('valid Offer catalog and seller relationship', SeoMetaValidator::validate(validMeta13PProductOffer($validOffer)));

$builderProduct = (new ProductJsonLdBuilder())->setBrand('Builder Brand')->toArray();
assertNoIssues13PProductOffer('Product brand accepts current builder Brand node', SeoMetaValidator::validate(validMeta13PProductOffer($builderProduct)));

$builderOffer = (new OfferJsonLdBuilder())
    ->setPrice(12)
    ->setSeller('Builder Seller')
    ->toArray();
assertNoIssues13PProductOffer('Offer builder output passes Product and Offer validation', SeoMetaValidator::validate(validMeta13PProductOffer($builderOffer)));

foreach ([
    ['@type' => 'Brand', 'name' => 'Raw Brand'],
    ['@type' => 'Organization', 'name' => 'Raw Organization'],
] as $brand) {
    assertNoIssues13PProductOffer('Product brand accepts canonical node range', SeoMetaValidator::validate(validMeta13PProductOffer([
        '@type' => 'Product',
        'brand' => $brand,
    ])));
}

$typeListProduct = SeoMetaValidator::validate(validMeta13PProductOffer([
    '@type' => ['Article', 'https://schema.org/Product'],
    'name' => 123,
    'offers' => [
        '@type' => ['Thing', 'http://schema.org/Offer'],
        'price' => 12,
    ],
    'isVariantOf' => [
        '@type' => ['Thing', 'http://schema.org/ProductModel'],
    ],
]));
assertFalseValue13PProductOffer('deep validation runs when type list contains Product', $typeListProduct->isValid);
assertIssue13PProductOffer('type list Product applies Product rules', $typeListProduct, 'json_ld_invalid_property', 'jsonLd.name');
assertNoIssues13PProductOffer('allowed type in a relationship list is sufficient', SeoMetaValidator::validate(validMeta13PProductOffer([
    '@type' => 'Product',
    'offers' => [
        '@type' => ['Thing', 'https://schema.org/Offer'],
        'price' => 12,
    ],
    'isVariantOf' => [
        '@type' => ['Thing', 'https://schema.org/ProductModel'],
    ],
])));

$malformedProductType = SeoMetaValidator::validate(validMeta13PProductOffer([
    '@type' => ['Product', ''],
    'name' => 123,
    'offers' => [
        '@type' => 'Thing',
    ],
]));
assertFalseValue13PProductOffer('malformed Product @type fails validation', $malformedProductType->isValid);
assertIssue13PProductOffer('malformed Product @type reports invalid type at the root path', $malformedProductType, 'json_ld_invalid_type', 'jsonLd.@type');
assertSameValue13PProductOffer('malformed Product @type does not emit semantic issues', 1, count($malformedProductType->issues));

$malformedOfferType = SeoMetaValidator::validate(validMeta13PProductOffer([
    '@type' => ['Offer', 123],
    'price' => [],
    'seller' => 'Seller name',
]));
assertFalseValue13PProductOffer('malformed Offer @type fails validation', $malformedOfferType->isValid);
assertIssue13PProductOffer('malformed Offer @type reports invalid type at the root path', $malformedOfferType, 'json_ld_invalid_type', 'jsonLd.@type');
assertSameValue13PProductOffer('malformed Offer @type does not emit semantic issues', 1, count($malformedOfferType->issues));

$productInvalidCases = [
    'name' => 123,
    'description' => ['@type' => 'Thing'],
    'sku' => [],
    'gtin' => true,
    'mpn' => 123,
    'brand' => ['@type' => 'Thing'],
    'image' => '',
    'category' => ['@type' => 'Product'],
    'url' => '',
    'color' => 123,
    'size' => ['@type' => 'Thing'],
    'material' => ['@type' => 'Organization'],
    'pattern' => ['@type' => 'Thing'],
    'offers' => ['@type' => 'Product'],
    'aggregateRating' => ['@type' => 'Thing'],
    'review' => ['@type' => 'Thing'],
    'isVariantOf' => ['@type' => 'Offer'],
    'inProductGroupWithID' => 123,
];
foreach ($productInvalidCases as $property => $value) {
    $result = SeoMetaValidator::validate(validMeta13PProductOffer([
        '@type' => 'Product',
        $property => $value,
    ]));
    assertFalseValue13PProductOffer('invalid Product property ' . $property . ' fails', $result->isValid);
    assertIssue13PProductOffer('invalid Product property ' . $property . ' has deterministic path', $result, str_contains((string) $property, 'brand') || in_array($property, ['description', 'category', 'size', 'material', 'pattern', 'offers', 'aggregateRating', 'review', 'isVariantOf'], true) ? 'json_ld_invalid_relationship' : 'json_ld_invalid_property', 'jsonLd.' . $property);
}

$repeatedPropertyInvalid = SeoMetaValidator::validate(validMeta13PProductOffer([
    '@type' => 'Product',
    'name' => ['valid text', 123],
]));
assertIssue13PProductOffer('repeated scalar property checks each item', $repeatedPropertyInvalid, 'json_ld_invalid_property', 'jsonLd.name.1');

$invalidOfferCases = [
    'price' => ['@type' => 'Product'],
    'priceCurrency' => 123,
    'availability' => '',
    'url' => '',
    'validFrom' => [],
    'priceValidUntil' => 123,
    'itemCondition' => [],
    'seller' => 'Seller name',
];
foreach ($invalidOfferCases as $property => $value) {
    $result = SeoMetaValidator::validate(validMeta13PProductOffer([
        '@type' => 'Offer',
        $property => $value,
    ]));
    assertFalseValue13PProductOffer('invalid Offer property ' . $property . ' fails', $result->isValid);
    assertIssue13PProductOffer('invalid Offer property ' . $property . ' has deterministic path', $result, $property === 'price' ? 'json_ld_invalid_property' : 'json_ld_invalid_property', 'jsonLd.' . $property);
}

$invalidSeller = SeoMetaValidator::validate(validMeta13PProductOffer([
    '@type' => 'Offer',
    'seller' => [
        '@type' => 'Thing',
    ],
]));
assertFalseValue13PProductOffer('invalid seller relationship fails', $invalidSeller->isValid);
assertIssue13PProductOffer('invalid seller relationship has deterministic path', $invalidSeller, 'json_ld_invalid_relationship', 'jsonLd.seller');

$nestedOfferInvalid = SeoMetaValidator::validate(validMeta13PProductOffer([
    '@type' => 'Product',
    'offers' => [
        '@type' => 'Offer',
        'price' => [],
    ],
]));
assertFalseValue13PProductOffer('nested Offer semantic violation fails', $nestedOfferInvalid->isValid);
assertIssue13PProductOffer('nested Offer semantic violation keeps path', $nestedOfferInvalid, 'json_ld_invalid_property', 'jsonLd.offers.price');

$nestedProductInvalid = SeoMetaValidator::validate(validMeta13PProductOffer([
    '@type' => 'Product',
    'material' => [
        '@type' => 'Product',
        'name' => 123,
    ],
]));
assertFalseValue13PProductOffer('nested Product semantic violation fails', $nestedProductInvalid->isValid);
assertIssue13PProductOffer('nested Product semantic violation keeps path', $nestedProductInvalid, 'json_ld_invalid_property', 'jsonLd.material.name');

$outOfScopeTargets = SeoMetaValidator::validate(validMeta13PProductOffer([
    '@type' => 'Product',
    'offers' => [
        [
            '@type' => 'Demand',
            'price' => [],
        ],
        [
            '@type' => 'AggregateOffer',
            'lowPrice' => ['not validated in WU2'],
        ],
        [
            '@type' => 'OfferForLease',
            'seller' => 'not validated in WU2',
        ],
        [
            '@type' => 'OfferForPurchase',
            'price' => ['not validated in WU2'],
        ],
    ],
    'isVariantOf' => [
        '@type' => 'ProductModel',
        'name' => 123,
    ],
    'brand' => [
        '@type' => 'Organization',
        'price' => ['not validated in WU2'],
    ],
]));
assertNoIssues13PProductOffer('valid out-of-scope relationship targets are not deeply validated', $outOfScopeTargets);

echo "Phase 13P Product and Offer semantic validation tests passed.\n";
