<?php

declare(strict_types=1);

namespace Maatify\Seo\Admin\Redirect\Service;

use Maatify\Seo\Admin\Redirect\DTO\AdminRedirectDTO;
use Maatify\Seo\Shared\DTO\RedirectDTO;
use Maatify\Seo\Shared\Service\RedirectQueryService;

final readonly class AdminRedirectQueryService
{
    public function __construct(private RedirectQueryService $queryService) {}
    public function getById(int $id): AdminRedirectDTO { return AdminRedirectDTO::fromShared($this->queryService->getById($id)); }
    public function getActiveByRequestedSlug(string $entityType, int $languageId, string $requestedSlug): AdminRedirectDTO { return AdminRedirectDTO::fromShared($this->queryService->getActiveByRequestedSlug($entityType, $languageId, $requestedSlug)); }
    /** @return list<AdminRedirectDTO> */
    public function listByEntity(string $entityType, ?int $languageId = null, bool $includeDeleted = false): array { return array_map(static fn (RedirectDTO $dto): AdminRedirectDTO => AdminRedirectDTO::fromShared($dto), $this->queryService->listByEntity($entityType, $languageId, $includeDeleted)); }
}
