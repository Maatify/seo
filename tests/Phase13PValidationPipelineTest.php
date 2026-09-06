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
use Maatify\Seo\Web\Validation\SeoValidationBatchReportBuilder;
use Maatify\Seo\Web\Validation\SeoValidationBatchReportExporter;
use Maatify\Seo\Web\Validation\SeoValidationReportBuilder;
use Maatify\Seo\Web\Validation\SeoValidationReportExporter;

function assertSameValue13PPipeline(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertTrueValue13PPipeline(string $label, bool $actual): void
{
    if (!$actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertFalseValue13PPipeline(string $label, bool $actual): void
{
    if ($actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertIssue13PPipeline(string $label, SeoValidationResultDTO $result, string $code, string $field): void
{
    foreach ($result->issues as $issue) {
        if ($issue->code === $code && $issue->field === $field) {
            return;
        }
    }

    fwrite(STDERR, "Assertion failed: {$label}\nMissing issue [{$code}] at [{$field}].\nActual:\n" . var_export($result->toArray(), true) . "\n");
    exit(1);
}

/** @return array<string, mixed> */
function validMeta13PPipeline(mixed $jsonLd = null): array
{
    $meta = [
        'title' => 'A valid pipeline product title',
        'description' => 'This description is long enough for validation pipeline compatibility tests.',
    ];

    if ($jsonLd !== null) {
        $meta['jsonLd'] = $jsonLd;
    }

    return $meta;
}

/** @param array<string, mixed> $meta */
function assertNoGoogleFindings13PPipeline(string $label, array $meta): void
{
    $result = SeoMetaValidator::validate($meta);
    foreach ($result->issues as $issue) {
        if (str_contains(strtolower($issue->code), 'google') || str_contains(strtolower($issue->code), 'merchant')) {
            fwrite(STDERR, "Assertion failed: {$label}\nUnexpected eligibility issue [{$issue->code}].\n");
            exit(1);
        }
    }
}

$semanticCases = [
    'Product' => [
        'node' => [
            '@type' => 'Product',
            'name' => 123,
        ],
        'field' => 'jsonLd.name',
    ],
    'Offer' => [
        'node' => [
            '@type' => 'Offer',
            'price' => [],
        ],
        'field' => 'jsonLd.price',
    ],
    'AggregateOffer' => [
        'node' => [
            '@type' => 'AggregateOffer',
            'offerCount' => 1.5,
        ],
        'field' => 'jsonLd.offerCount',
    ],
    'ProductGroup' => [
        'node' => [
            '@type' => 'ProductGroup',
            'hasVariant' => [
                '@type' => 'Offer',
            ],
        ],
        'field' => 'jsonLd.hasVariant',
    ],
];

foreach ($semanticCases as $type => $case) {
    /** @var array<string, mixed> $node */
    $node = $case['node'];
    /** @var string $field */
    $field = $case['field'];
    $result = SeoMetaValidator::validate(validMeta13PPipeline($node));

    assertFalseValue13PPipeline($type . ' semantic issue makes result invalid', $result->isValid);
    assertIssue13PPipeline($type . ' semantic issue reaches result DTO', $result, 'json_ld_' . ($type === 'ProductGroup' ? 'invalid_relationship' : 'invalid_property'), $field);
    assertSameValue13PPipeline($type . ' issue is an error', 1, count($result->errors));
    assertSameValue13PPipeline($type . ' does not add warnings', 0, count($result->warnings));
}

$invalidProduct = SeoMetaValidator::validate(validMeta13PPipeline([
    '@type' => 'Product',
    'name' => 123,
]));
assertSameValue13PPipeline('Product issue code is preserved in result DTO', 'json_ld_invalid_property', $invalidProduct->issues[0]->code);
assertSameValue13PPipeline('Product issue field is preserved in result DTO', 'jsonLd.name', $invalidProduct->issues[0]->field);
assertNoGoogleFindings13PPipeline('semantic validation emits no Google eligibility findings', validMeta13PPipeline([
    '@type' => 'Product',
    'name' => 123,
]));

$warningResult = SeoMetaValidator::validate([
    'title' => 'A valid pipeline product title',
    'jsonLd' => [
        '@type' => 'Product',
        'name' => 'Valid product',
    ],
]);
assertTrueValue13PPipeline('existing missing description warning keeps result valid', $warningResult->isValid);
assertSameValue13PPipeline('existing warning remains a warning', 1, count($warningResult->warnings));
assertSameValue13PPipeline('existing warning code remains unchanged', 'missing_description', $warningResult->warnings[0]->code);

$invalidReport = SeoValidationReportBuilder::build(validMeta13PPipeline([
    '@type' => 'Offer',
    'price' => [],
]));
assertFalseValue13PPipeline('semantic report is invalid', $invalidReport->isValid);
assertSameValue13PPipeline('semantic report score uses existing error penalty', 75, $invalidReport->score);
assertSameValue13PPipeline('semantic report grade uses existing score contract', 'C', $invalidReport->grade);
assertSameValue13PPipeline('semantic report summary status is fail', 'fail', $invalidReport->summary['status']);
assertSameValue13PPipeline('semantic report summary message is unchanged', 'SEO validation failed.', $invalidReport->summary['message']);
assertSameValue13PPipeline('semantic report exposes one error', 1, $invalidReport->errorCount);
assertSameValue13PPipeline('semantic report exposes the semantic issue', 'json_ld_invalid_property', $invalidReport->errors[0]['code']);
assertSameValue13PPipeline('semantic report exposes the semantic field', 'jsonLd.price', $invalidReport->errors[0]['field']);
assertSameValue13PPipeline('report array exporter preserves public shape', $invalidReport->toArray(), SeoValidationReportExporter::toArray($invalidReport));

$reportJson = SeoValidationReportExporter::toJson($invalidReport);
assertSameValue13PPipeline('report JSON exporter preserves public shape', $invalidReport->toArray(), json_decode($reportJson, true));
$reportMarkdown = SeoValidationReportExporter::toMarkdown($invalidReport);
assertTrueValue13PPipeline('report Markdown exporter includes semantic issue code', str_contains($reportMarkdown, 'json_ld_invalid_property'));
assertTrueValue13PPipeline('report Markdown exporter includes semantic field path', str_contains($reportMarkdown, 'jsonLd.price'));

$validMeta = validMeta13PPipeline([
    '@type' => 'Product',
    'name' => 'Valid product',
]);
$warningMeta = [
    'title' => 'A valid pipeline product title',
];
$invalidMeta = validMeta13PPipeline([
    '@type' => 'AggregateOffer',
    'offerCount' => 1.5,
]);
$batch = SeoValidationBatchReportBuilder::build([
    ['meta' => $validMeta],
    ['meta' => $warningMeta],
    ['meta' => $invalidMeta],
]);
assertFalseValue13PPipeline('semantic batch is invalid when one report is invalid', $batch->isValid);
assertFalseValue13PPipeline('warning batch is not healthy', $batch->isHealthy);
assertSameValue13PPipeline('batch total count remains compatible', 3, $batch->totalCount);
assertSameValue13PPipeline('batch valid count remains compatible', 2, $batch->validCount);
assertSameValue13PPipeline('batch invalid count remains compatible', 1, $batch->invalidCount);
assertSameValue13PPipeline('batch warning count aggregates existing warnings', 1, $batch->warningCount);
assertSameValue13PPipeline('batch error count aggregates semantic errors', 1, $batch->errorCount);
assertSameValue13PPipeline('batch semantic error score is included', 90.0, $batch->averageScore);
assertSameValue13PPipeline('batch summary status is fail', 'fail', $batch->summary['status']);
assertSameValue13PPipeline('batch summary message is unchanged', 'SEO batch validation failed.', $batch->summary['message']);
assertSameValue13PPipeline('batch invalid report carries semantic issue', 'json_ld_invalid_property', $batch->reports[2]->errors[0]['code']);
assertSameValue13PPipeline('batch array exporter preserves public shape', $batch->toArray(), SeoValidationBatchReportExporter::toArray($batch));
$batchJson = json_decode(SeoValidationBatchReportExporter::toJson($batch), true);
assertSameValue13PPipeline('batch JSON exporter preserves validity', $batch->isValid, $batchJson['is_valid']);
assertSameValue13PPipeline('batch JSON exporter preserves average score', 90.0, (float) $batchJson['average_score']);
assertSameValue13PPipeline('batch JSON exporter preserves semantic issue', 'json_ld_invalid_property', $batchJson['reports'][2]['errors'][0]['code']);
$batchMarkdown = SeoValidationBatchReportExporter::toMarkdown($batch);
assertTrueValue13PPipeline('batch Markdown exporter includes the invalid report summary', str_contains($batchMarkdown, '### Report 3'));
assertTrueValue13PPipeline('batch Markdown exporter preserves the invalid report status', str_contains($batchMarkdown, '- Status: fail'));

foreach (['jsonLd', 'json_ld', 'schema', 'schemas'] as $alias) {
    $aliasMeta = validMeta13PPipeline();
    unset($aliasMeta['jsonLd']);
    $aliasMeta[$alias] = [
        '@type' => 'ProductGroup',
        'hasVariant' => [
            '@type' => 'Offer',
        ],
    ];

    $aliasResult = SeoMetaValidator::validate($aliasMeta);
    assertFalseValue13PPipeline($alias . ' alias preserves semantic invalidity', $aliasResult->isValid);
    assertIssue13PPipeline($alias . ' alias reaches semantic validation', $aliasResult, 'json_ld_invalid_relationship', 'jsonLd.hasVariant');
}

echo "Phase 13P validation pipeline tests passed.\n";
