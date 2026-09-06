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

use Maatify\Seo\Web\Validation\DTO\SeoValidationResultDTO;
use Maatify\Seo\Web\Validation\SeoMetaValidator;
use Maatify\Seo\Web\Validation\SeoValidationReportBuilder;
use Maatify\Seo\Web\Validation\SeoValidationReportExporter;

function assertSameValue21StructuredData(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertTrueValue21StructuredData(string $label, bool $actual): void
{
    if (!$actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertFalseValue21StructuredData(string $label, bool $actual): void
{
    if ($actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertIssue21StructuredData(string $label, SeoValidationResultDTO $result, string $code, string $field): void
{
    foreach ($result->issues as $issue) {
        if ($issue->code === $code && $issue->field === $field) {
            return;
        }
    }

    fwrite(STDERR, "Assertion failed: {$label}\nExpected issue: {$code} at {$field}\nActual issues:\n" . var_export($result->toArray()['issues'], true) . "\n");
    exit(1);
}

function assertNoIssues21StructuredData(string $label, SeoValidationResultDTO $result): void
{
    assertSameValue21StructuredData($label . ' has no issues', [], $result->issues);
}

/** @return array<string, mixed> */
function validMeta21StructuredData(mixed $jsonLd): array
{
    return [
        'title' => 'A valid Phase 21 structured-data title',
        'description' => 'This description is long enough for the Phase 21 structured-data CI gate.',
        'jsonLd' => $jsonLd,
    ];
}

/** @return array<string, mixed> */
function baseMeta21StructuredData(): array
{
    return [
        'title' => 'A valid Phase 21 structured-data title',
        'description' => 'This description is long enough for the Phase 21 structured-data CI gate.',
    ];
}

$validNodes = [
    'Product' => [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => 'Phase 21 product',
    ],
    'Offer' => [
        '@context' => 'https://schema.org',
        '@type' => 'Offer',
        'price' => 19.99,
        'seller' => [
            '@type' => 'Organization',
            'name' => 'Phase 21 seller',
        ],
    ],
    'AggregateOffer' => [
        '@context' => 'https://schema.org',
        '@type' => 'AggregateOffer',
        'lowPrice' => 10,
        'highPrice' => 20,
        'offerCount' => 2,
    ],
    'ProductGroup' => [
        '@context' => 'https://schema.org',
        '@type' => 'ProductGroup',
        'name' => 'Phase 21 product group',
        'variesBy' => 'https://schema.org/color',
    ],
];

foreach ($validNodes as $type => $node) {
    $result = SeoMetaValidator::validate(validMeta21StructuredData($node));
    assertTrueValue21StructuredData($type . ' valid node remains valid', $result->isValid);
    assertNoIssues21StructuredData($type . ' valid node', $result);
}

$numericNodeList = [
    [
        '@type' => 'Product',
        'name' => 'List product',
    ],
    [
        '@type' => 'Offer',
        'price' => 29.99,
    ],
];
assertNoIssues21StructuredData(
    'numeric JSON-LD node list',
    SeoMetaValidator::validate(validMeta21StructuredData($numericNodeList)),
);

$graph = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Product',
            'name' => 'Graph product',
            'offers' => [
                '@type' => 'Offer',
                'price' => 39.99,
                'seller' => [
                    '@type' => 'Organization',
                    'name' => 'Graph seller',
                ],
            ],
        ],
        [
            '@type' => 'ProductGroup',
            'name' => 'Graph product group',
            'variesBy' => 'https://schema.org/size',
            'hasVariant' => [
                '@type' => 'Product',
                'name' => 'Nested graph product',
            ],
        ],
    ],
];
assertNoIssues21StructuredData(
    'graph and recursively nested nodes',
    SeoMetaValidator::validate(validMeta21StructuredData($graph)),
);

$validOutOfScopeTargets = [
    '@type' => 'Product',
    'name' => 'Out-of-scope target product',
    'offers' => [
        ['@type' => 'Demand'],
        ['@type' => 'OfferForLease'],
        ['@type' => 'OfferForPurchase'],
    ],
    'isVariantOf' => [
        '@type' => 'ProductModel',
        'name' => 'Out-of-scope product model',
    ],
];
assertNoIssues21StructuredData(
    'valid out-of-scope relationship targets',
    SeoMetaValidator::validate(validMeta21StructuredData($validOutOfScopeTargets)),
);

$aggregateOfferWithDemand = [
    '@type' => 'AggregateOffer',
    'lowPrice' => 10,
    'highPrice' => 20,
    'offerCount' => 2,
    'offers' => [
        '@type' => 'Demand',
    ],
];
assertNoIssues21StructuredData(
    'AggregateOffer Demand relationship target',
    SeoMetaValidator::validate(validMeta21StructuredData($aggregateOfferWithDemand)),
);

$aliases = ['jsonLd', 'json_ld', 'schema', 'schemas'];
foreach ($aliases as $alias) {
    $meta = baseMeta21StructuredData();
    $meta[$alias] = [
        '@type' => 'Product',
        'name' => 'Alias product',
    ];

    $aliasResult = SeoMetaValidator::validate($meta);
    assertTrueValue21StructuredData($alias . ' alias remains valid', $aliasResult->isValid);
    assertNoIssues21StructuredData($alias . ' alias', $aliasResult);
}

$nestedList = SeoMetaValidator::validate(validMeta21StructuredData([
    [
        [
            '@type' => 'Product',
        ],
    ],
]));
assertFalseValue21StructuredData('nested numeric node list is invalid', $nestedList->isValid);
assertIssue21StructuredData('nested numeric node list issue', $nestedList, 'json_ld_invalid_node', 'jsonLd.0');

$missingType = SeoMetaValidator::validate(validMeta21StructuredData([
    '@id' => 'https://example.com/product/1',
]));
assertFalseValue21StructuredData('missing node type is invalid', $missingType->isValid);
assertIssue21StructuredData('missing node type issue', $missingType, 'json_ld_missing_type', 'jsonLd.@type');

$invalidType = SeoMetaValidator::validate(validMeta21StructuredData([
    '@type' => ['Product', ''],
]));
assertFalseValue21StructuredData('malformed node type is invalid', $invalidType->isValid);
assertIssue21StructuredData('malformed node type issue', $invalidType, 'json_ld_invalid_type', 'jsonLd.@type');

$invalidProperty = SeoMetaValidator::validate(validMeta21StructuredData([
    '@type' => 'Product',
    'name' => 123,
]));
assertFalseValue21StructuredData('invalid property is invalid', $invalidProperty->isValid);
assertIssue21StructuredData('invalid property issue', $invalidProperty, 'json_ld_invalid_property', 'jsonLd.name');

$invalidRelationship = SeoMetaValidator::validate(validMeta21StructuredData([
    '@type' => 'ProductGroup',
    'hasVariant' => [
        '@type' => 'Offer',
    ],
]));
assertFalseValue21StructuredData('invalid relationship is invalid', $invalidRelationship->isValid);
assertIssue21StructuredData('invalid relationship issue', $invalidRelationship, 'json_ld_invalid_relationship', 'jsonLd.hasVariant');

$invalidGraph = SeoMetaValidator::validate(validMeta21StructuredData([
    '@context' => 'https://schema.org',
    '@graph' => [],
]));
assertFalseValue21StructuredData('empty graph is invalid', $invalidGraph->isValid);
assertIssue21StructuredData('empty graph issue', $invalidGraph, 'json_ld_invalid_node', 'jsonLd.@graph');

$report = SeoValidationReportBuilder::build(validMeta21StructuredData([
    '@type' => 'Product',
    'name' => 123,
]));
$reportJson = json_decode(SeoValidationReportExporter::toJson($report), true);
assertTrueValue21StructuredData('existing report JSON is an array', is_array($reportJson));
assertSameValue21StructuredData('report JSON preserves issue code', 'json_ld_invalid_property', $reportJson['errors'][0]['code'] ?? null);
assertSameValue21StructuredData('report JSON preserves field path', 'jsonLd.name', $reportJson['errors'][0]['field'] ?? null);

echo "Phase 21 WU2 structured-data validation gate passed.\n";
