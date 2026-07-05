<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\DTO;

final readonly class SeoValidationBatchReportDTO implements \JsonSerializable
{
    /**
     * @param list<SeoValidationReportDTO> $reports
     * @param array{status: string, message: string} $summary
     */
    public function __construct(
        public bool $isValid,
        public bool $isHealthy,
        public int $totalCount,
        public int $validCount,
        public int $invalidCount,
        public int $healthyCount,
        public int $unhealthyCount,
        public int $errorCount,
        public int $warningCount,
        public int $infoCount,
        public float $averageScore,
        public int $minScore,
        public int $maxScore,
        public array $reports,
        public array $summary,
    ) {
    }

    /**
     * @return array{is_valid: bool, is_healthy: bool, total_count: int, valid_count: int, invalid_count: int, healthy_count: int, unhealthy_count: int, error_count: int, warning_count: int, info_count: int, average_score: float, min_score: int, max_score: int, reports: list<array{is_valid: bool, is_healthy: bool, score: int, grade: string, error_count: int, warning_count: int, info_count: int, issues: list<array{code: string, severity: string, message: string, field: string|null}>, errors: list<array{code: string, severity: string, message: string, field: string|null}>, warnings: list<array{code: string, severity: string, message: string, field: string|null}>, info: list<array{code: string, severity: string, message: string, field: string|null}>, deductions: list<array{code: string, severity: string, field: string|null, points: int}>, context: array<string, mixed>, summary: array{status: string, message: string}}>, summary: array{status: string, message: string}}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return array{is_valid: bool, is_healthy: bool, total_count: int, valid_count: int, invalid_count: int, healthy_count: int, unhealthy_count: int, error_count: int, warning_count: int, info_count: int, average_score: float, min_score: int, max_score: int, reports: list<array{is_valid: bool, is_healthy: bool, score: int, grade: string, error_count: int, warning_count: int, info_count: int, issues: list<array{code: string, severity: string, message: string, field: string|null}>, errors: list<array{code: string, severity: string, message: string, field: string|null}>, warnings: list<array{code: string, severity: string, message: string, field: string|null}>, info: list<array{code: string, severity: string, message: string, field: string|null}>, deductions: list<array{code: string, severity: string, field: string|null, points: int}>, context: array<string, mixed>, summary: array{status: string, message: string}}>, summary: array{status: string, message: string}}
     */
    public function toArray(): array
    {
        return [
            'is_valid' => $this->isValid,
            'is_healthy' => $this->isHealthy,
            'total_count' => $this->totalCount,
            'valid_count' => $this->validCount,
            'invalid_count' => $this->invalidCount,
            'healthy_count' => $this->healthyCount,
            'unhealthy_count' => $this->unhealthyCount,
            'error_count' => $this->errorCount,
            'warning_count' => $this->warningCount,
            'info_count' => $this->infoCount,
            'average_score' => $this->averageScore,
            'min_score' => $this->minScore,
            'max_score' => $this->maxScore,
            'reports' => array_map(static fn (SeoValidationReportDTO $report): array => $report->toArray(), $this->reports),
            'summary' => $this->summary,
        ];
    }
}
