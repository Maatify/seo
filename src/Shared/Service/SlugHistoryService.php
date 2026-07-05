<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\Service;

use Maatify\Seo\Exception\SeoNotFoundException;
use Maatify\Seo\Shared\Command\CreateRedirectCommand;
use Maatify\Seo\Shared\Command\CreateSlugHistoryCommand;
use Maatify\Seo\Shared\Command\SlugHistory\RecordSlugChangeCommand;
use Maatify\Seo\Shared\DTO\SlugHistoryDTO;

final readonly class SlugHistoryService
{
    public function __construct(
        private SlugHistoryQueryService $slugHistoryQueryService,
        private SlugHistoryCommandService $slugHistoryCommandService,
        private ?RedirectCommandService $redirectCommandService = null,
    ) {
    }

    public function recordSlugChange(RecordSlugChangeCommand $command): int
    {
        $historyId = $this->slugHistoryCommandService->create(new CreateSlugHistoryCommand(
            $command->entityType,
            $command->entityId,
            $command->languageId,
            trim($command->oldSlug),
        ));

        if ($command->createRedirect && $this->redirectCommandService !== null) {
            $this->redirectCommandService->create(new CreateRedirectCommand(
                $command->entityType,
                $command->languageId,
                trim($command->oldSlug),
                $command->entityType,
                $command->entityId,
                301,
            ));
        }

        return $historyId;
    }

    public function findActiveBySlug(string $entityType, int $languageId, string $oldSlug): ?SlugHistoryDTO
    {
        try {
            return $this->slugHistoryQueryService->getActiveBySlug($entityType, $languageId, $oldSlug);
        } catch (SeoNotFoundException) {
            return null;
        }
    }

    /** @return list<SlugHistoryDTO> */
    public function findActiveForEntity(string $entityType, string $entityId, int $languageId): array
    {
        return $this->slugHistoryQueryService->getActiveForEntity($entityType, $entityId, $languageId);
    }
}
