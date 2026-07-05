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

use Maatify\Seo\Exception\SeoInvalidArgumentException;
use Maatify\Seo\Web\Validation\DTO\SeoValidationBatchReportDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationReportDTO;
use Maatify\Seo\Web\Validation\SeoValidationBatchReportBuilder;
use Maatify\Seo\Web\Validation\SeoValidationBatchReportExporter;

function assertTrueValue11G(string $label, bool $actual): void
{
    if (!$actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertFalseValue11G(string $label, bool $actual): void
{
    if ($actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertSameValue11G(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertFloatSameValue11G(string $label, float $expected, float $actual): void
{
    if (abs($expected - $actual) > 0.000001) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertContainsValue11G(string $label, string $needle, string $haystack): void
{
    if (!str_contains($haystack, $needle)) {
        fwrite(STDERR, "Assertion failed: {$label}\nMissing needle: {$needle}\n");
        exit(1);
    }
}

function assertThrowsInvalidExport11G(string $label, callable $callback): void
{
    try {
        $callback();
    } catch (SeoInvalidArgumentException) {
        return;
    }

    fwrite(STDERR, "Assertion failed: {$label}\nExpected SeoInvalidArgumentException.\n");
    exit(1);
}

$reportOne = new SeoValidationReportDTO(
    isValid: true,
    isHealthy: true,
    score: 99,
    grade: 'A',
    errorCount: 0,
    warningCount: 0,
    infoCount: 1,
    issues: [
        ['code' => 'canonical_present', 'severity' => 'info', 'message' => 'Canonical URL is present.', 'field' => 'canonical'],
    ],
    errors: [],
    warnings: [],
    info: [
        ['code' => 'canonical_present', 'severity' => 'info', 'message' => 'Canonical URL is present.', 'field' => 'canonical'],
    ],
    deductions: [],
    context: ['url' => 'https://example.com/products/useful', 'entityType' => 'product', 'entityId' => 123],
    summary: ['status' => 'pass', 'message' => 'SEO validation passed.'],
);
$reportTwo = new SeoValidationReportDTO(
    isValid: false,
    isHealthy: false,
    score: 70,
    grade: 'C',
    errorCount: 1,
    warningCount: 1,
    infoCount: 0,
    issues: [
        ['code' => 'missing_title', 'severity' => 'error', 'message' => 'Title is required.', 'field' => 'title'],
        ['code' => 'short_description', 'severity' => 'warning', 'message' => 'Description is short.', 'field' => 'description'],
    ],
    errors: [
        ['code' => 'missing_title', 'severity' => 'error', 'message' => 'Title is required.', 'field' => 'title'],
    ],
    warnings: [
        ['code' => 'short_description', 'severity' => 'warning', 'message' => 'Description is short.', 'field' => 'description'],
    ],
    info: [],
    deductions: [
        ['code' => 'missing_title', 'severity' => 'error', 'field' => 'title', 'points' => 25],
        ['code' => 'short_description', 'severity' => 'warning', 'field' => 'description', 'points' => 5],
    ],
    context: ['url' => 'https://example.com/products/broken', 'source' => 'fixture'],
    summary: ['status' => 'fail', 'message' => 'SEO validation failed.'],
);

$batch = new SeoValidationBatchReportDTO(
    isValid: false,
    isHealthy: false,
    totalCount: 2,
    validCount: 1,
    invalidCount: 1,
    healthyCount: 1,
    unhealthyCount: 1,
    errorCount: 1,
    warningCount: 1,
    infoCount: 1,
    averageScore: 84.5,
    minScore: 70,
    maxScore: 99,
    reports: [$reportOne, $reportTwo],
    summary: ['status' => 'fail', 'message' => 'SEO batch validation failed.'],
);
$before = $batch->toArray();

assertSameValue11G('toArray returns full batch data', $before, SeoValidationBatchReportExporter::toArray($batch));

$json = SeoValidationBatchReportExporter::toJson($batch);
assertTrueValue11G('toJson returns valid JSON', is_array(json_decode($json, true)));
assertContainsValue11G('default JSON is readable', "\n", $json);
assertContainsValue11G('default JSON keeps readable URL', 'https://example.com/products/useful', $json);

$escapedJson = SeoValidationBatchReportExporter::toJson($batch, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES);
assertFalseValue11G('custom JSON flags are respected instead of pretty default', str_contains($escapedJson, "\n"));
assertSameValue11G('custom JSON decodes to full batch', $before, json_decode($escapedJson, true));

assertSameValue11G('toSummaryArray returns compact expected fields', [
    'isValid' => false,
    'isHealthy' => false,
    'totalCount' => 2,
    'validCount' => 1,
    'invalidCount' => 1,
    'healthyCount' => 1,
    'unhealthyCount' => 1,
    'errorCount' => 1,
    'warningCount' => 1,
    'infoCount' => 1,
    'averageScore' => 84.5,
    'minScore' => 70,
    'maxScore' => 99,
    'status' => 'fail',
    'message' => 'SEO batch validation failed.',
], SeoValidationBatchReportExporter::toSummaryArray($batch));

$markdown = SeoValidationBatchReportExporter::toMarkdown($batch);
assertContainsValue11G('markdown includes title', '# SEO Validation Batch Report', $markdown);
assertContainsValue11G('markdown includes status', '- Status: fail', $markdown);
assertContainsValue11G('markdown includes message', '- Message: SEO batch validation failed.', $markdown);
assertContainsValue11G('markdown includes valid flag', '- Valid: false', $markdown);
assertContainsValue11G('markdown includes healthy flag', '- Healthy: false', $markdown);
assertContainsValue11G('markdown includes total count', '- Total: 2', $markdown);
assertContainsValue11G('markdown includes valid count', '- Valid Count: 1', $markdown);
assertContainsValue11G('markdown includes invalid count', '- Invalid Count: 1', $markdown);
assertContainsValue11G('markdown includes healthy count', '- Healthy Count: 1', $markdown);
assertContainsValue11G('markdown includes unhealthy count', '- Unhealthy Count: 1', $markdown);
assertContainsValue11G('markdown includes error count', '- Errors: 1', $markdown);
assertContainsValue11G('markdown includes warning count', '- Warnings: 1', $markdown);
assertContainsValue11G('markdown includes info count', '- Info: 1', $markdown);
assertContainsValue11G('markdown includes average score', '- Average Score: 84.5', $markdown);
assertContainsValue11G('markdown includes min score', '- Min Score: 70', $markdown);
assertContainsValue11G('markdown includes max score', '- Max Score: 99', $markdown);
assertContainsValue11G('markdown includes per-report section', '## Reports', $markdown);
assertContainsValue11G('markdown includes first report index', '### Report 1', $markdown);
assertContainsValue11G('markdown includes second report index', '### Report 2', $markdown);
assertContainsValue11G('markdown includes report status', '- Status: pass', $markdown);
assertContainsValue11G('markdown includes report message', '- Message: SEO validation failed.', $markdown);
assertContainsValue11G('markdown includes report score', '- Score: 70', $markdown);
assertContainsValue11G('markdown includes report grade', '- Grade: C', $markdown);
assertContainsValue11G('markdown includes report context header', '- Context:', $markdown);
assertContainsValue11G('markdown includes report context', '  - url: https://example.com/products/broken', $markdown);

assertSameValue11G('exporter does not mutate batch DTO', $before, $batch->toArray());

$validMeta = [
    'title' => 'A useful product page title',
    'description' => 'This useful product page description is long enough for ordinary search result snippets.',
    'canonical' => 'https://example.com/products/useful',
    'robots' => 'index,follow',
];
$passBatch = SeoValidationBatchReportBuilder::build([
    ['meta' => $validMeta, 'context' => ['source' => 'builder-pass']],
]);
assertSameValue11G('exporter works with pass batch built by builder', 'pass', SeoValidationBatchReportExporter::toSummaryArray($passBatch)['status']);
assertContainsValue11G('builder pass markdown includes context', '  - source: builder-pass', SeoValidationBatchReportExporter::toMarkdown($passBatch));

$warningBatch = SeoValidationBatchReportBuilder::build([
    ['meta' => $validMeta],
    ['meta' => ['title' => 'A useful product page title']],
]);
assertSameValue11G('exporter handles warning batch status', 'warning', SeoValidationBatchReportExporter::toSummaryArray($warningBatch)['status']);

$failBatch = SeoValidationBatchReportBuilder::build([
    ['meta' => $validMeta],
    ['meta' => ['description' => 'This page has a valid description but no title at all.']],
]);
assertSameValue11G('exporter handles fail batch status', 'fail', SeoValidationBatchReportExporter::toSummaryArray($failBatch)['status']);

assertFloatSameValue11G('builder average score is exported', $warningBatch->averageScore, SeoValidationBatchReportExporter::toSummaryArray($warningBatch)['averageScore']);

$invalidJsonReport = new SeoValidationReportDTO(
    isValid: true,
    isHealthy: true,
    score: 100,
    grade: 'A',
    errorCount: 0,
    warningCount: 0,
    infoCount: 0,
    issues: [],
    errors: [],
    warnings: [],
    info: [],
    deductions: [],
    context: ['notJson' => NAN],
    summary: ['status' => 'pass', 'message' => 'SEO validation passed.'],
);
$invalidJsonBatch = new SeoValidationBatchReportDTO(
    isValid: true,
    isHealthy: true,
    totalCount: 1,
    validCount: 1,
    invalidCount: 0,
    healthyCount: 1,
    unhealthyCount: 0,
    errorCount: 0,
    warningCount: 0,
    infoCount: 0,
    averageScore: 100.0,
    minScore: 100,
    maxScore: 100,
    reports: [$invalidJsonReport],
    summary: ['status' => 'pass', 'message' => 'SEO batch validation passed.'],
);
assertThrowsInvalidExport11G('invalid JSON encoding failure throws module exception', static function () use ($invalidJsonBatch): void {
    SeoValidationBatchReportExporter::toJson($invalidJsonBatch);
});

assertFalseValue11G('exporter does not send headers', headers_sent());

fwrite(STDOUT, "Phase 11G SEO validation batch report exporter tests passed.\n");
