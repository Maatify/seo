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
use Maatify\Seo\Web\Validation\DTO\SeoValidationIssueDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationResultDTO;
use Maatify\Seo\Web\Validation\SeoValidationScoreCalculator;

function assertTrueValue11B(string $label, bool $actual): void
{
    if (!$actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertFalseValue11B(string $label, bool $actual): void
{
    if ($actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertSameValue11B(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertThrowsInvalidConfig11B(string $label, callable $callback): void
{
    try {
        $callback();
    } catch (SeoInvalidArgumentException) {
        return;
    }

    fwrite(STDERR, "Assertion failed: {$label}\nExpected SeoInvalidArgumentException.\n");
    exit(1);
}

function issue11B(string $code, string $severity, ?string $field = null): SeoValidationIssueDTO
{
    return new SeoValidationIssueDTO($code, $severity, 'Test issue.', $field);
}

$perfect = SeoValidationScoreCalculator::score(new SeoValidationResultDTO());
assertSameValue11B('perfect result score', 100, $perfect->score);
assertSameValue11B('perfect result grade', 'A', $perfect->grade);
assertSameValue11B('perfect result deductions', [], $perfect->deductions);
assertTrueValue11B('perfect result is healthy', $perfect->isHealthy);

$oneWarningResult = new SeoValidationResultDTO([issue11B('warning_code', SeoValidationIssueDTO::SEVERITY_WARNING, 'description')]);
$oneWarning = SeoValidationScoreCalculator::score($oneWarningResult);
assertSameValue11B('one warning default score', 95, $oneWarning->score);
assertSameValue11B('one warning grade', 'A', $oneWarning->grade);
assertSameValue11B('one warning count', 1, $oneWarning->warningCount);
assertSameValue11B('one warning deduction points', 5, $oneWarning->deductions[0]['points']);

$oneErrorResult = new SeoValidationResultDTO([issue11B('error_code', SeoValidationIssueDTO::SEVERITY_ERROR, 'title')]);
$oneError = SeoValidationScoreCalculator::score($oneErrorResult);
assertSameValue11B('one error default score', 75, $oneError->score);
assertSameValue11B('one error grade', 'C', $oneError->grade);
assertSameValue11B('one error count', 1, $oneError->errorCount);
assertSameValue11B('one error deduction points', 25, $oneError->deductions[0]['points']);
assertFalseValue11B('one error below default healthy threshold', $oneError->isHealthy);

$multiple = SeoValidationScoreCalculator::score(new SeoValidationResultDTO([
    issue11B('error_one', SeoValidationIssueDTO::SEVERITY_ERROR, 'title'),
    issue11B('error_two', SeoValidationIssueDTO::SEVERITY_ERROR, 'canonical'),
    issue11B('warning_one', SeoValidationIssueDTO::SEVERITY_WARNING, 'description'),
    issue11B('warning_two', SeoValidationIssueDTO::SEVERITY_WARNING, 'robots'),
]));
assertSameValue11B('multiple issues deduct correctly', 40, $multiple->score);
assertSameValue11B('multiple issues grade', 'F', $multiple->grade);
assertSameValue11B('multiple issue deduction count', 4, count($multiple->deductions));

$belowZero = SeoValidationScoreCalculator::score(new SeoValidationResultDTO([
    issue11B('error_one', SeoValidationIssueDTO::SEVERITY_ERROR),
    issue11B('error_two', SeoValidationIssueDTO::SEVERITY_ERROR),
    issue11B('error_three', SeoValidationIssueDTO::SEVERITY_ERROR),
    issue11B('error_four', SeoValidationIssueDTO::SEVERITY_ERROR),
    issue11B('error_five', SeoValidationIssueDTO::SEVERITY_ERROR),
]));
assertSameValue11B('score never below zero', 0, $belowZero->score);

assertSameValue11B('grade boundary A', 'A', SeoValidationScoreCalculator::score($oneWarningResult, ['warningPenalty' => 10])->grade);
assertSameValue11B('grade boundary B', 'B', SeoValidationScoreCalculator::score($oneWarningResult, ['warningPenalty' => 11])->grade);
assertSameValue11B('grade boundary C', 'C', SeoValidationScoreCalculator::score($oneWarningResult, ['warningPenalty' => 21])->grade);
assertSameValue11B('grade boundary D', 'D', SeoValidationScoreCalculator::score($oneWarningResult, ['warningPenalty' => 31])->grade);
assertSameValue11B('grade boundary F', 'F', SeoValidationScoreCalculator::score($oneWarningResult, ['warningPenalty' => 41])->grade);

assertTrueValue11B('default threshold healthy true at 80', SeoValidationScoreCalculator::score($oneWarningResult, ['warningPenalty' => 20])->isHealthy);
assertFalseValue11B('default threshold healthy false below 80', SeoValidationScoreCalculator::score($oneWarningResult, ['warningPenalty' => 21])->isHealthy);
assertTrueValue11B('custom threshold healthy true', SeoValidationScoreCalculator::score($oneErrorResult, ['healthyMinimumScore' => 75])->isHealthy);
assertFalseValue11B('custom threshold healthy false', SeoValidationScoreCalculator::score($oneErrorResult, ['healthyMinimumScore' => 76])->isHealthy);

$customPenalties = SeoValidationScoreCalculator::score(new SeoValidationResultDTO([
    issue11B('custom_error', SeoValidationIssueDTO::SEVERITY_ERROR),
    issue11B('custom_warning', SeoValidationIssueDTO::SEVERITY_WARNING),
    issue11B('custom_info', SeoValidationIssueDTO::SEVERITY_INFO),
]), ['errorPenalty' => 10, 'warningPenalty' => 2, 'infoPenalty' => 1]);
assertSameValue11B('custom penalties score', 87, $customPenalties->score);
assertSameValue11B('custom info deduction points', 1, $customPenalties->deductions[2]['points']);

$infoDefault = SeoValidationScoreCalculator::score(new SeoValidationResultDTO([
    issue11B('info_code', SeoValidationIssueDTO::SEVERITY_INFO, 'jsonLd'),
]));
assertSameValue11B('info score default penalty zero', 100, $infoDefault->score);
assertSameValue11B('info count', 1, $infoDefault->infoCount);
assertSameValue11B('info deduction is present with zero points', 0, $infoDefault->deductions[0]['points']);

assertThrowsInvalidConfig11B('invalid option type throws module exception', static function () use ($oneWarningResult): void {
    SeoValidationScoreCalculator::score($oneWarningResult, ['warningPenalty' => '5']);
});
assertThrowsInvalidConfig11B('negative penalty throws module exception', static function () use ($oneWarningResult): void {
    SeoValidationScoreCalculator::score($oneWarningResult, ['errorPenalty' => -1]);
});
assertThrowsInvalidConfig11B('invalid healthy threshold throws module exception', static function () use ($oneWarningResult): void {
    SeoValidationScoreCalculator::score($oneWarningResult, ['healthyMinimumScore' => 101]);
});

$arrayOutput = $oneError->toArray();
assertSameValue11B('array output includes score', 75, $arrayOutput['score']);
assertSameValue11B('jsonSerialize matches toArray', $arrayOutput, $oneError->jsonSerialize());
$json = json_encode($oneError, JSON_THROW_ON_ERROR);
assertTrueValue11B('JSON serialization includes deductions', str_contains($json, 'deductions'));

$originalIssue = issue11B('original_warning', SeoValidationIssueDTO::SEVERITY_WARNING, 'robots');
$originalResult = new SeoValidationResultDTO([$originalIssue]);
$originalArrayBefore = $originalResult->toArray();
$score = SeoValidationScoreCalculator::score($originalResult);
assertSameValue11B('deduction entry is a copied array', ['code' => 'original_warning', 'severity' => 'warning', 'field' => 'robots', 'points' => 5], $score->deductions[0]);
assertSameValue11B('original validation result remains unchanged', $originalArrayBefore, $originalResult->toArray());
assertSameValue11B('deduction does not mutate original issue', 'original_warning', $originalIssue->code);

assertFalseValue11B('score calculator does not send headers', headers_sent());

fwrite(STDOUT, "Phase 11B SEO validation score helpers tests passed.\n");
