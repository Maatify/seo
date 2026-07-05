<?php

declare(strict_types=1);

namespace Maatify\Seo\Admin\SeoOverride\Service;

use Maatify\Seo\Admin\SeoOverride\Command\CreateSeoOverrideCommand as AdminCreateSeoOverrideCommand;
use Maatify\Seo\Admin\SeoOverride\Command\UpdateSeoOverrideCommand as AdminUpdateSeoOverrideCommand;
use Maatify\Seo\Shared\Command\SeoOverride\CreateSeoOverrideCommand;
use Maatify\Seo\Shared\Command\SeoOverride\UpdateSeoOverrideCommand;
use Maatify\Seo\Shared\Service\SeoOverrideCommandService;

final readonly class AdminSeoOverrideCommandService
{
    public function __construct(private SeoOverrideCommandService $commandService)
    {
    }

    public function create(AdminCreateSeoOverrideCommand $command): int
    {
        return $this->commandService->create(new CreateSeoOverrideCommand($command->entityType, $command->entityId, $command->languageId, $command->metaTitle, $command->metaDescription));
    }

    public function update(AdminUpdateSeoOverrideCommand $command): void
    {
        $this->commandService->update(new UpdateSeoOverrideCommand($command->id, $command->metaTitle, $command->metaDescription));
    }

    public function softDelete(int $id): void
    {
        $this->commandService->softDelete($id);
    }

    public function hardDelete(int $id): void
    {
        $this->commandService->hardDelete($id);
    }
}
