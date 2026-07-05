<?php

declare(strict_types=1);

namespace Maatify\Seo\Admin\SlugHistory\Service;

use Maatify\Seo\Admin\SlugHistory\Command\RecordAdminSlugHistoryCommand;
use Maatify\Seo\Shared\Command\CreateSlugHistoryCommand;
use Maatify\Seo\Shared\Service\SlugHistoryCommandService;

final readonly class AdminSlugHistoryCommandService
{
    public function __construct(private SlugHistoryCommandService $commandService) {}
    public function record(RecordAdminSlugHistoryCommand $command): int { return $this->commandService->create(new CreateSlugHistoryCommand($command->entityType, $command->entityId, $command->languageId, $command->oldSlug)); }
    public function softDelete(int $id): void { $this->commandService->softDelete($id); }
    public function hardDelete(int $id): void { $this->commandService->hardDelete($id); }
}
