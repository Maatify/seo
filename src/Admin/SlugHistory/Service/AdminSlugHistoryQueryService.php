<?php

declare(strict_types=1);

namespace Maatify\Seo\Admin\SlugHistory\Service;

use Maatify\Seo\Admin\SlugHistory\DTO\AdminSlugHistoryDTO;
use Maatify\Seo\Shared\DTO\SlugHistoryDTO;
use Maatify\Seo\Shared\Service\SlugHistoryQueryService;

final readonly class AdminSlugHistoryQueryService
{
    public function __construct(private SlugHistoryQueryService $queryService) {}
    public function getById(int $id): AdminSlugHistoryDTO { return AdminSlugHistoryDTO::fromShared($this->queryService->getById($id)); }
    public function getActiveBySlug(string $entityType, int $languageId, string $oldSlug): AdminSlugHistoryDTO { return AdminSlugHistoryDTO::fromShared($this->queryService->getActiveBySlug($entityType, $languageId, $oldSlug)); }
    /** @return list<AdminSlugHistoryDTO> */
    public function listActiveForEntity(string $entityType, string $entityId, int $languageId): array { return array_map(static fn (SlugHistoryDTO $dto): AdminSlugHistoryDTO => AdminSlugHistoryDTO::fromShared($dto), $this->queryService->getActiveForEntity($entityType, $entityId, $languageId)); }
}
