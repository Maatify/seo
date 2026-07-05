<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation;

use Maatify\Seo\Web\Validation\DTO\SeoValidationIssueDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationReportDTO;

final class SeoValidationReportBuilder
{
    /**
     * @param array<string, mixed>|object $meta
     * @param array<string, mixed> $validationOptions
     * @param array<string, mixed> $scoreOptions
     * @param array<string, mixed> $context
     */
    public static function build(
        array|object $meta,
        array $validationOptions = [],
        array $scoreOptions = [],
        array $context = [],
    ): SeoValidationReportDTO {
        $validationResult = SeoMetaValidator::validate($meta, $validationOptions);
        $score = SeoValidationScoreCalculator::score($validationResult, $scoreOptions);

        return new SeoValidationReportDTO(
            isValid: $validationResult->isValid,
            isHealthy: $score->isHealthy,
            score: $score->score,
            grade: $score->grade,
            errorCount: $score->errorCount,
            warningCount: $score->warningCount,
            infoCount: $score->infoCount,
            issues: self::issuesToArray($validationResult->issues),
            errors: self::issuesToArray($validationResult->errors),
            warnings: self::issuesToArray($validationResult->warnings),
            info: self::issuesToArray($validationResult->info),
            deductions: $score->deductions,
            context: $context,
            summary: self::summary($validationResult->isValid, $score->isHealthy, $score->warningCount),
        );
    }

    /**
     * @param list<SeoValidationIssueDTO> $issues
     * @return list<array{code: string, severity: string, message: string, field: string|null}>
     */
    private static function issuesToArray(array $issues): array
    {
        return array_map(static fn (SeoValidationIssueDTO $issue): array => $issue->toArray(), $issues);
    }

    /** @return array{status: string, message: string} */
    private static function summary(bool $isValid, bool $isHealthy, int $warningCount): array
    {
        if (!$isValid) {
            return ['status' => 'fail', 'message' => 'SEO validation failed.'];
        }

        if ($warningCount > 0 || !$isHealthy) {
            return ['status' => 'warning', 'message' => 'SEO validation completed with warnings.'];
        }

        return ['status' => 'pass', 'message' => 'SEO validation passed.'];
    }
}
