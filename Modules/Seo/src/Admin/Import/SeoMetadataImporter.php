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
            $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            return new SeoMetadataImportResultDTO(failed: 1, errors: ['Invalid JSON: ' . $exception->getMessage()], dryRun: $dryRun);
        }
        return is_array($payload) ? $this->importArray($payload, $dryRun) : new SeoMetadataImportResultDTO(failed: 1, errors: ['Import payload must decode to an array.'], dryRun: $dryRun);
    }

    /** @param array<string, mixed> $payload */
    public function importArray(array $payload, bool $dryRun = false): SeoMetadataImportResultDTO
    {
        $errors = $this->validate($payload);
        if ($errors !== []) {
            return new SeoMetadataImportResultDTO(failed: count($errors), errors: $errors, dryRun: $dryRun);
        }

        /** @var array{seo_overrides:list<array<string, mixed>>, redirects:list<array<string, mixed>>, slug_history:list<array<string, mixed>>} $data */
        $data = $payload['data'];
        $created = 0; $skipped = 0; $failed = 0; $importErrors = [];

        foreach ($data['seo_overrides'] as $index => $row) {
            if ($dryRun) { $created++; continue; }
            if ($this->seoOverrideRepository === null) { $skipped++; continue; }
            try {
                $this->seoOverrideRepository->create(new CreateSeoOverrideCommand((string)$row['entity_type'], (string)$row['entity_id'], (int)$row['language_id'], isset($row['meta_title']) && is_string($row['meta_title']) ? $row['meta_title'] : null, isset($row['meta_description']) && is_string($row['meta_description']) ? $row['meta_description'] : null));
                $created++;
            } catch (\Throwable $e) { $failed++; $importErrors[] = 'seo_overrides[' . $index . ']: ' . $e->getMessage(); }
        }
        foreach ($data['redirects'] as $index => $row) {
            if ($dryRun) { $created++; continue; }
            if ($this->redirectRepository === null) { $skipped++; continue; }
            try {
                $this->redirectRepository->create(new CreateRedirectCommand((string)$row['entity_type'], (int)$row['language_id'], (string)$row['requested_slug'], isset($row['target_entity_type']) && is_string($row['target_entity_type']) ? $row['target_entity_type'] : null, isset($row['target_entity_id']) && is_string($row['target_entity_id']) ? $row['target_entity_id'] : null, (int)$row['http_status']));
                $created++;
            } catch (\Throwable $e) { $failed++; $importErrors[] = 'redirects[' . $index . ']: ' . $e->getMessage(); }
        }
        foreach ($data['slug_history'] as $index => $row) {
            if ($dryRun) { $created++; continue; }
            if ($this->slugHistoryRepository === null) { $skipped++; continue; }
            try {
                $this->slugHistoryRepository->create(new CreateSlugHistoryCommand((string)$row['entity_type'], (string)$row['entity_id'], (int)$row['language_id'], (string)$row['old_slug']));
                $created++;
            } catch (\Throwable $e) { $failed++; $importErrors[] = 'slug_history[' . $index . ']: ' . $e->getMessage(); }
        }

        return new SeoMetadataImportResultDTO($created, 0, $skipped, $failed, $importErrors, $dryRun);
    }

    /** @param array<string, mixed> $payload @return list<string> */
    public function validate(array $payload): array
    {
        $errors = [];
        if (($payload['schema_version'] ?? null) !== SeoMetadataExportDTO::SCHEMA_VERSION) $errors[] = 'Unsupported or missing schema_version.';
        if (!isset($payload['data']) || !is_array($payload['data'])) return [...$errors, 'Missing data object.'];
        foreach (['seo_overrides', 'redirects', 'slug_history'] as $key) {
            if (!isset($payload['data'][$key]) || !is_array($payload['data'][$key])) {
                $errors[] = 'Missing data.' . $key . ' list.';
            }
        }
        if ($errors !== []) {
            return $errors;
        }

        /** @var array{seo_overrides:list<mixed>, redirects:list<mixed>, slug_history:list<mixed>} $data */
        $data = $payload['data'];
        foreach ($data['seo_overrides'] as $index => $row) {
            if (!is_array($row) || !$this->hasString($row, 'entity_type') || !$this->hasString($row, 'entity_id') || !$this->hasPositiveInt($row, 'language_id')) {
                $errors[] = 'Invalid seo_overrides[' . $index . '] row.';
            }
        }
        foreach ($data['redirects'] as $index => $row) {
            if (!is_array($row) || !$this->hasString($row, 'entity_type') || !$this->hasPositiveInt($row, 'language_id') || !$this->hasString($row, 'requested_slug') || !$this->hasPositiveInt($row, 'http_status')) {
                $errors[] = 'Invalid redirects[' . $index . '] row.';
            }
        }
        foreach ($data['slug_history'] as $index => $row) {
            if (!is_array($row) || !$this->hasString($row, 'entity_type') || !$this->hasString($row, 'entity_id') || !$this->hasPositiveInt($row, 'language_id') || !$this->hasString($row, 'old_slug')) {
                $errors[] = 'Invalid slug_history[' . $index . '] row.';
            }
        }

        return $errors;
    }

    /** @param array<mixed> $row */
    private function hasString(array $row, string $key): bool
    {
        return isset($row[$key]) && is_string($row[$key]) && trim($row[$key]) !== '';
    }

    /** @param array<mixed> $row */
    private function hasPositiveInt(array $row, string $key): bool
    {
        return isset($row[$key]) && is_numeric($row[$key]) && (int)$row[$key] > 0;
    }
}
