<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\Service;

use Maatify\Seo\Exception\SeoNotFoundException;
use Maatify\Seo\Shared\Contract\SeoOverrideRepositoryInterface;
use Maatify\Seo\Shared\DTO\SeoOverride\SeoOverrideDTO;

final readonly class SeoOverrideQueryService
{
    public function __construct(private SeoOverrideRepositoryInterface $repository)
    {
    }

    public function getById(int $id): SeoOverrideDTO
    {
        $override = $this->repository->findById($id);
        if ($override === null) {
            throw SeoNotFoundException::withId($id);
        }
        return $override;
    }

    public function getActiveForEntity(string $entityType, string $entityId, int $languageId): SeoOverrideDTO
    {
        $override = $this->repository->findActiveForEntity($entityType, $entityId, $languageId);
        if ($override === null) {
            throw SeoNotFoundException::withCode($entityType . ':' . $entityId . ':' . (string) $languageId);
        }
        return $override;
    }

    /** @return list<SeoOverrideDTO> */
    public function listByEntity(string $entityType, string $entityId, ?int $languageId = null, bool $includeDeleted = false): array
    {
        return $this->repository->findByEntity($entityType, $entityId, $languageId, $includeDeleted);
    }
}
