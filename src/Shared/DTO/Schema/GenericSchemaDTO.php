<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\DTO\Schema;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class GenericSchemaDTO implements \JsonSerializable
{
    /**
     * @param array<string, mixed> $properties
     */
    public function __construct(
        public string $type,
        public array $properties = [],
    ) {
        if (trim($this->type) === '') {
            throw SeoInvalidArgumentException::emptyField('type');
        }

        $this->assertValidProperties($this->properties, 'properties');
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_merge(
            [
                '@context' => 'https://schema.org',
                '@type' => trim($this->type),
            ],
            $this->properties,
        );
    }

    /**
     * @param array<string, mixed> $properties
     */
    private function assertValidProperties(array $properties, string $field): void
    {
        foreach ($properties as $key => $value) {
            if (trim((string) $key) === '') {
                throw SeoInvalidArgumentException::emptyField($field . '.key');
            }

            if ($value === null) {
                throw SeoInvalidArgumentException::emptyField($field . '.' . $key);
            }

            if (is_string($value) && trim($value) === '') {
                throw SeoInvalidArgumentException::emptyField($field . '.' . $key);
            }

            if (is_array($value)) {
                if ($value === []) {
                    throw SeoInvalidArgumentException::emptyField($field . '.' . $key);
                }

                /** @var array<string, mixed> $nested */
                $nested = $value;
                $this->assertValidProperties($nested, $field . '.' . $key);
            }
        }
    }
}
