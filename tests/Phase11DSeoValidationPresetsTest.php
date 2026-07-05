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
use Maatify\Seo\Web\Validation\DTO\SeoValidationResultDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationScoreDTO;
use Maatify\Seo\Web\Validation\SeoMetaValidator;
use Maatify\Seo\Web\Validation\SeoValidationPreset;
use Maatify\Seo\Web\Validation\SeoValidationReportBuilder;
use Maatify\Seo\Web\Validation\SeoValidationScoreCalculator;

function assertTrueValue11D(string $label, bool $actual): void
{
    if (!$actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertFalseValue11D(string $label, bool $actual): void
{
    if ($actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertSameValue11D(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertThrowsInvalidPreset11D(string $label, callable $callback): void
{
    try {
        $callback();
    } catch (SeoInvalidArgumentException) {
        return;
    }

    fwrite(STDERR, "Assertion failed: {$label}\nExpected SeoInvalidArgumentException.\n");
    exit(1);
}

$minimalExpected = [
    'validationOptions' => ['requireCanonical' => false],
    'scoreOptions' => ['errorPenalty' => 25, 'warningPenalty' => 5, 'infoPenalty' => 0, 'healthyMinimumScore' => 80],
];
$standardExpected = [
    'validationOptions' => ['requireCanonical' => true, 'titleMinLength' => 10, 'titleMaxLength' => 60, 'descriptionMinLength' => 50, 'descriptionMaxLength' => 160],
    'scoreOptions' => ['errorPenalty' => 25, 'warningPenalty' => 5, 'infoPenalty' => 0, 'healthyMinimumScore' => 80],
];
$strictExpected = [
    'validationOptions' => ['requireCanonical' => true, 'titleMinLength' => 20, 'titleMaxLength' => 60, 'descriptionMinLength' => 80, 'descriptionMaxLength' => 155],
    'scoreOptions' => ['errorPenalty' => 30, 'warningPenalty' => 10, 'infoPenalty' => 0, 'healthyMinimumScore' => 90],
];

assertSameValue11D('minimal preset structure', $minimalExpected, SeoValidationPreset::minimal());
assertSameValue11D('standard preset structure', $standardExpected, SeoValidationPreset::standard());
assertSameValue11D('strict preset structure', $strictExpected, SeoValidationPreset::strict());

$copyOne = SeoValidationPreset::standard();
$copyOne['validationOptions']['requireCanonical'] = false;
$copyOne['scoreOptions']['warningPenalty'] = 99;
assertSameValue11D('standard preset returns independent validation copy', true, SeoValidationPreset::standard()['validationOptions']['requireCanonical']);
assertSameValue11D('standard preset returns independent score copy', 5, SeoValidationPreset::standard()['scoreOptions']['warningPenalty']);

$validMeta = [
    'title' => 'A useful product page title',
    'description' => 'This useful product page description is long enough for strict validation and ordinary snippets.',
    'canonical' => 'https://example.com/products/useful',
    'robots' => 'index,follow',
];

$standard = SeoValidationPreset::standard();
$validationResult = SeoMetaValidator::validate($validMeta, $standard['validationOptions']);
assertTrueValue11D('preset validation options work with validator', $validationResult instanceof SeoValidationResultDTO);
assertTrueValue11D('standard preset validates valid metadata', $validationResult->isValid);

$score = SeoValidationScoreCalculator::score($validationResult, $standard['scoreOptions']);
assertTrueValue11D('preset score options work with score calculator', $score instanceof SeoValidationScoreDTO);
assertSameValue11D('standard preset valid metadata score', 100, $score->score);

$report = SeoValidationReportBuilder::build($validMeta, $standard['validationOptions'], $standard['scoreOptions']);
assertTrueValue11D('presets work with report builder', $report instanceof SeoValidationReportDTO);
assertSameValue11D('standard preset valid metadata report status', 'pass', $report->summary['status']);

$strict = SeoValidationPreset::strict();
$strictReport = SeoValidationReportBuilder::build([
    'title' => 'Short title',
    'description' => 'This description is long enough for standard validation but not strict.',
    'canonical' => 'https://example.com/strict',
], $strict['validationOptions'], $strict['scoreOptions']);
assertSameValue11D('strict preset warning penalty applies through report builder', 80, $strictReport->score);
assertSameValue11D('strict preset healthy threshold applies through report builder', false, $strictReport->isHealthy);

assertSameValue11D('for minimal matches minimal', SeoValidationPreset::minimal(), SeoValidationPreset::for('minimal'));
assertSameValue11D('for standard matches standard', SeoValidationPreset::standard(), SeoValidationPreset::for('standard'));
assertSameValue11D('for strict matches strict', SeoValidationPreset::strict(), SeoValidationPreset::for('strict'));

assertThrowsInvalidPreset11D('invalid preset name throws module exception', static function (): void {
    SeoValidationPreset::for('unknown');
});

$minimal = SeoValidationPreset::minimal();
$minimalResult = SeoMetaValidator::validate(['title' => 'A useful product page title', 'description' => 'This useful product page description is long enough.'], $minimal['validationOptions']);
assertFalseValue11D('minimal preset does not require canonical', $minimalResult->hasWarnings);
assertFalseValue11D('presets do not send headers', headers_sent());

fwrite(STDOUT, "Phase 11D SEO validation presets tests passed.\n");
