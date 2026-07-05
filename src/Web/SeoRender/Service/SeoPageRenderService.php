<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\SeoRender\Service;

use Maatify\Seo\Exception\SeoConflictException;
use Maatify\Seo\Shared\Command\GenerateMetaTagsCommand;
use Maatify\Seo\Shared\Command\Redirect\ResolveRedirectCommand;
use Maatify\Seo\Shared\DTO\Redirect\RedirectDecisionDTO;
use Maatify\Seo\Shared\DTO\Schema\BreadcrumbSchemaDTO;
use Maatify\Seo\Shared\DTO\Schema\JsonLdSchemaDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapGenerationResultDTO;
use Maatify\Seo\Shared\Service\MetaGeneratorService;
use Maatify\Seo\Shared\Service\RedirectManagerService;
use Maatify\Seo\Shared\Service\SchemaGeneratorService;
use Maatify\Seo\Shared\Service\SitemapGeneratorService;
use Maatify\Seo\Web\SeoRender\Command\RenderSeoPageCommand;
use Maatify\Seo\Web\SeoRender\DTO\SeoPagePayloadDTO;

final readonly class SeoPageRenderService
{
    public function __construct(
        private MetaGeneratorService $metaGeneratorService,
        private SchemaGeneratorService $schemaGeneratorService,
        private ?RedirectManagerService $redirectManagerService = null,
        private ?SitemapGeneratorService $sitemapGeneratorService = null,
    ) {
    }

    public function render(RenderSeoPageCommand $command): SeoPagePayloadDTO
    {
        return new SeoPagePayloadDTO(
            metaTags: $this->metaGeneratorService->generate($this->createMetaCommand($command)),
            schemas: $this->generateSchemas($command),
        );
    }

    public function renderWithRedirectDecision(
        RenderSeoPageCommand $command,
        string $requestedSlug,
        ?string $requestedPath = null,
    ): SeoPagePayloadDTO {
        return new SeoPagePayloadDTO(
            metaTags: $this->metaGeneratorService->generate($this->createMetaCommand($command)),
            schemas: $this->generateSchemas($command),
            redirectDecision: $this->resolveRedirect($command->entityType, $command->languageId, $requestedSlug, $requestedPath),
        );
    }

    /**
     * @param array<mixed> $urls
     */
    public function generateUrlSitemap(array $urls): SitemapGenerationResultDTO
    {
        return $this->requireSitemapGenerator()->generateUrlSitemap($urls);
    }

    /**
     * @param array<mixed> $entries
     */
    public function generateSitemapIndex(array $entries): SitemapGenerationResultDTO
    {
        return $this->requireSitemapGenerator()->generateSitemapIndex($entries);
    }

    public function withSitemapXml(RenderSeoPageCommand $command, string $sitemapXml): SeoPagePayloadDTO
    {
        return new SeoPagePayloadDTO(
            metaTags: $this->metaGeneratorService->generate($this->createMetaCommand($command)),
            schemas: $this->generateSchemas($command),
            sitemapXml: $sitemapXml,
        );
    }

    private function resolveRedirect(
        string $entityType,
        int $languageId,
        string $requestedSlug,
        ?string $requestedPath,
    ): RedirectDecisionDTO {
        if ($this->redirectManagerService === null) {
            throw SeoConflictException::dueToReason('RedirectManagerService is required to resolve Web redirect decisions.');
        }

        return $this->redirectManagerService->resolve(new ResolveRedirectCommand(
            $entityType,
            $languageId,
            $requestedSlug,
            $requestedPath,
        ));
    }

    private function createMetaCommand(RenderSeoPageCommand $command): GenerateMetaTagsCommand
    {
        return new GenerateMetaTagsCommand(
            $command->entityType,
            $command->entityId,
            $command->languageId,
            $command->defaultTitle,
            $command->defaultDescription,
            $command->slug,
            $command->canonicalUrl,
            $command->robots,
        );
    }

    /**
     * @return list<JsonLdSchemaDTO>
     */
    private function generateSchemas(RenderSeoPageCommand $command): array
    {
        $schemas = $command->schemas;
        if ($command->breadcrumbs !== null) {
            $schemas[] = new BreadcrumbSchemaDTO($command->breadcrumbs);
        }

        if ($schemas === []) {
            return [];
        }

        /** @var list<\JsonSerializable> $schemasList */
        $schemasList = array_values($schemas);
        return [$this->schemaGeneratorService->generateGraph($schemasList)];
    }

    private function requireSitemapGenerator(): SitemapGeneratorService
    {
        if ($this->sitemapGeneratorService === null) {
            throw SeoConflictException::dueToReason('SitemapGeneratorService is required to generate Web sitemap XML.');
        }

        return $this->sitemapGeneratorService;
    }
}
