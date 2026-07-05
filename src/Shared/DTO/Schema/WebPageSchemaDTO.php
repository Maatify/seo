<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\DTO\Schema;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class WebPageSchemaDTO implements \JsonSerializable
{
    public function __construct(
        public string $name,
        public string $url,
        public ?string $description = null,
    ) {
        if (trim($this->name) === '') {
            throw SeoInvalidArgumentException::emptyField('name');
        }
        if (trim($this->url) === '') {
            throw SeoInvalidArgumentException::emptyField('url');
        }
        if ($this->description !== null && trim($this->description) === '') {
            throw SeoInvalidArgumentException::emptyField('description');
        }
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => trim($this->name),
            'url' => trim($this->url),
        ];

        if ($this->description !== null) {
            $schema['description'] = trim($this->description);
        }

        return $schema;
    }
}
