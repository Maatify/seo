<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation;

use Maatify\Seo\Exception\SeoInvalidArgumentException;
use Maatify\Seo\Web\Validation\DTO\SeoValidationBatchReportDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationReportDTO;

final class SeoValidationBatchReportBuilder
{
    /**
     * @param array<int, array{meta?: array<string, mixed>|object, context?: array<string, mixed>}> $items
     * @param array<string, mixed> $validationOptions
     * @param array<string, mixed> $scoreOptions
     * @param array<string, mixed> $sharedContext
     */
    public static function build(
        array $items,
        array $validationOptions = [],
        array $scoreOptions = [],
        array $sharedContext = [],
    ): SeoValidationBatchReportDTO {
        self::assertItemsList($items);

        $reports = [];
        foreach ($items as $index => $item) {
            self::assertValidItem($item, $index);

            /** @var array{meta: array<string, mixed>|object, context?: array<string, mixed>} $item */
            $context = array_key_exists('context', $item)
                ? array_merge($sharedContext, $item['context'])
                : $sharedContext;

            $reports[] = SeoValidationReportBuilder::build($item['meta'], $validationOptions, $scoreOptions, $context);
        }

        return self::fromReports($reports);
    }

    /**
     * @param array<int, mixed> $items
     */
    private static function assertItemsList(array $items): void
    {
        if ($items === []) {
            throw SeoInvalidArgumentException::invalidValue('items', 'Expected a non-empty list of batch report items.');
        }

        if (!array_is_list($items)) {
            throw SeoInvalidArgumentException::invalidValue('items', 'Expected a list of batch report items.');
        }
    }

    private static function assertValidItem(mixed $item, int $index): void
    {
        if (!is_array($item) || array_is_list($item)) {
            throw SeoInvalidArgumentException::invalidValue("items.{$index}", 'Expected an associative array batch report item.');
        }

        if (!array_key_exists('meta', $item)) {
            throw SeoInvalidArgumentException::invalidValue("items.{$index}.meta", 'Expected meta to be provided.');
        }

        if (!is_array($item['meta']) && !is_object($item['meta'])) {
            throw SeoInvalidArgumentException::invalidValue("items.{$index}.meta", 'Expected meta to be an array or object.');
        }

        if (array_key_exists('context', $item) && !is_array($item['context'])) {
            throw SeoInvalidArgumentException::invalidValue("items.{$index}.context", 'Expected context to be an array.');
        }
    }

    /**
     * @param list<SeoValidationReportDTO> $reports
     */
    private static function fromReports(array $reports): SeoValidationBatchReportDTO
    {
        $totalCount = count($reports);
        $validCount = 0;
        $healthyCount = 0;
        $errorCount = 0;
        $warningCount = 0;
        $infoCount = 0;
        /** @var non-empty-list<int> $scores */
        $scores = [];

        foreach ($reports as $report) {
            if ($report->isValid) {
                ++$validCount;
            }

            if ($report->isHealthy) {
                ++$healthyCount;
            }

            $errorCount += $report->errorCount;
            $warningCount += $report->warningCount;
            $infoCount += $report->infoCount;
            $scores[] = $report->score;
        }

        $invalidCount = $totalCount - $validCount;
        $unhealthyCount = $totalCount - $healthyCount;
        $isValid = $invalidCount === 0;
        $isHealthy = $unhealthyCount === 0 && $warningCount === 0;

        return new SeoValidationBatchReportDTO(
            isValid: $isValid,
            isHealthy: $isHealthy,
            totalCount: $totalCount,
            validCount: $validCount,
            invalidCount: $invalidCount,
            healthyCount: $healthyCount,
            unhealthyCount: $unhealthyCount,
            errorCount: $errorCount,
            warningCount: $warningCount,
            infoCount: $infoCount,
            averageScore: array_sum($scores) / $totalCount,
            minScore: min($scores),
            maxScore: max($scores),
            reports: $reports,
            summary: self::summary($isValid, $isHealthy),
        );
    }

    /** @return array{status: string, message: string} */
    private static function summary(bool $isValid, bool $isHealthy): array
    {
        if (!$isValid) {
            return ['status' => 'fail', 'message' => 'SEO batch validation failed.'];
        }

        if (!$isHealthy) {
            return ['status' => 'warning', 'message' => 'SEO batch validation completed with warnings.'];
        }

        return ['status' => 'pass', 'message' => 'SEO batch validation passed.'];
    }
}
