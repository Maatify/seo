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
use Maatify\Seo\Web\Validation\SeoMetaValidator;
use Maatify\Seo\Web\Validation\SeoValidationReportBuilder;
use Maatify\Seo\Web\Validation\SeoValidationScoreCalculator;

function assertTrueValue11C(string $label, bool $actual): void
{
    if (!$actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertFalseValue11C(string $label, bool $actual): void
{
    if ($actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertSameValue11C(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertThrowsInvalidConfig11C(string $label, callable $callback): void
{
    try {
        $callback();
    } catch (SeoInvalidArgumentException) {
        return;
    }

    fwrite(STDERR, "Assertion failed: {$label}\nExpected SeoInvalidArgumentException.\n");
    exit(1);
}

$validMeta = [
    'title' => 'A useful product page title',
    'description' => 'This useful product page description is long enough for ordinary search result snippets.',
    'canonical' => 'https://example.com/products/useful',
    'robots' => 'index,follow',
];

$pass = SeoValidationReportBuilder::build($validMeta);
assertTrueValue11C('valid metadata report is valid', $pass->isValid);
assertTrueValue11C('valid metadata report is healthy', $pass->isHealthy);
assertSameValue11C('valid metadata status', 'pass', $pass->summary['status']);
assertSameValue11C('valid metadata message', 'SEO validation passed.', $pass->summary['message']);
assertSameValue11C('valid metadata score', 100, $pass->score);
assertSameValue11C('valid metadata grade', 'A', $pass->grade);

$warning = SeoValidationReportBuilder::build(['title' => 'A useful product page title']);
assertTrueValue11C('warning report has no errors', $warning->isValid);
assertSameValue11C('warning report status', 'warning', $warning->summary['status']);
assertSameValue11C('warning report message', 'SEO validation completed with warnings.', $warning->summary['message']);
assertSameValue11C('warning report warning count', 1, $warning->warningCount);
assertSameValue11C('warning report exposes warnings', 'missing_description', $warning->warnings[0]['code']);

$fail = SeoValidationReportBuilder::build(['description' => 'This page has a valid description but no title at all.']);
assertFalseValue11C('error report is not valid', $fail->isValid);
assertSameValue11C('error report status', 'fail', $fail->summary['status']);
assertSameValue11C('error report message', 'SEO validation failed.', $fail->summary['message']);
assertSameValue11C('error report error count', 1, $fail->errorCount);
assertSameValue11C('error report exposes errors', 'missing_title', $fail->errors[0]['code']);

$unhealthy = SeoValidationReportBuilder::build($validMeta, [], ['healthyMinimumScore' => 100, 'infoPenalty' => 1]);
assertSameValue11C('healthy threshold equal score passes', 'pass', $unhealthy->summary['status']);
$unhealthy = SeoValidationReportBuilder::build(['title' => 'A useful product page title'], [], ['warningPenalty' => 1, 'healthyMinimumScore' => 100]);
assertTrueValue11C('unhealthy without errors remains valid', $unhealthy->isValid);
assertFalseValue11C('unhealthy without errors is not healthy', $unhealthy->isHealthy);
assertSameValue11C('unhealthy without errors is warning report', 'warning', $unhealthy->summary['status']);

$context = ['url' => 'https://example.com/products/useful', 'entityType' => 'product', 'entityId' => 123, 'language' => 'en', 'source' => 'qa'];
$contextReport = SeoValidationReportBuilder::build($validMeta, [], [], $context);
assertSameValue11C('context is preserved as-is', $context, $contextReport->context);

$counts = SeoValidationReportBuilder::build(['title' => '', 'description' => 'Short']);
assertSameValue11C('report exposes issue count', 2, count($counts->issues));
assertSameValue11C('report exposes error count', 1, $counts->errorCount);
assertSameValue11C('report exposes warning count', 1, $counts->warningCount);
assertSameValue11C('report exposes info count', 0, $counts->infoCount);
assertSameValue11C('report exposes info array', [], $counts->info);
assertSameValue11C('report exposes deductions count', 2, count($counts->deductions));

$arrayOutput = $counts->toArray();
assertSameValue11C('array output includes score', $counts->score, $arrayOutput['score']);
assertSameValue11C('jsonSerialize matches toArray', $arrayOutput, $counts->jsonSerialize());
$json = json_encode($counts, JSON_THROW_ON_ERROR);
assertTrueValue11C('JSON serialization includes summary', str_contains($json, 'summary'));

$originalMeta = $validMeta;
SeoValidationReportBuilder::build($validMeta);
assertSameValue11C('original metadata array remains unchanged', $originalMeta, $validMeta);

$validationBefore = SeoMetaValidator::validate($validMeta)->toArray();
SeoValidationReportBuilder::build($validMeta);
$validationAfter = SeoMetaValidator::validate($validMeta)->toArray();
assertSameValue11C('builder does not change validator behavior', $validationBefore, $validationAfter);

$result = SeoMetaValidator::validate(['title' => 'A useful product page title']);
$scoreBefore = SeoValidationScoreCalculator::score($result)->toArray();
SeoValidationReportBuilder::build(['title' => 'A useful product page title']);
$scoreAfter = SeoValidationScoreCalculator::score($result)->toArray();
assertSameValue11C('builder does not change score calculator behavior', $scoreBefore, $scoreAfter);
assertSameValue11C('builder does not mutate validation result DTO', $result->toArray(), SeoMetaValidator::validate(['title' => 'A useful product page title'])->toArray());

assertThrowsInvalidConfig11C('invalid validation options throw module exception', static function (): void {
    SeoValidationReportBuilder::build(['title' => 'A useful product page title'], ['requireCanonical' => 'yes']);
});
assertThrowsInvalidConfig11C('invalid score options throw module exception', static function () use ($validMeta): void {
    SeoValidationReportBuilder::build($validMeta, [], ['healthyMinimumScore' => 101]);
});

assertFalseValue11C('report builder does not send headers', headers_sent());

fwrite(STDOUT, "Phase 11C SEO validation report helpers tests passed.\n");
