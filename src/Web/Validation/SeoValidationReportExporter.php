<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation;

use Maatify\Seo\Exception\SeoInvalidArgumentException;
use Maatify\Seo\Web\Validation\DTO\SeoValidationReportDTO;

final class SeoValidationReportExporter
{
    private const DEFAULT_JSON_FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    /**
     * @return array{is_valid: bool, is_healthy: bool, score: int, grade: string, error_count: int, warning_count: int, info_count: int, issues: list<array{code: string, severity: string, message: string, field: string|null}>, errors: list<array{code: string, severity: string, message: string, field: string|null}>, warnings: list<array{code: string, severity: string, message: string, field: string|null}>, info: list<array{code: string, severity: string, message: string, field: string|null}>, deductions: list<array{code: string, severity: string, field: string|null, points: int}>, context: array<string, mixed>, summary: array{status: string, message: string}}
     */
    public static function toArray(SeoValidationReportDTO $report): array
    {
        return $report->toArray();
    }

    public static function toJson(SeoValidationReportDTO $report, int $flags = 0): string
    {
        $encoded = json_encode($report, $flags === 0 ? self::DEFAULT_JSON_FLAGS : $flags);

        if ($encoded === false) {
            throw SeoInvalidArgumentException::invalidValue('report', 'JSON encoding failed: ' . json_last_error_msg() . '.');
        }

        return $encoded;
    }

    /**
     * @return array{isValid: bool, isHealthy: bool, score: int, grade: string, errorCount: int, warningCount: int, infoCount: int, status: string, message: string}
     */
    public static function toSummaryArray(SeoValidationReportDTO $report): array
    {
        return [
            'isValid' => $report->isValid,
            'isHealthy' => $report->isHealthy,
            'score' => $report->score,
            'grade' => $report->grade,
            'errorCount' => $report->errorCount,
            'warningCount' => $report->warningCount,
            'infoCount' => $report->infoCount,
            'status' => $report->summary['status'],
            'message' => $report->summary['message'],
        ];
    }

    public static function toMarkdown(SeoValidationReportDTO $report): string
    {
        $lines = [
            '# SEO Validation Report',
            '',
            '## Summary',
            '- Status: ' . self::markdownScalar($report->summary['status']),
            '- Message: ' . self::markdownScalar($report->summary['message']),
            '- Score: ' . (string) $report->score,
            '- Grade: ' . self::markdownScalar($report->grade),
            '- Valid: ' . self::boolText($report->isValid),
            '- Healthy: ' . self::boolText($report->isHealthy),
            '- Errors: ' . (string) $report->errorCount,
            '- Warnings: ' . (string) $report->warningCount,
            '- Info: ' . (string) $report->infoCount,
            '',
            '## Issues',
        ];

        self::appendIssueGroup($lines, 'Errors', $report->errors);
        self::appendIssueGroup($lines, 'Warnings', $report->warnings);
        self::appendIssueGroup($lines, 'Info', $report->info);

        if ($report->deductions !== []) {
            $lines[] = '';
            $lines[] = '## Deductions';
            foreach ($report->deductions as $deduction) {
                $field = $deduction['field'] === null ? 'n/a' : $deduction['field'];
                $lines[] = '- ' . $deduction['code'] . ' (' . $deduction['severity'] . ', field: ' . $field . '): -' . (string) $deduction['points'] . ' points';
            }
        }

        if ($report->context !== []) {
            $lines[] = '';
            $lines[] = '## Context';
            foreach ($report->context as $key => $value) {
                $lines[] = '- ' . $key . ': ' . self::markdownScalar($value);
            }
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * @param list<string> $lines
     * @param list<array{code: string, severity: string, message: string, field: string|null}> $issues
     */
    private static function appendIssueGroup(array &$lines, string $heading, array $issues): void
    {
        $lines[] = '';
        $lines[] = '### ' . $heading;

        if ($issues === []) {
            $lines[] = '- None';
            return;
        }

        foreach ($issues as $issue) {
            $field = $issue['field'] === null ? 'n/a' : $issue['field'];
            $lines[] = '- ' . $issue['code'] . ' (field: ' . $field . '): ' . $issue['message'];
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
