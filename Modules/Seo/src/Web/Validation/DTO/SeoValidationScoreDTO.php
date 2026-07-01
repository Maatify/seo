<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\DTO;

final readonly class SeoValidationScoreDTO implements \JsonSerializable
{
    /**
     * @param list<array{code: string, severity: string, field: string|null, points: int}> $deductions
     */
    public function __construct(
        public int $score,
        public string $grade,
        public int $errorCount,
        public int $warningCount,
        public int $infoCount,
        public array $deductions,
        public bool $isHealthy,
    ) {
    }

    /**
     * @return array{score: int, grade: string, error_count: int, warning_count: int, info_count: int, deductions: list<array{code: string, severity: string, field: string|null, points: int}>, is_healthy: bool}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return array{score: int, grade: string, error_count: int, warning_count: int, info_count: int, deductions: list<array{code: string, severity: string, field: string|null, points: int}>, is_healthy: bool}
     */
    public function toArray(): array
    {
        return [
            'score' => $this->score,
            'grade' => $this->grade,
            'error_count' => $this->errorCount,
            'warning_count' => $this->warningCount,
            'info_count' => $this->infoCount,
            'deductions' => $this->deductions,
            'is_healthy' => $this->isHealthy,
        ];
    }
}
