<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\DTO;

final readonly class SeoValidationReportDTO implements \JsonSerializable
{
    /**
     * @param list<array{code: string, severity: string, message: string, field: string|null}> $issues
     * @param list<array{code: string, severity: string, message: string, field: string|null}> $errors
     * @param list<array{code: string, severity: string, message: string, field: string|null}> $warnings
     * @param list<array{code: string, severity: string, message: string, field: string|null}> $info
     * @param list<array{code: string, severity: string, field: string|null, points: int}> $deductions
     * @param array<string, mixed> $context
     * @param array{status: string, message: string} $summary
     */
    public function __construct(
        public bool $isValid,
        public bool $isHealthy,
        public int $score,
        public string $grade,
        public int $errorCount,
        public int $warningCount,
        public int $infoCount,
        public array $issues,
        public array $errors,
        public array $warnings,
        public array $info,
        public array $deductions,
        public array $context,
        public array $summary,
    ) {
    }

    /**
     * @return array{is_valid: bool, is_healthy: bool, score: int, grade: string, error_count: int, warning_count: int, info_count: int, issues: list<array{code: string, severity: string, message: string, field: string|null}>, errors: list<array{code: string, severity: string, message: string, field: string|null}>, warnings: list<array{code: string, severity: string, message: string, field: string|null}>, info: list<array{code: string, severity: string, message: string, field: string|null}>, deductions: list<array{code: string, severity: string, field: string|null, points: int}>, context: array<string, mixed>, summary: array{status: string, message: string}}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return array{is_valid: bool, is_healthy: bool, score: int, grade: string, error_count: int, warning_count: int, info_count: int, issues: list<array{code: string, severity: string, message: string, field: string|null}>, errors: list<array{code: string, severity: string, message: string, field: string|null}>, warnings: list<array{code: string, severity: string, message: string, field: string|null}>, info: list<array{code: string, severity: string, message: string, field: string|null}>, deductions: list<array{code: string, severity: string, field: string|null, points: int}>, context: array<string, mixed>, summary: array{status: string, message: string}}
     */
    public function toArray(): array
    {
        return [
            'is_valid' => $this->isValid,
            'is_healthy' => $this->isHealthy,
            'score' => $this->score,
            'grade' => $this->grade,
            'error_count' => $this->errorCount,
            'warning_count' => $this->warningCount,
            'info_count' => $this->infoCount,
            'issues' => $this->issues,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'info' => $this->info,
            'deductions' => $this->deductions,
            'context' => $this->context,
            'summary' => $this->summary,
        ];
    }
}
