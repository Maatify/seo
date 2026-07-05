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
use Maatify\Seo\Web\Validation\SeoValidationPreset;

function assertTrueValue11F(string $label, bool $actual): void
{
    if (!$actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertFalseValue11F(string $label, bool $actual): void
{
    if ($actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertSameValue11F(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertFloatSameValue11F(string $label, float $expected, float $actual): void
{
    if (abs($expected - $actual) > 0.000001) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertThrowsInvalidConfig11F(string $label, callable $callback): void
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
$secondValidMeta = (object) [
    'title' => 'Another useful page title',
    'description' => 'Another useful page description is long enough for ordinary search result snippets.',
    'canonical' => 'https://example.com/products/another',
];

$passBatch = SeoValidationBatchReportBuilder::build([
    ['meta' => $validMeta, 'context' => ['url' => 'https://example.com/products/useful']],
    ['meta' => $secondValidMeta, 'context' => ['url' => 'https://example.com/products/another']],
]);
assertTrueValue11F('batch DTO is returned', $passBatch instanceof SeoValidationBatchReportDTO);
assertTrueValue11F('multiple valid items batch is valid', $passBatch->isValid);
assertTrueValue11F('multiple valid items batch is healthy', $passBatch->isHealthy);
assertSameValue11F('pass summary status', 'pass', $passBatch->summary['status']);
assertSameValue11F('pass summary message', 'SEO batch validation passed.', $passBatch->summary['message']);
assertSameValue11F('pass total count', 2, $passBatch->totalCount);
assertSameValue11F('pass valid count', 2, $passBatch->validCount);
assertSameValue11F('pass invalid count', 0, $passBatch->invalidCount);
assertSameValue11F('pass healthy count', 2, $passBatch->healthyCount);
assertSameValue11F('pass unhealthy count', 0, $passBatch->unhealthyCount);
assertSameValue11F('reports are DTO list', true, $passBatch->reports[0] instanceof SeoValidationReportDTO);

$failBatch = SeoValidationBatchReportBuilder::build([
    ['meta' => $validMeta],
    ['meta' => ['description' => 'This page has a valid description but no title at all.']],
]);
assertFalseValue11F('invalid item batch is invalid', $failBatch->isValid);
assertSameValue11F('fail summary status', 'fail', $failBatch->summary['status']);
assertSameValue11F('fail summary message', 'SEO batch validation failed.', $failBatch->summary['message']);
assertSameValue11F('fail valid count', 1, $failBatch->validCount);
assertSameValue11F('fail invalid count', 1, $failBatch->invalidCount);
assertSameValue11F('fail error count sum', 1, $failBatch->errorCount);

$warningBatch = SeoValidationBatchReportBuilder::build([
    ['meta' => $validMeta],
    ['meta' => ['title' => 'A useful product page title']],
]);
assertTrueValue11F('warning batch remains valid', $warningBatch->isValid);
assertFalseValue11F('warning batch is not healthy when warnings exist', $warningBatch->isHealthy);
assertSameValue11F('warning summary status', 'warning', $warningBatch->summary['status']);
assertSameValue11F('warning summary message', 'SEO batch validation completed with warnings.', $warningBatch->summary['message']);
assertSameValue11F('warning count sum', 1, $warningBatch->warningCount);

$unhealthyBatch = SeoValidationBatchReportBuilder::build([
    ['meta' => $validMeta],
    ['meta' => ['title' => 'A useful product page title']],
], [], ['warningPenalty' => 1, 'healthyMinimumScore' => 100]);
assertTrueValue11F('unhealthy warning batch remains valid', $unhealthyBatch->isValid);
assertFalseValue11F('unhealthy warning batch is not healthy', $unhealthyBatch->isHealthy);
assertSameValue11F('unhealthy warning batch status', 'warning', $unhealthyBatch->summary['status']);

$contextBatch = SeoValidationBatchReportBuilder::build([
    ['meta' => $validMeta, 'context' => ['url' => 'https://example.com/a']],
    ['meta' => $validMeta, 'context' => ['source' => 'item', 'entityId' => 200]],
], [], [], ['source' => 'shared', 'language' => 'en', 'entityId' => 100]);
assertSameValue11F('shared context merges into first report', ['source' => 'shared', 'language' => 'en', 'entityId' => 100, 'url' => 'https://example.com/a'], $contextBatch->reports[0]->context);
assertSameValue11F('item context overrides shared context', ['source' => 'item', 'language' => 'en', 'entityId' => 200], $contextBatch->reports[1]->context);

$countBatch = SeoValidationBatchReportBuilder::build([
    ['meta' => $validMeta],
    ['meta' => ['title' => 'A useful product page title']],
    ['meta' => ['title' => '', 'description' => 'Short']],
]);
assertSameValue11F('count total', 3, $countBatch->totalCount);
assertSameValue11F('count valid', 2, $countBatch->validCount);
assertSameValue11F('count invalid', 1, $countBatch->invalidCount);
assertSameValue11F('count healthy', 2, $countBatch->healthyCount);
assertSameValue11F('count unhealthy', 1, $countBatch->unhealthyCount);
assertSameValue11F('sum errors', 1, $countBatch->errorCount);
assertSameValue11F('sum warnings', 2, $countBatch->warningCount);
assertSameValue11F('sum info', 0, $countBatch->infoCount);
assertFloatSameValue11F('average score', (100 + 95 + 70) / 3, $countBatch->averageScore);
assertSameValue11F('min score', 70, $countBatch->minScore);
assertSameValue11F('max score', 100, $countBatch->maxScore);

$arrayOutput = $countBatch->toArray();
assertSameValue11F('toArray includes report arrays', $countBatch->reports[0]->toArray(), $arrayOutput['reports'][0]);
assertSameValue11F('toArray includes summary', $countBatch->summary, $arrayOutput['summary']);
assertSameValue11F('jsonSerialize matches toArray', $arrayOutput, $countBatch->jsonSerialize());
$json = json_encode($countBatch, JSON_THROW_ON_ERROR);
assertTrueValue11F('JSON serialization includes batch counts', str_contains($json, 'total_count'));

assertThrowsInvalidConfig11F('rejects empty items list', static function (): void {
    SeoValidationBatchReportBuilder::build([]);
});
assertThrowsInvalidConfig11F('rejects non-list items array', static function () use ($validMeta): void {
    SeoValidationBatchReportBuilder::build(['first' => ['meta' => $validMeta]]);
});
assertThrowsInvalidConfig11F('rejects item missing meta', static function (): void {
    SeoValidationBatchReportBuilder::build([['context' => ['url' => 'https://example.com']]]);
});
assertThrowsInvalidConfig11F('rejects invalid meta', static function (): void {
    SeoValidationBatchReportBuilder::build([['meta' => 'invalid']]);
});
assertThrowsInvalidConfig11F('rejects invalid item context', static function () use ($validMeta): void {
    SeoValidationBatchReportBuilder::build([['meta' => $validMeta, 'context' => 'invalid']]);
});

$inputObject = (object) ['title' => 'A useful product page title', 'description' => 'This useful product page description is long enough.'];
$inputItems = [['meta' => $validMeta, 'context' => ['source' => 'original']], ['meta' => $inputObject]];
$originalItems = $inputItems;
$originalObjectVars = get_object_vars($inputObject);
SeoValidationBatchReportBuilder::build($inputItems, [], [], ['source' => 'shared']);
assertSameValue11F('input item arrays are not mutated', $originalItems, $inputItems);
assertSameValue11F('input meta object is not mutated', $originalObjectVars, get_object_vars($inputObject));

$standard = SeoValidationPreset::standard();
$standardBatch = SeoValidationBatchReportBuilder::build([
    ['meta' => $validMeta],
], $standard['validationOptions'], $standard['scoreOptions']);
assertSameValue11F('standard preset works with batch builder', 'pass', $standardBatch->summary['status']);

assertFalseValue11F('batch builder does not send headers', headers_sent());

fwrite(STDOUT, "Phase 11F SEO validation batch report helpers tests passed.\n");
