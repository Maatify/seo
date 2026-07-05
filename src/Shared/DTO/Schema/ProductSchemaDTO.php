<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\DTO\Schema;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class ProductSchemaDTO implements \JsonSerializable
{
    /**
     * @param array<string, mixed> $additionalProperties
     */
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?string $sku = null,
        public ?string $brandName = null,
        public array $additionalProperties = [],
    ) {
        if (trim($this->name) === '') {
            throw SeoInvalidArgumentException::emptyField('name');
        }
        $this->assertOptionalString($this->description, 'description');
        $this->assertOptionalString($this->sku, 'sku');
        $this->assertOptionalString($this->brandName, 'brandName');

        foreach ($this->additionalProperties as $key => $value) {
            if (trim((string) $key) === '') {
                throw SeoInvalidArgumentException::emptyField('additionalProperties.key');
            }
            if ($value === null || (is_string($value) && trim($value) === '')) {
                throw SeoInvalidArgumentException::emptyField('additionalProperties.' . $key);
            }
        }
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $schema = array_merge(
            [
                '@context' => 'https://schema.org',
                '@type' => 'Product',
                'name' => trim($this->name),
            ],
            $this->additionalProperties,
        );

        if ($this->description !== null) {
            $schema['description'] = trim($this->description);
        }
        if ($this->sku !== null) {
            $schema['sku'] = trim($this->sku);
        }
        if ($this->brandName !== null) {
            $schema['brand'] = [
                '@type' => 'Brand',
                'name' => trim($this->brandName),
            ];
        }

        return $schema;
    }

    private function assertOptionalString(?string $value, string $field): void
    {
        if ($value !== null && trim($value) === '') {
            throw SeoInvalidArgumentException::emptyField($field);
        }
    }
}
