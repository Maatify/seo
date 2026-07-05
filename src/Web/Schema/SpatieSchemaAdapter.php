<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Schema;

use Maatify\Seo\Exception\SeoInvalidArgumentException;
use Maatify\Seo\Shared\DTO\Schema\JsonLdSchemaDTO;

final class SpatieSchemaAdapter
{
    public function supports(object $schema): bool
    {
        try {
            $this->extractSchemaArray($schema);
            return true;
        } catch (SeoInvalidArgumentException) {
            return false;
        }
    }

    public function toJsonLdSchemaDTO(object $schema): JsonLdSchemaDTO
    {
        return new JsonLdSchemaDTO($this->extractSchemaArray($schema));
    }

    /**
     * @param array<mixed> $schemas
     * @return list<JsonLdSchemaDTO>
     */
    public function toJsonLdSchemaDTOs(array $schemas): array
    {
        $dtoList = [];

        foreach ($schemas as $index => $schema) {
            if (!is_object($schema)) {
                throw SeoInvalidArgumentException::invalidSchemaEntry('schemas[' . $index . ']');
            }

            $dtoList[] = $this->toJsonLdSchemaDTO($schema);
        }

        return $dtoList;
    }

    /** @return array<string, mixed> */
    private function extractSchemaArray(object $schema): array
    {
        if (method_exists($schema, 'toArray')) {
            $arraySchema = $this->normalizeSchemaArray($schema->toArray());
            if ($arraySchema !== null) {
                return $arraySchema;
            }
        }

        if (method_exists($schema, 'jsonSerialize')) {
            $arraySchema = $this->normalizeSchemaArray($schema->jsonSerialize());
            if ($arraySchema !== null) {
                return $arraySchema;
            }
        }

        if (method_exists($schema, 'toScript')) {
            $arraySchema = $this->schemaArrayFromScript($schema->toScript());
            if ($arraySchema !== null) {
                return $arraySchema;
            }
        }

        throw SeoInvalidArgumentException::invalidSchemaEntry('schema');
    }

    /** @return array<string, mixed>|null */
    private function normalizeSchemaArray(mixed $schema): ?array
    {
        if (!is_array($schema) || $schema === [] || array_is_list($schema)) {
            return null;
        }

        foreach (array_keys($schema) as $key) {
            if (!is_string($key)) {
                return null;
            }
        }

        /** @var array<string, mixed> $schema */
        return $schema;
    }

    /** @return array<string, mixed>|null */
    private function schemaArrayFromScript(mixed $script): ?array
    {
        if (!is_string($script) || trim($script) === '') {
            return null;
        }

        $json = $this->extractJsonFromScript($script);
        if ($json === null) {
            return null;
        }

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return $this->normalizeSchemaArray($decoded);
    }

    private function extractJsonFromScript(string $script): ?string
    {
        $trimmed = trim($script);

        if (str_starts_with($trimmed, '{')) {
            return $trimmed;
        }

        $matches = [];
        if (preg_match('/<script\b[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $script, $matches) !== 1) {
            return null;
        }

        $json = html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');

        return $json === '' ? null : $json;
    }
}
