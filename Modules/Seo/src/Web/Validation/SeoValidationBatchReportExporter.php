<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation;

use Maatify\Seo\Exception\SeoInvalidArgumentException;
use Maatify\Seo\Web\Validation\DTO\SeoValidationBatchReportDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationReportDTO;

final class SeoValidationBatchReportExporter
{
    private const DEFAULT_JSON_FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    /**
     * @return array{is_valid: bool, is_healthy: bool, total_count: int, valid_count: int, invalid_count: int, healthy_count: int, unhealthy_count: int, error_count: int, warning_count: int, info_count: int, average_score: float, min_score: int, max_score: int, reports: list<array{is_valid: bool, is_healthy: bool, score: int, grade: string, error_count: int, warning_count: int, info_count: int, issues: list<array{code: string, severity: string, message: string, field: string|null}>, errors: list<array{code: string, severity: string, message: string, field: string|null}>, warnings: list<array{code: string, severity: string, message: string, field: string|null}>, info: list<array{code: string, severity: string, message: string, field: string|null}>, deductions: list<array{code: string, severity: string, field: string|null, points: int}>, context: array<string, mixed>, summary: array{status: string, message: string}}>, summary: array{status: string, message: string}}
     */
    public static function toArray(SeoValidationBatchReportDTO $batch): array
    {
        return $batch->toArray();
    }

    public static function toJson(SeoValidationBatchReportDTO $batch, int $flags = 0): string
    {
        $encoded = json_encode($batch, $flags === 0 ? self::DEFAULT_JSON_FLAGS : $flags);

        if ($encoded === false) {
            throw SeoInvalidArgumentException::invalidValue('batch', 'JSON encoding failed: ' . json_last_error_msg() . '.');
        }

        return $encoded;
    }

    /**
     * @return array{isValid: bool, isHealthy: bool, totalCount: int, validCount: int, invalidCount: int, healthyCount: int, unhealthyCount: int, errorCount: int, warningCount: int, infoCount: int, averageScore: float, minScore: int, maxScore: int, status: string, message: string}
     */
    public static function toSummaryArray(SeoValidationBatchReportDTO $batch): array
    {
        return [
            'isValid' => $batch->isValid,
            'isHealthy' => $batch->isHealthy,
            'totalCount' => $batch->totalCount,
            'validCount' => $batch->validCount,
            'invalidCount' => $batch->invalidCount,
            'healthyCount' => $batch->healthyCount,
            'unhealthyCount' => $batch->unhealthyCount,
            'errorCount' => $batch->errorCount,
            'warningCount' => $batch->warningCount,
            'infoCount' => $batch->infoCount,
            'averageScore' => $batch->averageScore,
            'minScore' => $batch->minScore,
            'maxScore' => $batch->maxScore,
            'status' => $batch->summary['status'],
            'message' => $batch->summary['message'],
        ];
    }

    public static function toMarkdown(SeoValidationBatchReportDTO $batch): string
    {
        $lines = [
            '# SEO Validation Batch Report',
            '',
            '## Summary',
            '- Status: ' . self::markdownScalar($batch->summary['status']),
            '- Message: ' . self::markdownScalar($batch->summary['message']),
            '- Valid: ' . self::boolText($batch->isValid),
            '- Healthy: ' . self::boolText($batch->isHealthy),
            '- Total: ' . (string) $batch->totalCount,
            '- Valid Count: ' . (string) $batch->validCount,
            '- Invalid Count: ' . (string) $batch->invalidCount,
            '- Healthy Count: ' . (string) $batch->healthyCount,
            '- Unhealthy Count: ' . (string) $batch->unhealthyCount,
            '- Errors: ' . (string) $batch->errorCount,
            '- Warnings: ' . (string) $batch->warningCount,
            '- Info: ' . (string) $batch->infoCount,
            '- Average Score: ' . self::markdownScalar($batch->averageScore),
            '- Min Score: ' . (string) $batch->minScore,
            '- Max Score: ' . (string) $batch->maxScore,
            '',
            '## Reports',
        ];

        foreach ($batch->reports as $index => $report) {
            self::appendReportSummary($lines, $index + 1, $report);
        }

        return implode("\n", $lines) . "\n";
    }

    /** @param list<string> $lines */
    private static function appendReportSummary(array &$lines, int $number, SeoValidationReportDTO $report): void
    {
        $lines[] = '';
        $lines[] = '### Report ' . (string) $number;
        $lines[] = '- Status: ' . self::markdownScalar($report->summary['status']);
        $lines[] = '- Message: ' . self::markdownScalar($report->summary['message']);
        $lines[] = '- Score: ' . (string) $report->score;
        $lines[] = '- Grade: ' . self::markdownScalar($report->grade);
        $lines[] = '- Valid: ' . self::boolText($report->isValid);
        $lines[] = '- Healthy: ' . self::boolText($report->isHealthy);
        $lines[] = '- Errors: ' . (string) $report->errorCount;
        $lines[] = '- Warnings: ' . (string) $report->warningCount;
        $lines[] = '- Info: ' . (string) $report->infoCount;

        if ($report->context !== []) {
            $lines[] = '- Context:';
            foreach ($report->context as $key => $value) {
                $lines[] = '  - ' . $key . ': ' . self::markdownScalar($value);
            }
        }
    }

    private static function boolText(bool $value): string
    {
        return $value ? 'true' : 'false';
    }

    private static function markdownScalar(mixed $value): string
    {
        if (is_bool($value)) {
            return self::boolText($value);
        }

        if ($value === null) {
            return 'null';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $encoded === false ? '[unserializable]' : $encoded;
    }
}
