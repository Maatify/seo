<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\DTO\Schema;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class WebsiteSchemaDTO implements \JsonSerializable
{
    public function __construct(
        public string $name,
        public string $url,
        public ?string $searchUrlTemplate = null,
    ) {
        if (trim($this->name) === '') {
            throw SeoInvalidArgumentException::emptyField('name');
        }
        if (trim($this->url) === '') {
            throw SeoInvalidArgumentException::emptyField('url');
        }
        if ($this->searchUrlTemplate !== null && trim($this->searchUrlTemplate) === '') {
            throw SeoInvalidArgumentException::emptyField('searchUrlTemplate');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => trim($this->name),
            'url' => trim($this->url),
        ];

        if ($this->searchUrlTemplate !== null) {
            $schema['potentialAction'] = [
                '@type' => 'SearchAction',
                'target' => trim($this->searchUrlTemplate),
                'query-input' => 'required name=search_term_string',
            ];
        }

        return $schema;
    }
}
