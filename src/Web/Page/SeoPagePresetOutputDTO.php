<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Page;

use Maatify\Seo\Shared\DTO\MetaTagsDTO;
use Maatify\Seo\Shared\DTO\Schema\JsonLdSchemaDTO;

final readonly class SeoPagePresetOutputDTO implements \JsonSerializable
{
    /**
     * @param list<array{name: string, content: string, attribute: string}> $socialTags
     * @param list<JsonLdSchemaDTO> $schemas
     */
    public function __construct(
        public MetaTagsDTO $metaTags,
        public ?string $canonicalUrl,
        public string $robots,
        public array $socialTags = [],
        public string $socialHtml = '',
        public array $schemas = [],
        public string $html = '',
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'meta_tags' => $this->metaTags->jsonSerialize(),
            'canonical_url' => $this->canonicalUrl,
            'robots' => $this->robots,
            'social_tags' => $this->socialTags,
            'social_html' => $this->socialHtml,
            'schemas' => array_map(
                static fn (JsonLdSchemaDTO $schema): array => $schema->jsonSerialize(),
                $this->schemas
            ),
            'html' => $this->html,
        ];
    }
}
