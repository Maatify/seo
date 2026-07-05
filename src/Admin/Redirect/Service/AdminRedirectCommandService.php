<?php

declare(strict_types=1);

namespace Maatify\Seo\Admin\Redirect\Service;

use Maatify\Seo\Admin\Redirect\Command\CreateAdminRedirectCommand;
use Maatify\Seo\Admin\Redirect\Command\UpdateAdminRedirectCommand;
use Maatify\Seo\Shared\Command\CreateRedirectCommand;
use Maatify\Seo\Shared\Command\UpdateRedirectCommand;
use Maatify\Seo\Shared\Service\RedirectCommandService;

final readonly class AdminRedirectCommandService
{
    public function __construct(private RedirectCommandService $commandService) {}
    public function createManualRedirect(CreateAdminRedirectCommand $command): int { return $this->commandService->create(new CreateRedirectCommand($command->entityType, $command->languageId, $command->requestedSlug, $command->targetEntityType, $command->targetEntityId, 301)); }
    public function createGoneRedirect(CreateAdminRedirectCommand $command): int { return $this->commandService->create(new CreateRedirectCommand($command->entityType, $command->languageId, $command->requestedSlug, null, null, 410)); }
    public function create(CreateAdminRedirectCommand $command): int { return $this->commandService->create(new CreateRedirectCommand($command->entityType, $command->languageId, $command->requestedSlug, $command->targetEntityType, $command->targetEntityId, $command->httpStatus)); }
    public function update(UpdateAdminRedirectCommand $command): void { $this->commandService->update(new UpdateRedirectCommand($command->id, $command->targetEntityType, $command->targetEntityId, $command->httpStatus)); }
    public function softDelete(int $id): void { $this->commandService->softDelete($id); }
    public function hardDelete(int $id): void { $this->commandService->hardDelete($id); }
}
