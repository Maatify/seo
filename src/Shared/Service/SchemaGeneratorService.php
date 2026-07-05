<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\Service;

use Maatify\Seo\Exception\SeoInvalidArgumentException;
use Maatify\Seo\Shared\DTO\Schema\JsonLdSchemaDTO;

final readonly class SchemaGeneratorService
{
    public function generate(\JsonSerializable $schema): JsonLdSchemaDTO
    {
        $serialized = $schema->jsonSerialize();
        if (!is_array($serialized) || $serialized === []) {
            throw SeoInvalidArgumentException::emptyField('schema');
        }

        /** @var array<string, mixed> $jsonLd */
        $jsonLd = $serialized;

        return new JsonLdSchemaDTO($jsonLd);
    }

    /**
     * @param list<\JsonSerializable> $schemas
     */
    public function generateGraph(array $schemas): JsonLdSchemaDTO
    {
        if ($schemas === []) {
            throw SeoInvalidArgumentException::emptyField('schemas');
        }

        $graph = [];
        foreach ($schemas as $schema) {
            $serialized = $this->generate($schema)->jsonSerialize();
            unset($serialized['@context']);
            $graph[] = $serialized;
        }

        return new JsonLdSchemaDTO([
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ]);
    }
}
