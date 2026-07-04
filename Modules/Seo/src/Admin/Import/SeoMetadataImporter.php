<?php

declare(strict_types=1);

namespace Maatify\Seo\Admin\Import;

use Maatify\Seo\Admin\DTO\SeoMetadataExportDTO;
use Maatify\Seo\Admin\DTO\SeoMetadataImportResultDTO;
use Maatify\Seo\Shared\Command\CreateRedirectCommand;
use Maatify\Seo\Shared\Command\CreateSlugHistoryCommand;
use Maatify\Seo\Shared\Command\SeoOverride\CreateSeoOverrideCommand;
use Maatify\Seo\Shared\Contract\RedirectRepositoryInterface;
use Maatify\Seo\Shared\Contract\SeoOverrideRepositoryInterface;
use Maatify\Seo\Shared\Contract\SlugHistoryRepositoryInterface;

final class SeoMetadataImporter
{
    public function __construct(
        private ?SeoOverrideRepositoryInterface $seoOverrideRepository = null,
        private ?RedirectRepositoryInterface $redirectRepository = null,
        private ?SlugHistoryRepositoryInterface $slugHistoryRepository = null,
    ) {
    }

    public function importJson(string $json, bool $dryRun = false): SeoMetadataImportResultDTO
    {
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            return new SeoMetadataImportResultDTO(failed: 1, errors: ['Invalid JSON: ' . $exception->getMessage()], dryRun: $dryRun);
        }

        if (!is_array($decoded)) {
            return new SeoMetadataImportResultDTO(failed: 1, errors: ['Import payload must decode to an object.'], dryRun: $dryRun);
        }

