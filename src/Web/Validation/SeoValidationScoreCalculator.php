<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation;

use Maatify\Seo\Exception\SeoInvalidArgumentException;
use Maatify\Seo\Web\Validation\DTO\SeoValidationIssueDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationResultDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationScoreDTO;

final class SeoValidationScoreCalculator
{
    /**
     * @param array<string, mixed> $options
     */
    public static function score(SeoValidationResultDTO $result, array $options = []): SeoValidationScoreDTO
    {
        $errorPenalty = self::penaltyOption($options, 'errorPenalty', 25);
        $warningPenalty = self::penaltyOption($options, 'warningPenalty', 5);
        $infoPenalty = self::penaltyOption($options, 'infoPenalty', 0);
        $healthyMinimumScore = self::healthyMinimumScoreOption($options);

        $totalDeduction = 0;
        $deductions = [];

        foreach ($result->issues as $issue) {
            $points = self::pointsForIssue($issue, $errorPenalty, $warningPenalty, $infoPenalty);
            $totalDeduction += $points;
            $deductions[] = [
                'code' => $issue->code,
                'severity' => $issue->severity,
                'field' => $issue->field,
                'points' => $points,
            ];
        }

        $score = max(0, min(100, 100 - $totalDeduction));

        return new SeoValidationScoreDTO(
            score: $score,
            grade: self::grade($score),
            errorCount: count($result->errors),
            warningCount: count($result->warnings),
            infoCount: count($result->info),
            deductions: $deductions,
            isHealthy: $score >= $healthyMinimumScore,
        );
    }

    /** @param array<string, mixed> $options */
    private static function penaltyOption(array $options, string $key, int $default): int
    {
        $value = self::intOption($options, $key, $default);
        if ($value < 0) {
            throw SeoInvalidArgumentException::invalidValue($key, 'Expected integer greater than or equal to 0.');
        }

        return $value;
    }

    /** @param array<string, mixed> $options */
    private static function healthyMinimumScoreOption(array $options): int
    {
        $value = self::intOption($options, 'healthyMinimumScore', 80);
        if ($value < 0 || $value > 100) {
            throw SeoInvalidArgumentException::invalidValue('healthyMinimumScore', 'Expected integer between 0 and 100.');
        }

        return $value;
    }

    /** @param array<string, mixed> $options */
    private static function intOption(array $options, string $key, int $default): int
    {
        if (!array_key_exists($key, $options)) {
            return $default;
        }

        if (!is_int($options[$key])) {
            throw SeoInvalidArgumentException::invalidValue($key, 'Expected integer.');
        }

        return $options[$key];
    }

    private static function pointsForIssue(SeoValidationIssueDTO $issue, int $errorPenalty, int $warningPenalty, int $infoPenalty): int
    {
        if ($issue->severity === SeoValidationIssueDTO::SEVERITY_ERROR) {
            return $errorPenalty;
        }

        if ($issue->severity === SeoValidationIssueDTO::SEVERITY_WARNING) {
            return $warningPenalty;
        }

        return $infoPenalty;
    }

    private static function grade(int $score): string
    {
        if ($score >= 90) {
            return 'A';
        }

        if ($score >= 80) {
            return 'B';
        }

        if ($score >= 70) {
            return 'C';
        }

        if ($score >= 60) {
            return 'D';
        }

        return 'F';
    }
}
