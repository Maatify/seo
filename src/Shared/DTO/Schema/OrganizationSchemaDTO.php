<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\DTO\Schema;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class OrganizationSchemaDTO implements \JsonSerializable
{
    /**
     * @param list<string> $sameAsUrls
     */
    public function __construct(
        public string $name,
        public ?string $url = null,
        public ?string $logoUrl = null,
        public array $sameAsUrls = [],
    ) {
        if (trim($this->name) === '') {
            throw SeoInvalidArgumentException::emptyField('name');
        }
        $this->assertOptionalUrl($this->url, 'url');
        $this->assertOptionalUrl($this->logoUrl, 'logoUrl');

        foreach ($this->sameAsUrls as $sameAsUrl) {
            if (trim($sameAsUrl) === '') {
                throw SeoInvalidArgumentException::emptyField('sameAsUrls');
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => trim($this->name),
        ];

        if ($this->url !== null) {
            $schema['url'] = trim($this->url);
        }
        if ($this->logoUrl !== null) {
            $schema['logo'] = trim($this->logoUrl);
        }
        if ($this->sameAsUrls !== []) {
            $schema['sameAs'] = array_map('trim', $this->sameAsUrls);
        }

        return $schema;
    }

    private function assertOptionalUrl(?string $url, string $field): void
    {
        if ($url !== null && trim($url) === '') {
            throw SeoInvalidArgumentException::emptyField($field);
        }
    }
}
