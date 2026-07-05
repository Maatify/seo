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
use Maatify\Seo\Web\Validation\DTO\SeoValidationReportDTO;
use Maatify\Seo\Web\Validation\SeoValidationReportBuilder;
use Maatify\Seo\Web\Validation\SeoValidationReportExporter;

function assertTrueValue11E(string $label, bool $actual): void
{
    if (!$actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertFalseValue11E(string $label, bool $actual): void
{
    if ($actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertSameValue11E(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertContainsValue11E(string $label, string $needle, string $haystack): void
{
    if (!str_contains($haystack, $needle)) {
        fwrite(STDERR, "Assertion failed: {$label}\nMissing needle: {$needle}\n");
        exit(1);
    }
}

function assertThrowsInvalidExport11E(string $label, callable $callback): void
{
    try {
        $callback();
    } catch (SeoInvalidArgumentException) {
        return;
    }

    fwrite(STDERR, "Assertion failed: {$label}\nExpected SeoInvalidArgumentException.\n");
    exit(1);
}

$report = new SeoValidationReportDTO(
    isValid: false,
    isHealthy: false,
    score: 60,
    grade: 'D',
    errorCount: 1,
    warningCount: 1,
    infoCount: 1,
    issues: [
        ['code' => 'missing_title', 'severity' => 'error', 'message' => 'Title is required.', 'field' => 'title'],
        ['code' => 'short_description', 'severity' => 'warning', 'message' => 'Description is short.', 'field' => 'description'],
        ['code' => 'canonical_present', 'severity' => 'info', 'message' => 'Canonical URL is present.', 'field' => 'canonical'],
    ],
    errors: [
        ['code' => 'missing_title', 'severity' => 'error', 'message' => 'Title is required.', 'field' => 'title'],
    ],
    warnings: [
        ['code' => 'short_description', 'severity' => 'warning', 'message' => 'Description is short.', 'field' => 'description'],
    ],
    info: [
        ['code' => 'canonical_present', 'severity' => 'info', 'message' => 'Canonical URL is present.', 'field' => 'canonical'],
    ],
    deductions: [
        ['code' => 'missing_title', 'severity' => 'error', 'field' => 'title', 'points' => 25],
        ['code' => 'short_description', 'severity' => 'warning', 'field' => 'description', 'points' => 5],
    ],
    context: ['url' => 'https://example.com/products/useful', 'entityType' => 'product', 'entityId' => 123],
    summary: ['status' => 'fail', 'message' => 'SEO validation failed.'],
);

$before = $report->toArray();

assertSameValue11E('toArray returns full report data', $before, SeoValidationReportExporter::toArray($report));

$json = SeoValidationReportExporter::toJson($report);
assertTrueValue11E('toJson returns valid JSON', is_array(json_decode($json, true)));
assertContainsValue11E('default JSON is readable', "\n", $json);
assertContainsValue11E('default JSON keeps readable URL', 'https://example.com/products/useful', $json);

$escapedJson = SeoValidationReportExporter::toJson($report, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES);
assertFalseValue11E('custom JSON flags are respected instead of pretty default', str_contains($escapedJson, "\n"));
assertSameValue11E('custom JSON decodes to full report', $before, json_decode($escapedJson, true));

assertSameValue11E('toSummaryArray returns compact expected fields', [
    'isValid' => false,
    'isHealthy' => false,
    'score' => 60,
    'grade' => 'D',
    'errorCount' => 1,
    'warningCount' => 1,
    'infoCount' => 1,
    'status' => 'fail',
    'message' => 'SEO validation failed.',
], SeoValidationReportExporter::toSummaryArray($report));

$markdown = SeoValidationReportExporter::toMarkdown($report);
assertContainsValue11E('markdown includes title', '# SEO Validation Report', $markdown);
assertContainsValue11E('markdown includes status', '- Status: fail', $markdown);
assertContainsValue11E('markdown includes message', '- Message: SEO validation failed.', $markdown);
assertContainsValue11E('markdown includes score', '- Score: 60', $markdown);
assertContainsValue11E('markdown includes grade', '- Grade: D', $markdown);
assertContainsValue11E('markdown includes valid flag', '- Valid: false', $markdown);
assertContainsValue11E('markdown includes healthy flag', '- Healthy: false', $markdown);
assertContainsValue11E('markdown includes error count', '- Errors: 1', $markdown);
assertContainsValue11E('markdown includes warning count', '- Warnings: 1', $markdown);
assertContainsValue11E('markdown includes info count', '- Info: 1', $markdown);
assertContainsValue11E('markdown groups errors', "### Errors\n- missing_title", $markdown);
assertContainsValue11E('markdown groups warnings', "### Warnings\n- short_description", $markdown);
assertContainsValue11E('markdown groups info', "### Info\n- canonical_present", $markdown);
assertContainsValue11E('markdown includes deductions section', '## Deductions', $markdown);
assertContainsValue11E('markdown includes deduction points', '- missing_title (error, field: title): -25 points', $markdown);
assertContainsValue11E('markdown includes context section', '## Context', $markdown);
assertContainsValue11E('markdown includes context value', '- url: https://example.com/products/useful', $markdown);

assertSameValue11E('exporter does not mutate report DTO', $before, $report->toArray());

$builtReport = SeoValidationReportBuilder::build([
    'title' => 'A useful product page title',
    'description' => 'This useful product page description is long enough for ordinary search result snippets.',
    'canonical' => 'https://example.com/products/useful',
    'robots' => 'index,follow',
], [], [], ['source' => 'builder-test']);
assertSameValue11E('exporter works with reports built by report builder', 'pass', SeoValidationReportExporter::toSummaryArray($builtReport)['status']);
assertContainsValue11E('builder report markdown includes context', '- source: builder-test', SeoValidationReportExporter::toMarkdown($builtReport));

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
assertThrowsInvalidExport11E('invalid JSON encoding failure throws module exception', static function () use ($invalidJsonReport): void {
    SeoValidationReportExporter::toJson($invalidJsonReport);
});

assertFalseValue11E('exporter does not send headers', headers_sent());

fwrite(STDOUT, "Phase 11E SEO validation report exporter tests passed.\n");
