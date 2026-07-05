<?php

declare(strict_types=1);

namespace Maatify\Seo\Admin\SeoOverride\Service;

use Maatify\Seo\Admin\SeoOverride\DTO\AdminSeoOverrideDTO;
use Maatify\Seo\Shared\DTO\SeoOverride\SeoOverrideDTO;
use Maatify\Seo\Shared\Service\SeoOverrideQueryService;

final readonly class AdminSeoOverrideQueryService
{
    public function __construct(private SeoOverrideQueryService $queryService)
    {
    }

    public function getById(int $id): AdminSeoOverrideDTO
    {
        return AdminSeoOverrideDTO::fromShared($this->queryService->getById($id));
    }

    public function getActiveForEntity(string $entityType, string $entityId, int $languageId): AdminSeoOverrideDTO
    {
        return AdminSeoOverrideDTO::fromShared($this->queryService->getActiveForEntity($entityType, $entityId, $languageId));
    }

    /** @return list<AdminSeoOverrideDTO> */
    public function listByEntity(string $entityType, string $entityId, ?int $languageId = null, bool $includeDeleted = false): array
    {
        return array_map(static fn (SeoOverrideDTO $dto): AdminSeoOverrideDTO => AdminSeoOverrideDTO::fromShared($dto), $this->queryService->listByEntity($entityType, $entityId, $languageId, $includeDeleted));
    }
}
