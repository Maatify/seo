<?php

declare(strict_types=1);

namespace Maatify\Seo\Admin\Export;

use Maatify\Seo\Admin\DTO\SeoMetadataExportDTO;
use Maatify\Seo\Shared\DTO\RedirectDTO;
use Maatify\Seo\Shared\DTO\SeoOverride\SeoOverrideDTO;
use Maatify\Seo\Shared\DTO\SlugHistoryDTO;

final class SeoMetadataExporter
{
    /**
     * @param list<SeoOverrideDTO|array<string, mixed>> $seoOverrides
     * @param list<RedirectDTO|array<string, mixed>> $redirects
     * @param list<SlugHistoryDTO|array<string, mixed>> $slugHistory
     */
    public function export(array $seoOverrides = [], array $redirects = [], array $slugHistory = []): SeoMetadataExportDTO
    {
        return new SeoMetadataExportDTO(
            SeoMetadataExportDTO::SCHEMA_VERSION,
            gmdate('c'),
            $this->normalizeList($seoOverrides),
            $this->normalizeList($redirects),
            $this->normalizeList($slugHistory),
        );
    }

    /** @param list<SeoOverrideDTO|array<string, mixed>> $seoOverrides */
    public function exportSeoOverrides(array $seoOverrides): SeoMetadataExportDTO
    {
        return $this->export($seoOverrides);
    }

    /** @param list<RedirectDTO|array<string, mixed>> $redirects */
    public function exportRedirects(array $redirects): SeoMetadataExportDTO
    {
        return $this->export([], $redirects);
    }

    /** @param list<SlugHistoryDTO|array<string, mixed>> $slugHistory */
    public function exportSlugHistory(array $slugHistory): SeoMetadataExportDTO
    {
        return $this->export([], [], $slugHistory);
    }

    /** @param SeoMetadataExportDTO|array<string, mixed> $export */
    public function toJson(SeoMetadataExportDTO|array $export): string
    {
        $json = json_encode($export instanceof SeoMetadataExportDTO ? $export->toArray() : $export, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        return $json;
    }

    /**
     * @param list<object|array<string, mixed>> $items
     * @return list<array<string, mixed>>
     */
    private function normalizeList(array $items): array
    {
        $normalized = [];
        foreach ($items as $item) {
            if ($item instanceof \JsonSerializable) {
                $value = $item->jsonSerialize();
                $normalized[] = is_array($value) ? $value : [];
                continue;
            }
            $normalized[] = $item;
        }
        return $normalized;
    }
}
