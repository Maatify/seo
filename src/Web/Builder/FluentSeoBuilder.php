<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Builder;

use Maatify\Seo\Exception\SeoInvalidArgumentException;
use Maatify\Seo\Shared\DTO\MetaTagsDTO;
use Maatify\Seo\Shared\DTO\Schema\JsonLdSchemaDTO;
use Maatify\Seo\Web\DTO\SeoHeadHtmlDTO;
use Maatify\Seo\Web\Render\SeoHeadHtmlRenderer;
use Maatify\Seo\Web\Schema\SpatieSchemaAdapter;

final class FluentSeoBuilder
{
    private ?string $title = null;
    private ?string $description = null;
    private ?string $canonicalUrl = null;
    private string $robots = 'index,follow';
    private ?string $openGraphTitle = null;
    private ?string $openGraphDescription = null;
    private ?string $openGraphType = null;
    private ?string $openGraphUrl = null;
    private ?string $openGraphImage = null;
    private ?string $twitterCard = null;
    private ?string $twitterTitle = null;
    private ?string $twitterDescription = null;
    private ?string $twitterImage = null;

    /** @var list<JsonLdSchemaDTO> */
    private array $schemas = [];

    public function title(string $title): self
    {
        if ($title === '') {
            throw SeoInvalidArgumentException::emptyField('title');
        }

        $this->title = $title;

        return $this;
    }

    public function description(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function canonical(?string $canonicalUrl): self
    {
        $this->canonicalUrl = $canonicalUrl;

        return $this;
    }

    public function robots(string $robots): self
    {
        if ($robots === '') {
            throw SeoInvalidArgumentException::emptyField('robots');
        }

        $this->robots = $robots;

        return $this;
    }

    public function openGraphTitle(?string $title): self
    {
        $this->openGraphTitle = $title;

        return $this;
    }

    public function openGraphDescription(?string $description): self
    {
        $this->openGraphDescription = $description;

        return $this;
    }

    public function openGraphType(?string $type): self
    {
        $this->openGraphType = $type;

        return $this;
    }

    public function openGraphUrl(?string $url): self
    {
        $this->openGraphUrl = $url;

        return $this;
    }

    public function openGraphImage(?string $image): self
    {
        $this->openGraphImage = $image;

        return $this;
    }

    public function twitterCard(?string $card): self
    {
        $this->twitterCard = $card;

        return $this;
    }

    public function twitterTitle(?string $title): self
    {
        $this->twitterTitle = $title;

        return $this;
    }

    public function twitterDescription(?string $description): self
    {
        $this->twitterDescription = $description;

        return $this;
    }

    public function twitterImage(?string $image): self
    {
        $this->twitterImage = $image;

        return $this;
    }

    /** @param array<string, mixed>|JsonLdSchemaDTO $schema */
    public function schema(JsonLdSchemaDTO|array $schema): self
    {
        $this->schemas[] = $this->normalizeSchema($schema, 'schema');

        return $this;
    }

    public function spatieSchema(object $schema, ?SpatieSchemaAdapter $adapter = null): self
    {
        $this->schemas[] = ($adapter ?? new SpatieSchemaAdapter())->toJsonLdSchemaDTO($schema);

        return $this;
    }

    /** @param array<mixed> $schemas */
    public function schemas(array $schemas): self
    {
        if (!array_is_list($schemas)) {
            throw SeoInvalidArgumentException::invalidSchemaEntry('schemas');
        }

        foreach ($schemas as $index => $schema) {
            $this->schemas[] = $this->normalizeSchema($schema, 'schemas[' . $index . ']');
        }

        return $this;
    }

    public function clearSchemas(): self
    {
        $this->schemas = [];

        return $this;
    }

    public function buildMetaTags(): MetaTagsDTO
    {
        return new MetaTagsDTO(
            title: $this->requiredTitle(),
            description: $this->description,
            canonicalUrl: $this->canonicalUrl,
            robots: $this->robots,
            openGraphTitle: $this->openGraphTitle,
            openGraphDescription: $this->openGraphDescription,
            openGraphUrl: $this->openGraphUrl,
            twitterTitle: $this->twitterTitle,
            twitterDescription: $this->twitterDescription,
            openGraphType: $this->openGraphType,
            openGraphImage: $this->openGraphImage,
            twitterCard: $this->twitterCard,
            twitterImage: $this->twitterImage,
        );
    }

    public function render(SeoHeadHtmlRenderer $renderer = new SeoHeadHtmlRenderer()): string
    {
        return $renderer->render($this->buildMetaTags(), $this->schemas);
    }

    public function renderDto(SeoHeadHtmlRenderer $renderer = new SeoHeadHtmlRenderer()): SeoHeadHtmlDTO
    {
        return $renderer->renderDto($this->buildMetaTags(), $this->schemas);
    }

    private function requiredTitle(): string
    {
        if ($this->title === null || $this->title === '') {
            throw SeoInvalidArgumentException::emptyField('title');
        }

        return $this->title;
    }

    private function normalizeSchema(mixed $schema, string $field): JsonLdSchemaDTO
    {
        if ($schema instanceof JsonLdSchemaDTO) {
            return $schema;
        }

        if (!is_array($schema) || $schema === [] || array_is_list($schema)) {
            throw SeoInvalidArgumentException::invalidSchemaEntry($field);
        }

        /** @var array<string, mixed> $schema */
        return new JsonLdSchemaDTO($schema);
    }
}
