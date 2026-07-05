<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\DTO\Schema;

final readonly class JsonLdSchemaDTO implements \JsonSerializable
{
    /**
     * @param array<string, mixed> $schema
     */
    public function __construct(
        private array $schema,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->schema;
    }
}
