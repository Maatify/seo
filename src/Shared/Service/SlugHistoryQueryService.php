<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\Service;

use Maatify\Seo\Exception\SeoNotFoundException;
use Maatify\Seo\Shared\Contract\SlugHistoryRepositoryInterface;
use Maatify\Seo\Shared\DTO\SlugHistoryDTO;

final readonly class SlugHistoryQueryService
{
    public function __construct(private SlugHistoryRepositoryInterface $repository)
    {
    }

    public function getById(int $id): SlugHistoryDTO
    {
        $slugHistory = $this->repository->findById($id);

        if ($slugHistory === null) {
            throw SeoNotFoundException::withId($id);
        }

        return $slugHistory;
    }

    public function getActiveBySlug(string $entityType, int $languageId, string $oldSlug): SlugHistoryDTO
    {
        $slugHistory = $this->repository->findActiveBySlug($entityType, $languageId, $oldSlug);

        if ($slugHistory === null) {
            throw SeoNotFoundException::withCode($entityType . ':' . (string) $languageId . ':' . $oldSlug);
        }

        return $slugHistory;
    }

    /** @return list<SlugHistoryDTO> */
    public function getActiveForEntity(string $entityType, string $entityId, int $languageId): array
    {
        return $this->repository->findActiveForEntity($entityType, $entityId, $languageId);
    }
}
