<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\Service;

use Maatify\Seo\Exception\SeoConflictException;
use Maatify\Seo\Exception\SeoNotFoundException;
use Maatify\Seo\Shared\Command\CreateRedirectCommand;
use Maatify\Seo\Shared\Command\Redirect\ResolveRedirectCommand;
use Maatify\Seo\Shared\Contract\HostUrlGeneratorInterface;
use Maatify\Seo\Shared\DTO\Redirect\RedirectDecisionDTO;
use Maatify\Seo\Shared\DTO\RedirectDTO;

final readonly class RedirectManagerService
{
    public function __construct(
        private RedirectQueryService $redirectQueryService,
        private ?RedirectCommandService $redirectCommandService = null,
        private ?HostUrlGeneratorInterface $urlGenerator = null,
    ) {
    }

    public function resolve(ResolveRedirectCommand $command): RedirectDecisionDTO
    {
        try {
            $redirect = $this->redirectQueryService->getActiveByRequestedSlug(
                $command->entityType,
                $command->languageId,
                $this->normalizeRequestedSlug($command),
            );
        } catch (SeoNotFoundException) {
            return RedirectDecisionDTO::noRedirect();
        }

        if ($redirect->httpStatus === 410) {
            return RedirectDecisionDTO::gone($redirect);
        }

        return RedirectDecisionDTO::permanent($redirect, $this->generateTargetUrl($redirect));
    }

    public function recordPermanentRedirect(
        string $entityType,
        int $languageId,
        string $requestedSlug,
        string $targetEntityType,
        string $targetEntityId,
    ): int {
        if ($this->redirectCommandService === null) {
            throw SeoConflictException::dueToReason('RedirectCommandService is required to record redirects.');
        }

        return $this->redirectCommandService->create(new CreateRedirectCommand(
            $entityType,
            $languageId,
            $requestedSlug,
            $targetEntityType,
            $targetEntityId,
            301,
        ));
    }

    public function recordGoneRedirect(string $entityType, int $languageId, string $requestedSlug): int
    {
        if ($this->redirectCommandService === null) {
            throw SeoConflictException::dueToReason('RedirectCommandService is required to record redirects.');
        }

        return $this->redirectCommandService->create(new CreateRedirectCommand(
            $entityType,
            $languageId,
            $requestedSlug,
            null,
            null,
            410,
        ));
    }

    private function normalizeRequestedSlug(ResolveRedirectCommand $command): string
    {
        $requestedPath = $this->normalizeNullableString($command->requestedPath);
        if ($requestedPath !== null) {
            return $requestedPath;
        }

        return trim($command->requestedSlug);
    }

    private function generateTargetUrl(RedirectDTO $redirect): ?string
    {
        if ($this->urlGenerator === null || $redirect->targetEntityType === null || $redirect->targetEntityId === null) {
            return null;
        }

        return $this->urlGenerator->generateEntityUrl(
            $redirect->targetEntityType,
            $redirect->targetEntityId,
            $redirect->languageId,
            null,
        );
    }

    private function normalizeNullableString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        return $trimmed === '' ? null : $trimmed;
    }
}