        return $this->importArray($this->normalizeStringKeyArray($decoded), $dryRun);
    }

    /** @param array<string, mixed> $payload */
    public function importArray(array $payload, bool $dryRun = false): SeoMetadataImportResultDTO
    {
        $errors = $this->validate($payload);
        if ($errors !== []) {
            return new SeoMetadataImportResultDTO(failed: count($errors), errors: $errors, dryRun: $dryRun);
        }

        $data = $this->normalizeValidatedPayload($payload);
        $created = 0;
        $skipped = 0;
        $failed = 0;
        /** @var list<string> $importErrors */
        $importErrors = [];

        foreach ($data['seo_overrides'] as $index => $row) {
            if ($dryRun) {
                $created++;
                continue;
            }
            if ($this->seoOverrideRepository === null) {
                $skipped++;
                continue;
            }

            try {
                $command = new CreateSeoOverrideCommand(
                    $row['entity_type'],
                    $row['entity_id'],
                    $row['language_id'],
                    $row['meta_title'],
                    $row['meta_description']
                );
                $this->seoOverrideRepository->create($command);
                $created++;
            } catch (\Throwable $exception) {
                $failed++;
                $importErrors[] = 'seo_overrides[' . $index . ']: ' . $exception->getMessage();
            }
        }

        foreach ($data['redirects'] as $index => $row) {
            if ($dryRun) {
                $created++;
                continue;
            }
            if ($this->redirectRepository === null) {
                $skipped++;
                continue;
            }

            try {
                $command = new CreateRedirectCommand(
                    $row['entity_type'],
                    $row['language_id'],
                    $row['requested_slug'],
                    $row['target_entity_type'],
                    $row['target_entity_id'],
                    $row['http_status']
                );
                $this->redirectRepository->create($command);
                $created++;
            } catch (\Throwable $exception) {
                $failed++;
                $importErrors[] = 'redirects[' . $index . ']: ' . $exception->getMessage();
            }
        }

        foreach ($data['slug_history'] as $index => $row) {
            if ($dryRun) {
                $created++;
                continue;
            }
            if ($this->slugHistoryRepository === null) {
                $skipped++;
                continue;
            }

            try {
                $command = new CreateSlugHistoryCommand(
                    $row['entity_type'],
                    $row['entity_id'],
                    $row['language_id'],
                    $row['old_slug']
                );
                $this->slugHistoryRepository->create($command);
                $created++;
            } catch (\Throwable $exception) {
                $failed++;
                $importErrors[] = 'slug_history[' . $index . ']: ' . $exception->getMessage();
            }
        }

        // Current repository contracts expose create-only import operations; updates are intentionally not reported.
        return new SeoMetadataImportResultDTO($created, 0, $skipped, $failed, $importErrors, $dryRun);
    }

    /**
     * @param array<string, mixed> $payload
     * @return list<string>
     */
    public function validate(array $payload): array
    {
        /** @var list<string> $errors */
        $errors = [];

        if (($payload['schema_version'] ?? null) !== SeoMetadataExportDTO::SCHEMA_VERSION) {
            $errors[] = 'Unsupported or missing schema_version.';
        }

        $data = $payload['data'] ?? null;
        if (!is_array($data)) {
            $errors[] = 'Missing data object.';
            return $errors;
        }

        foreach (['seo_overrides', 'redirects', 'slug_history'] as $section) {
            $rows = $data[$section] ?? null;
            if (!is_array($rows) || !array_is_list($rows)) {
                $errors[] = 'Missing data.' . $section . ' list.';
            }
        }

        if ($errors !== []) {
            return $errors;
        }

        /** @var array{seo_overrides:list<mixed>, redirects:list<mixed>, slug_history:list<mixed>} $typedData */
        $typedData = $data;
        foreach ($typedData['seo_overrides'] as $index => $row) {
            if (!is_array($row)) {
                $errors[] = 'Invalid seo_overrides[' . $index . '] row.';
                continue;
            }
            $errors = array_merge($errors, $this->validateSeoOverrideRow($this->normalizeStringKeyArray($row), $index));
        }

        foreach ($typedData['redirects'] as $index => $row) {
            if (!is_array($row)) {
                $errors[] = 'Invalid redirects[' . $index . '] row.';
                continue;
            }
            $errors = array_merge($errors, $this->validateRedirectRow($this->normalizeStringKeyArray($row), $index));
        }

        foreach ($typedData['slug_history'] as $index => $row) {
            if (!is_array($row)) {
                $errors[] = 'Invalid slug_history[' . $index . '] row.';
                continue;
            }
            $errors = array_merge($errors, $this->validateSlugHistoryRow($this->normalizeStringKeyArray($row), $index));
        }

        return array_values($errors);
    }

    /**
     * @param array<array-key, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeStringKeyArray(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{seo_overrides:list<array{entity_type:string, entity_id:string, language_id:int, meta_title:?string, meta_description:?string}>, redirects:list<array{entity_type:string, language_id:int, requested_slug:string, target_entity_type:?string, target_entity_id:?string, http_status:int}>, slug_history:list<array{entity_type:string, entity_id:string, language_id:int, old_slug:string}>}
     */
    private function normalizeValidatedPayload(array $payload): array
    {
        $data = $payload['data'];
        if (!is_array($data)) {
            throw new \LogicException('Payload data must be validated before normalization.');
        }

        $seoOverrides = [];
        $redirects = [];
        $slugHistory = [];

        /** @var list<array<string, mixed>> $overrideRows */
        $overrideRows = $data['seo_overrides'];
        foreach ($overrideRows as $row) {
            $seoOverrides[] = [
                'entity_type' => $this->requireStringField($row, 'entity_type'),
                'entity_id' => $this->requireStringField($row, 'entity_id'),
                'language_id' => $this->requirePositiveIntField($row, 'language_id'),
                'meta_title' => $this->optionalStringField($row, 'meta_title'),
                'meta_description' => $this->optionalStringField($row, 'meta_description'),
            ];
        }

        /** @var list<array<string, mixed>> $redirectRows */
        $redirectRows = $data['redirects'];
        foreach ($redirectRows as $row) {
            $redirects[] = [
                'entity_type' => $this->requireStringField($row, 'entity_type'),
                'language_id' => $this->requirePositiveIntField($row, 'language_id'),
                'requested_slug' => $this->requireStringField($row, 'requested_slug'),
                'target_entity_type' => $this->optionalStringField($row, 'target_entity_type'),
                'target_entity_id' => $this->optionalStringField($row, 'target_entity_id'),
                'http_status' => $this->requirePositiveIntField($row, 'http_status'),
            ];
        }

        /** @var list<array<string, mixed>> $slugRows */
        $slugRows = $data['slug_history'];
        foreach ($slugRows as $row) {
            $slugHistory[] = [
                'entity_type' => $this->requireStringField($row, 'entity_type'),
                'entity_id' => $this->requireStringField($row, 'entity_id'),
                'language_id' => $this->requirePositiveIntField($row, 'language_id'),
                'old_slug' => $this->requireStringField($row, 'old_slug'),
            ];
        }

        return [
            'seo_overrides' => $seoOverrides,
            'redirects' => $redirects,
            'slug_history' => $slugHistory,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return list<string>
     */
    private function validateSeoOverrideRow(array $row, int $index): array
    {
        $errors = [];
        $this->appendRequiredStringError($errors, $row, 'entity_type', 'seo_overrides', $index);
        $this->appendRequiredStringError($errors, $row, 'entity_id', 'seo_overrides', $index);
        $this->appendRequiredPositiveIntError($errors, $row, 'language_id', 'seo_overrides', $index);
        $this->appendOptionalStringError($errors, $row, 'meta_title', 'seo_overrides', $index);
        $this->appendOptionalStringError($errors, $row, 'meta_description', 'seo_overrides', $index);

        return $errors;
    }

    /**
     * @param array<string, mixed> $row
     * @return list<string>
     */
    private function validateRedirectRow(array $row, int $index): array
    {
        $errors = [];
        $this->appendRequiredStringError($errors, $row, 'entity_type', 'redirects', $index);
        $this->appendRequiredPositiveIntError($errors, $row, 'language_id', 'redirects', $index);
        $this->appendRequiredStringError($errors, $row, 'requested_slug', 'redirects', $index);
        $this->appendOptionalStringError($errors, $row, 'target_entity_type', 'redirects', $index);
        $this->appendOptionalStringError($errors, $row, 'target_entity_id', 'redirects', $index);
        $this->appendRequiredPositiveIntError($errors, $row, 'http_status', 'redirects', $index);

        return $errors;
    }

    /**
     * @param array<string, mixed> $row
     * @return list<string>
     */
    private function validateSlugHistoryRow(array $row, int $index): array
    {
        $errors = [];
        $this->appendRequiredStringError($errors, $row, 'entity_type', 'slug_history', $index);
        $this->appendRequiredStringError($errors, $row, 'entity_id', 'slug_history', $index);
        $this->appendRequiredPositiveIntError($errors, $row, 'language_id', 'slug_history', $index);
        $this->appendRequiredStringError($errors, $row, 'old_slug', 'slug_history', $index);

        return $errors;
    }

    /**
     * @param list<string> $errors
     * @param array<string, mixed> $row
     */
    private function appendRequiredStringError(array &$errors, array $row, string $field, string $section, int $index): void
    {
        if (!$this->hasRequiredStringField($row, $field)) {
            $errors[] = $section . '[' . $index . '].' . $field . ' must be a non-empty string.';
        }
    }

    /**
     * @param list<string> $errors
     * @param array<string, mixed> $row
     */
    private function appendOptionalStringError(array &$errors, array $row, string $field, string $section, int $index): void
    {
        if (array_key_exists($field, $row) && $row[$field] !== null && !is_string($row[$field])) {
            $errors[] = $section . '[' . $index . '].' . $field . ' must be a string or null.';
        }
    }

    /**
     * @param list<string> $errors
     * @param array<string, mixed> $row
     */
    private function appendRequiredPositiveIntError(array &$errors, array $row, string $field, string $section, int $index): void
    {
        if (!$this->hasPositiveIntField($row, $field)) {
            $errors[] = $section . '[' . $index . '].' . $field . ' must be a positive integer.';
        }
    }

    /** @param array<string, mixed> $row */
    private function hasRequiredStringField(array $row, string $field): bool
    {
        return isset($row[$field]) && is_string($row[$field]) && trim($row[$field]) !== '';
    }

    /** @param array<string, mixed> $row */
    private function hasPositiveIntField(array $row, string $field): bool
    {
        return isset($row[$field]) && is_int($row[$field]) && $row[$field] > 0;
    }

    /** @param array<string, mixed> $row */
    private function requireStringField(array $row, string $field): string
    {
        if (!$this->hasRequiredStringField($row, $field)) {
            throw new \LogicException('Required string field [' . $field . '] was not validated.');
        }

        return $row[$field];
    }

    /** @param array<string, mixed> $row */
    private function requirePositiveIntField(array $row, string $field): int
    {
        if (!$this->hasPositiveIntField($row, $field)) {
            throw new \LogicException('Required positive integer field [' . $field . '] was not validated.');
        }

        return $row[$field];
    }

    /** @param array<string, mixed> $row */
    private function optionalStringField(array $row, string $field): ?string
    {
        $value = $row[$field] ?? null;
        if ($value === null) {
            return null;
        }
        if (!is_string($value)) {
            throw new \LogicException('Optional string field [' . $field . '] was not validated.');
        }

        return $value;
    }
}
