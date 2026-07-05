<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\SeoRender\DTO;

use Maatify\Seo\Exception\SeoInvalidArgumentException;
use Maatify\Seo\Shared\DTO\MetaTagsDTO;
use Maatify\Seo\Shared\DTO\Redirect\RedirectDecisionDTO;
use Maatify\Seo\Shared\DTO\Schema\JsonLdSchemaDTO;

final readonly class SeoPagePayloadDTO implements \JsonSerializable
{
    /**
     * @param array<mixed> $schemas
     */
    public function __construct(
        public MetaTagsDTO $metaTags,
        public array $schemas = [],
        public ?RedirectDecisionDTO $redirectDecision = null,
        public ?string $sitemapXml = null,
    ) {
        foreach ($this->schemas as $schema) {
            if (!$schema instanceof JsonLdSchemaDTO) {
                throw SeoInvalidArgumentException::emptyField('schemas');
            }
        }
        if ($this->sitemapXml !== null && trim($this->sitemapXml) === '') {
            throw SeoInvalidArgumentException::emptyField('sitemapXml');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'meta_tags' => $this->metaTags->jsonSerialize(),
            'schemas' => array_map(
                static function (mixed $schema): array { /** @var JsonLdSchemaDTO $schema */ return $schema->jsonSerialize(); },
                $this->schemas,
            ),
            'redirect_decision' => $this->redirectDecision?->jsonSerialize(),
            'sitemap_xml' => $this->sitemapXml,
        ];
    }
}
