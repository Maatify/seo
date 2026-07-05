<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\Service;

use Maatify\Seo\Shared\Service\SeoOverrideQueryService;
use Maatify\Seo\Exception\SeoNotFoundException;
use Maatify\Seo\Shared\Command\GenerateMetaTagsCommand;
use Maatify\Seo\Shared\Contract\HostUrlGeneratorInterface;
use Maatify\Seo\Shared\DTO\MetaTagsDTO;

final readonly class MetaGeneratorService
{
    public function __construct(
        private SeoOverrideQueryService $overrideQueryService,
        private ?HostUrlGeneratorInterface $urlGenerator = null,
    ) {
    }

    /**
     * Builds host-agnostic meta tags from host-provided defaults.
     *
     * Missing SEO overrides are expected for most entities, so the query service's
     * not-found exception is treated as "no override" and the defaults are used.
     */
    public function generate(GenerateMetaTagsCommand $command): MetaTagsDTO
    {
        $title = trim($command->defaultTitle);
        $description = $this->normalizeNullableString($command->defaultDescription);

        try {
            $override = $this->overrideQueryService->getActiveForEntity(
                $command->entityType,
                $command->entityId,
                $command->languageId,
            );

            $overrideTitle = $this->normalizeNullableString($override->metaTitle);
            if ($overrideTitle !== null) {
                $title = $overrideTitle;
            }

            $overrideDescription = $this->normalizeNullableString($override->metaDescription);
            if ($overrideDescription !== null) {
                $description = $overrideDescription;
            }
        } catch (SeoNotFoundException) {
            // No override exists for this entity/language; keep host-provided defaults.
        }

        $canonicalUrl = $this->resolveCanonicalUrl($command);

        return new MetaTagsDTO(
            title: $title,
            description: $description,
            canonicalUrl: $canonicalUrl,
            robots: trim($command->robots),
            openGraphTitle: $title,
            openGraphDescription: $description,
            openGraphUrl: $canonicalUrl,
            twitterTitle: $title,
            twitterDescription: $description,
        );
    }

    private function resolveCanonicalUrl(GenerateMetaTagsCommand $command): ?string
    {
        $canonicalUrl = $this->normalizeNullableString($command->canonicalUrl);
        if ($canonicalUrl !== null) {
            return $canonicalUrl;
        }

        if ($this->urlGenerator === null) {
            return null;
        }

        return $this->urlGenerator->generateEntityUrl(
            $command->entityType,
            $command->entityId,
            $command->languageId,
            $command->slug,
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
