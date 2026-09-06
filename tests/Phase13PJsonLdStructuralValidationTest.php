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

use Maatify\Seo\Shared\DTO\Schema\GenericSchemaDTO;
use Maatify\Seo\Shared\Service\SchemaGeneratorService;
use Maatify\Seo\Web\Validation\DTO\SeoValidationResultDTO;
use Maatify\Seo\Web\Validation\SeoMetaValidator;

function assertSameValue13P(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertTrueValue13P(string $label, bool $actual): void
{
    if (!$actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertFalseValue13P(string $label, bool $actual): void
{
    if ($actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertIssue13P(string $label, SeoValidationResultDTO $result, string $code, ?string $field = null): void
{
    foreach ($result->issues as $issue) {
        if ($issue->code === $code && ($field === null || $issue->field === $field)) {
            return;
        }
    }

    fwrite(STDERR, "Assertion failed: {$label}\nMissing issue [{$code}] at [{$field}].\nActual:\n" . var_export($result->toArray(), true) . "\n");
    exit(1);
}

function assertNoStructuralIssues13P(string $label, SeoValidationResultDTO $result): void
{
    assertSameValue13P($label . ' has no issues', [], $result->issues);
}

/** @return array<string, mixed> */
function validMeta13P(mixed $jsonLd): array
{
    return [
        'title' => 'A valid structured data title',
        'description' => 'This description is long enough for the structural validation regression tests.',
        'jsonLd' => $jsonLd,
    ];
}

$root = [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => 'A structurally valid root node',
];
assertNoStructuralIssues13P('associative root node', SeoMetaValidator::validate(validMeta13P($root)));

$rootWithoutType = SeoMetaValidator::validate(validMeta13P([
    '@context' => 'https://schema.org',
    'headline' => 'Missing type',
]));
assertFalseValue13P('root without type is invalid', $rootWithoutType->isValid);
assertIssue13P('root without type reports missing type', $rootWithoutType, 'json_ld_missing_type', 'jsonLd.@type');

$malformedTypes = [
    '',
    42,
    [],
    [''],
    ['Product', ''],
    ['Product', 42],
    ['Product' => true],
];
foreach ($malformedTypes as $index => $type) {
    $result = SeoMetaValidator::validate(validMeta13P(['@type' => $type]));
    assertFalseValue13P('malformed type ' . $index . ' is invalid', $result->isValid);
    assertIssue13P('malformed type ' . $index . ' reports invalid type', $result, 'json_ld_invalid_type', 'jsonLd.@type');
}

$validTypeList = SeoMetaValidator::validate(validMeta13P([
    '@type' => ['Product', 'https://schema.org/Offer', 'http://schema.org/Thing', 'CustomType'],
]));
assertNoStructuralIssues13P('valid type list and Schema.org aliases', $validTypeList);

$outOfScope = SeoMetaValidator::validate(validMeta13P([
    '@type' => 'Article',
    'headline' => 'Out of scope types remain structurally valid',
]));
assertNoStructuralIssues13P('out-of-scope type has no scope-only issue', $outOfScope);

$nodeList = SeoMetaValidator::validate(validMeta13P([
    [
        '@type' => 'Product',
        'name' => 'First node',
    ],
    [
        '@type' => 'http://schema.org/Article',
        'headline' => 'Second node',
    ],
]));
assertNoStructuralIssues13P('numeric list of nodes', $nodeList);

$nestedTopLevelList = SeoMetaValidator::validate(validMeta13P([
    [
        [
            '@type' => 'Product',
        ],
    ],
]));
assertFalseValue13P('non-empty nested top-level list is invalid', $nestedTopLevelList->isValid);
assertIssue13P('nested top-level list keeps deterministic field path', $nestedTopLevelList, 'json_ld_invalid_node', 'jsonLd.0');

$idReferenceWithoutType = SeoMetaValidator::validate(validMeta13P([
    '@id' => 'https://example.com/products/1',
]));
assertFalseValue13P('@id node reference without type is invalid', $idReferenceWithoutType->isValid);
assertIssue13P('@id node reference requires type', $idReferenceWithoutType, 'json_ld_missing_type', 'jsonLd.@type');

$nestedList = SeoMetaValidator::validate(validMeta13P([
    '@type' => 'Product',
    'offers' => [
        [
            '@type' => 'Offer',
            'price' => 'not semantically validated in WU1',
        ],
        [
            '@type' => ['https://schema.org/Offer', 'CustomOfferType'],
        ],
    ],
]));
assertNoStructuralIssues13P('nested nodes and numeric lists are traversed structurally', $nestedList);

$nestedMissingType = SeoMetaValidator::validate(validMeta13P([
    '@type' => 'Product',
    'offers' => [
        '@type' => 'Offer',
        'seller' => [
            'name' => 'Missing nested type',
        ],
    ],
]));
assertFalseValue13P('nested node without type is invalid', $nestedMissingType->isValid);
assertIssue13P('nested node keeps deterministic field path', $nestedMissingType, 'json_ld_missing_type', 'jsonLd.offers.seller.@type');

$generator = new SchemaGeneratorService();
$generatedGraph = $generator->generateGraph([
    new GenericSchemaDTO('Product', ['name' => 'Generated product']),
    new GenericSchemaDTO('Article', ['headline' => 'Generated article']),
])->jsonSerialize();
assertSameValue13P('generated graph wrapper context', 'https://schema.org', $generatedGraph['@context']);
assertTrueValue13P('generated graph has no wrapper type requirement', !array_key_exists('@type', $generatedGraph));
assertTrueValue13P('generated graph is a non-empty list', is_array($generatedGraph['@graph']) && $generatedGraph['@graph'] !== [] && array_is_list($generatedGraph['@graph']));
assertSameValue13P('generated graph preserves node order', ['Product', 'Article'], array_column($generatedGraph['@graph'], '@type'));
assertFalseValue13P('generated graph nodes omit nested contexts', array_key_exists('@context', $generatedGraph['@graph'][0]));
assertNoStructuralIssues13P('SchemaGeneratorService graph output is valid', SeoMetaValidator::validate(validMeta13P($generatedGraph)));

$graphInput = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'Product',
            'offers' => [
                '@type' => 'Offer',
                'seller' => [
                    '@type' => 'Organization',
                    'name' => 'A seller',
                ],
            ],
        ],
    ],
];
$graphBeforeValidation = $graphInput;
$graphResult = SeoMetaValidator::validate(validMeta13P($graphInput));
assertNoStructuralIssues13P('graph wrapper and nested nodes are recursively valid', $graphResult);
assertSameValue13P('graph validation is read-only', $graphBeforeValidation, $graphInput);

$graphNestedMissingType = SeoMetaValidator::validate(validMeta13P([
    '@context' => ['@vocab' => 'https://schema.org/'],
    '@graph' => [
        [
            '@type' => 'Product',
            'offers' => [
                '@type' => 'Offer',
                'seller' => [
                    'name' => 'Missing graph nested type',
                ],
            ],
        ],
    ],
]));
assertFalseValue13P('graph nested node without type is invalid', $graphNestedMissingType->isValid);
assertIssue13P('graph nested node keeps deterministic field path', $graphNestedMissingType, 'json_ld_missing_type', 'jsonLd.@graph.0.offers.seller.@type');

$emptyGraph = SeoMetaValidator::validate(validMeta13P([
    '@context' => 'https://schema.org',
    '@graph' => [],
]));
assertFalseValue13P('empty graph is invalid', $emptyGraph->isValid);
assertIssue13P('empty graph uses existing structural taxonomy', $emptyGraph, 'json_ld_invalid_node', 'jsonLd.@graph');

$malformedGraph = SeoMetaValidator::validate(validMeta13P([
    '@context' => 'https://schema.org',
    '@graph' => ['not a node'],
]));
assertFalseValue13P('malformed graph entry is invalid', $malformedGraph->isValid);
assertIssue13P('malformed graph entry uses existing structural taxonomy', $malformedGraph, 'json_ld_invalid_node', 'jsonLd.@graph.0');

$nonListGraph = SeoMetaValidator::validate(validMeta13P([
    '@context' => 'https://schema.org',
    '@graph' => [
        'first' => ['@type' => 'Product'],
    ],
]));
assertFalseValue13P('non-list graph is invalid', $nonListGraph->isValid);
assertIssue13P('non-list graph uses existing structural taxonomy', $nonListGraph, 'json_ld_invalid_node', 'jsonLd.@graph');

$legacyInvalidList = SeoMetaValidator::validate(validMeta13P(['invalid entry']));
assertTrueValue13P('legacy invalid JSON-LD list remains warning-only', $legacyInvalidList->isValid);
assertIssue13P('legacy invalid JSON-LD warning remains', $legacyInvalidList, 'invalid_json_ld_schema', 'jsonLd.0');

echo "Phase 13P JSON-LD structural validation tests passed.\n";
