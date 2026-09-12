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

use Maatify\Seo\Shared\DTO\MetaTagsDTO;
use Maatify\Seo\Shared\DTO\Schema\GenericSchemaDTO;
use Maatify\Seo\Shared\Service\SchemaGeneratorService;
use Maatify\Seo\Web\JsonLd\Builder\ArticleJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\BookJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\CourseJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\JsonLdBuilderInterface;
use Maatify\Seo\Web\Validation\DTO\SeoValidationResultDTO;
use Maatify\Seo\Web\Validation\JsonLd\JsonLdSemanticValidator;
use Maatify\Seo\Web\Validation\SeoMetaValidator;
use Maatify\Seo\Web\Validation\SeoValidationBatchReportBuilder;
use Maatify\Seo\Web\Validation\SeoValidationBatchReportExporter;
use Maatify\Seo\Web\Validation\SeoValidationReportBuilder;
use Maatify\Seo\Web\Validation\SeoValidationReportExporter;
use Maatify\Seo\Web\Validation\SeoValidationScoreCalculator;

function stack7AssertSame(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function stack7AssertTrue(string $label, bool $actual): void
{
    stack7AssertSame($label, true, $actual);
}

function stack7AssertFalse(string $label, bool $actual): void
{
    stack7AssertSame($label, false, $actual);
}

/** @return array<string, mixed> */
function stack7ValidMeta(mixed $jsonLd): array
{
    return [
        'title' => 'A valid Stack 7 structured data title',
        'description' => 'This description keeps Stack 7 contract fixtures away from unrelated metadata warnings.',
        'jsonLd' => $jsonLd,
    ];
}

function stack7AssertNoIssues(string $label, SeoValidationResultDTO $result): void
{
    stack7AssertSame($label . ' issue set', [], $result->issues);
    stack7AssertTrue($label . ' is valid', $result->isValid);
    stack7AssertFalse($label . ' has no warnings', $result->hasWarnings);
}

function stack7AssertSingleIssue(
    string $label,
    SeoValidationResultDTO $result,
    string $code,
    string $severity,
    string $field,
): void {
    stack7AssertSame($label . ' issue count', 1, count($result->issues));
    $issue = $result->issues[0];
    stack7AssertSame($label . ' code', $code, $issue->code);
    stack7AssertSame($label . ' severity', $severity, $issue->severity);
    stack7AssertSame($label . ' field', $field, $issue->field);
}

/** @return list<string> */
function stack7TypeNames(ReflectionType $type): array
{
    if (!$type instanceof ReflectionUnionType) {
        return [];
    }

    $names = array_map(
        static fn (ReflectionNamedType $namedType): string => $namedType->getName(),
        $type->getTypes(),
    );
    sort($names);

    return $names;
}

// A. Exact public signatures are characterization contracts, not implementation hints.
$semanticReflection = new ReflectionMethod(JsonLdSemanticValidator::class, 'validate');
stack7AssertTrue('JsonLdSemanticValidator::validate is public', $semanticReflection->isPublic());
stack7AssertTrue('JsonLdSemanticValidator::validate is static', $semanticReflection->isStatic());
stack7AssertSame('JsonLdSemanticValidator parameter names', ['node', 'field', 'issues'], array_map(
    static fn (ReflectionParameter $parameter): string => $parameter->getName(),
    $semanticReflection->getParameters(),
));
stack7AssertSame('JsonLdSemanticValidator node type', 'array', $semanticReflection->getParameters()[0]->getType()?->getName());
stack7AssertSame('JsonLdSemanticValidator field type', 'string', $semanticReflection->getParameters()[1]->getType()?->getName());
stack7AssertSame('JsonLdSemanticValidator issues type', 'array', $semanticReflection->getParameters()[2]->getType()?->getName());
stack7AssertTrue('JsonLdSemanticValidator issues is by reference', $semanticReflection->getParameters()[2]->isPassedByReference());
stack7AssertSame('JsonLdSemanticValidator return type', 'void', $semanticReflection->getReturnType()?->getName());

$legacyReflection = new ReflectionMethod(SeoMetaValidator::class, 'validate');
stack7AssertTrue('SeoMetaValidator::validate is public', $legacyReflection->isPublic());
stack7AssertTrue('SeoMetaValidator::validate is static', $legacyReflection->isStatic());
stack7AssertSame('SeoMetaValidator parameter names', ['meta', 'options'], array_map(
    static fn (ReflectionParameter $parameter): string => $parameter->getName(),
    $legacyReflection->getParameters(),
));
stack7AssertSame('SeoMetaValidator legacy input union', ['array', 'object'], stack7TypeNames($legacyReflection->getParameters()[0]->getType()));
stack7AssertSame('SeoMetaValidator options type', 'array', $legacyReflection->getParameters()[1]->getType()?->getName());
stack7AssertTrue('SeoMetaValidator options default is empty array', $legacyReflection->getParameters()[1]->isDefaultValueAvailable());
stack7AssertSame('SeoMetaValidator options default', [], $legacyReflection->getParameters()[1]->getDefaultValue());
stack7AssertSame('SeoMetaValidator return type', SeoValidationResultDTO::class, $legacyReflection->getReturnType()?->getName());

$objectResult = SeoMetaValidator::validate(new MetaTagsDTO(
    'An object branch title for Stack 7',
    'An object branch description that remains long enough for the current validation thresholds.',
    'https://example.com/object-branch',
));
stack7AssertNoIssues('MetaTagsDTO object branch remains callable', $objectResult);

// B. Scoped structural/property-range semantic validation remains limited to the four audited types.
$scopedCases = [
    [
        'label' => 'Product invalid property',
        'node' => ['@type' => 'Product', 'name' => 123],
        'code' => 'json_ld_invalid_property',
        'field' => 'jsonLd.name',
    ],
    [
        'label' => 'Product invalid relationship',
        'node' => ['@type' => 'Product', 'offers' => ['@type' => 'Thing']],
        'code' => 'json_ld_invalid_relationship',
        'field' => 'jsonLd.offers',
    ],
    [
        'label' => 'Offer invalid property',
        'node' => ['@type' => 'Offer', 'price' => []],
        'code' => 'json_ld_invalid_property',
        'field' => 'jsonLd.price',
    ],
    [
        'label' => 'Offer invalid relationship',
        'node' => ['@type' => 'Offer', 'seller' => ['@type' => 'Thing']],
        'code' => 'json_ld_invalid_relationship',
        'field' => 'jsonLd.seller',
    ],
    [
        'label' => 'AggregateOffer invalid property',
        'node' => ['@type' => 'AggregateOffer', 'offerCount' => 1.5],
        'code' => 'json_ld_invalid_property',
        'field' => 'jsonLd.offerCount',
    ],
    [
        'label' => 'AggregateOffer invalid relationship',
        'node' => ['@type' => 'AggregateOffer', 'offers' => ['@type' => 'Product']],
        'code' => 'json_ld_invalid_relationship',
        'field' => 'jsonLd.offers',
    ],
    [
        'label' => 'ProductGroup invalid property',
        'node' => ['@type' => 'ProductGroup', 'url' => 123],
        'code' => 'json_ld_invalid_property',
        'field' => 'jsonLd.url',
    ],
    [
        'label' => 'ProductGroup invalid relationship',
        'node' => ['@type' => 'ProductGroup', 'hasVariant' => ['@type' => 'Offer']],
        'code' => 'json_ld_invalid_relationship',
        'field' => 'jsonLd.hasVariant',
    ],
];

foreach ($scopedCases as $case) {
    /** @var array<string, mixed> $node */
    $node = $case['node'];
    $result = SeoMetaValidator::validate(stack7ValidMeta($node));
    stack7AssertFalse($case['label'] . ' makes the legacy result invalid', $result->isValid);
    stack7AssertSingleIssue($case['label'], $result, $case['code'], 'error', $case['field']);
}

// C. Current representation boundaries remain permissive for non-empty URL/date/enumeration strings.
$lexicalCases = [
    [
        'label' => 'Product URL and image representations',
        'node' => [
            '@type' => 'Product',
            'url' => 'not-a-url',
            'image' => 'not-a-url',
        ],
    ],
    [
        'label' => 'Offer URL/date/enumeration representations',
        'node' => [
            '@type' => 'Offer',
            'url' => '/relative/path',
            'validFrom' => 'not-a-date',
            'priceValidUntil' => 'still-not-a-date',
            'availability' => 'anything',
            'itemCondition' => 'anything',
        ],
    ],
];

foreach ($lexicalCases as $case) {
    $meta = stack7ValidMeta($case['node']);
    $result = SeoMetaValidator::validate($meta);
    stack7AssertNoIssues($case['label'] . ' remain accepted', $result);

    $score = SeoValidationScoreCalculator::score($result);
    stack7AssertSame($case['label'] . ' score remains unchanged', 100, $score->score);
    stack7AssertSame($case['label'] . ' score deductions remain empty', [], $score->deductions);

    $report = SeoValidationReportBuilder::build($meta);
    stack7AssertSame($case['label'] . ' report issue path remains empty', [], $report->issues);
    stack7AssertSame($case['label'] . ' report score remains unchanged', 100, $report->score);
    stack7AssertSame($case['label'] . ' report summary remains pass', ['status' => 'pass', 'message' => 'SEO validation passed.'], $report->summary);
    stack7AssertSame($case['label'] . ' report exporter preserves shape', $report->toArray(), SeoValidationReportExporter::toArray($report));
}

$wrongRepresentation = SeoMetaValidator::validate(stack7ValidMeta([
    '@type' => 'Offer',
    'validFrom' => [],
]));
stack7AssertFalse('wrong representation still invalidates the legacy result', $wrongRepresentation->isValid);
stack7AssertSingleIssue('wrong representation keeps current issue contract', $wrongRepresentation, 'json_ld_invalid_property', 'error', 'jsonLd.validFrom');

// D. Unknown extensions/types and valid out-of-scope targets retain current behavior.
$unknownExtension = SeoMetaValidator::validate(stack7ValidMeta([
    '@type' => 'Product',
    'name' => 'Product with an extension property',
    'https://example.test/extension' => [
        '@type' => 'CustomExtension',
        'arbitrary' => [],
    ],
]));
stack7AssertNoIssues('unknown Product extension property is not rejected', $unknownExtension);

$unsupportedType = SeoMetaValidator::validate(stack7ValidMeta([
    '@type' => 'https://schema.org/Article',
    'headline' => 123,
]));
stack7AssertNoIssues('well-formed out-of-scope type is not rejected by scope alone', $unsupportedType);

$outOfScopeTarget = SeoMetaValidator::validate(stack7ValidMeta([
    '@type' => 'Product',
    'offers' => [
        '@type' => 'Demand',
        'price' => [],
    ],
    'isVariantOf' => [
        '@type' => 'ProductModel',
        'name' => 123,
    ],
]));
stack7AssertNoIssues('valid out-of-scope relationship targets are not deeply validated', $outOfScopeTarget);

// E/F. Course and Book remain generic builders and do not acquire provider eligibility rules.
$course = (new CourseJsonLdBuilder())
    ->setName('Intro to PHP')
    ->setDescription('Learn PHP basics')
    ->setProvider('University')
    ->setCourseCode('CS101')
    ->setEducationalCredentialAwarded('Certificate')
    ->setHasCourseInstance(['courseMode' => 'online'])
    ->addCourseInstance(['courseMode' => 'onsite'])
    ->setOffers([['@type' => 'Offer', 'price' => '100.00']])
    ->setAggregateRating(['ratingValue' => '4.5']);
$courseArray = $course->toArray();
stack7AssertTrue('Course builder remains callable through the generic builder interface', $course instanceof JsonLdBuilderInterface);
stack7AssertSame('Course builder current context', 'https://schema.org', $courseArray['@context'] ?? null);
stack7AssertSame('Course builder current type', 'Course', $courseArray['@type'] ?? null);
stack7AssertSame('Course builder toJson remains compatible with toArray', $courseArray, json_decode($course->toJson(), true, 512, JSON_THROW_ON_ERROR));
stack7AssertNoIssues('Course output passes generic validation without eligibility findings', SeoMetaValidator::validate(stack7ValidMeta($courseArray)));

$courseWithoutProviderFields = SeoMetaValidator::validate(stack7ValidMeta((new CourseJsonLdBuilder())->toArray()));
stack7AssertNoIssues('missing Course provider fields do not create a new eligibility failure', $courseWithoutProviderFields);

$book = (new BookJsonLdBuilder())
    ->setName('The Hobbit')
    ->setUrl('https://example.com/books/the-hobbit')
    ->setImage(['https://example.com/book1.jpg', 'https://example.com/book2.jpg'])
    ->setDescription('A great book about a hobbit.')
    ->setAuthor('J.R.R. Tolkien')
    ->setPublisher(['@type' => 'Organization', 'name' => 'George Allen & Unwin'])
    ->setIsbn('978-0547928227')
    ->setBookFormat('Hardcover')
    ->setDatePublished('1937-09-21')
    ->setNumberOfPages(310)
    ->setInLanguage('en')
    ->setAggregateRating(['ratingValue' => '4.8', 'reviewCount' => '150'])
    ->setOffers([
        ['@type' => 'Offer', 'price' => '15.99', 'priceCurrency' => 'USD'],
        ['@type' => 'Offer', 'price' => '12.99', 'priceCurrency' => 'GBP'],
    ]);
$bookArray = $book->toArray();
stack7AssertTrue('Book builder remains callable through the generic builder interface', $book instanceof JsonLdBuilderInterface);
stack7AssertSame('Book builder current context', 'https://schema.org', $bookArray['@context'] ?? null);
stack7AssertSame('Book builder current type', 'Book', $bookArray['@type'] ?? null);
stack7AssertSame('Book builder toJson remains compatible with toArray', $bookArray, json_decode($book->toJson(), true, 512, JSON_THROW_ON_ERROR));
stack7AssertNoIssues('Book output passes generic validation without eligibility findings', SeoMetaValidator::validate(stack7ValidMeta($bookArray)));

$bookWithoutProviderFields = SeoMetaValidator::validate(stack7ValidMeta((new BookJsonLdBuilder())
    ->setUrl('not-a-url')
    ->setDatePublished('not-a-date')
    ->toArray()));
stack7AssertNoIssues('missing Book provider fields and lexical-looking strings do not create eligibility findings', $bookWithoutProviderFields);

$genericBuilder = (new ArticleJsonLdBuilder())->setHeadline('A generic Article schema');
stack7AssertTrue('representative generic builder remains callable', $genericBuilder instanceof JsonLdBuilderInterface);
$genericBuilderArray = $genericBuilder->toArray();
stack7AssertSame('generic builder toJson is compatible with toArray', $genericBuilderArray, json_decode($genericBuilder->toJson(), true, 512, JSON_THROW_ON_ERROR));

$genericSchema = new GenericSchemaDTO('Article', ['headline' => 'A generic generated schema']);
$generatedSchema = (new SchemaGeneratorService())->generate($genericSchema)->jsonSerialize();
stack7AssertSame('generic SchemaGeneratorService output remains provider-neutral', [
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => 'A generic generated schema',
], $generatedSchema);

// G/H/I. Legacy DTO/result/report/score/batch contracts remain unchanged.
$legacyInvalidMeta = stack7ValidMeta(['@type' => 'Product', 'name' => 123]);
$legacyInvalid = SeoMetaValidator::validate($legacyInvalidMeta);
stack7AssertSame('legacy result serialized shape remains unchanged', [
    'is_valid' => false,
    'has_warnings' => false,
    'errors' => [[
        'code' => 'json_ld_invalid_property',
        'severity' => 'error',
        'message' => 'JSON-LD property value does not match its declared representation.',
        'field' => 'jsonLd.name',
    ]],
    'warnings' => [],
    'info' => [],
    'issues' => [[
        'code' => 'json_ld_invalid_property',
        'severity' => 'error',
        'message' => 'JSON-LD property value does not match its declared representation.',
        'field' => 'jsonLd.name',
    ]],
], $legacyInvalid->toArray());

$legacyScore = SeoValidationScoreCalculator::score($legacyInvalid);
stack7AssertSame('legacy score remains 75', 75, $legacyScore->score);
stack7AssertSame('legacy grade remains C', 'C', $legacyScore->grade);
stack7AssertSame('legacy score counts remain unchanged', [1, 0, 0], [$legacyScore->errorCount, $legacyScore->warningCount, $legacyScore->infoCount]);
stack7AssertSame('legacy score deduction remains unchanged', [
    ['code' => 'json_ld_invalid_property', 'severity' => 'error', 'field' => 'jsonLd.name', 'points' => 25],
], $legacyScore->deductions);

$legacyReport = SeoValidationReportBuilder::build($legacyInvalidMeta);
stack7AssertFalse('legacy report remains invalid', $legacyReport->isValid);
stack7AssertSame('legacy report path remains unchanged', 'jsonLd.name', $legacyReport->errors[0]['field']);
stack7AssertSame('legacy report score remains unchanged', 75, $legacyReport->score);
stack7AssertSame('legacy report summary remains unchanged', ['status' => 'fail', 'message' => 'SEO validation failed.'], $legacyReport->summary);

$legacyBatch = SeoValidationBatchReportBuilder::build([
    ['meta' => stack7ValidMeta(['@type' => 'Product', 'name' => 'Valid product'])],
    ['meta' => $legacyInvalidMeta],
]);
stack7AssertFalse('legacy batch remains invalid', $legacyBatch->isValid);
stack7AssertSame('legacy batch counts remain unchanged', [2, 1, 1, 1, 0], [
    $legacyBatch->totalCount,
    $legacyBatch->validCount,
    $legacyBatch->invalidCount,
    $legacyBatch->errorCount,
    $legacyBatch->warningCount,
]);
stack7AssertSame('legacy batch average score remains unchanged', 87.5, $legacyBatch->averageScore);
stack7AssertSame('legacy batch invalid report path remains unchanged', 'jsonLd.name', $legacyBatch->reports[1]->errors[0]['field']);
stack7AssertSame('legacy batch exporter preserves serialized shape', $legacyBatch->toArray(), SeoValidationBatchReportExporter::toArray($legacyBatch));

echo "Stack 7 structured-data contract boundary tests passed.\n";
