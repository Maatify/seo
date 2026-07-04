<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Page;

use Maatify\Seo\Exception\SeoInvalidArgumentException;
use Maatify\Seo\Shared\DTO\Schema\JsonLdSchemaDTO;

/** @internal */
final class DomainSeoPresetFactoryHelper
{
    /** @param array<string, mixed> $data */
    public static function requireString(array $data, string $key, string $field): string
    {
        if (!array_key_exists($key, $data)) {
            throw SeoInvalidArgumentException::emptyField($field);
        }

        return self::expectString($data[$key], $field);
    }

    public static function expectString(mixed $value, string $field): string
    {
        if (!is_string($value) || $value === '') {
            throw SeoInvalidArgumentException::emptyField($field);
        }

        return $value;
    }

    public static function expectPrice(mixed $value, string $field): string|int|float
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            throw SeoInvalidArgumentException::invalidValue($field, 'Expected string, integer, or float price.');
        }

        if (is_string($value) && $value === '') {
            throw SeoInvalidArgumentException::emptyField($field);
        }

        return $value;
    }

    /** @param array<string, mixed> $options */
    public static function canonicalFromOptions(array $options): ?string
    {
        if (array_key_exists('canonicalUrl', $options)) {
            return self::expectString($options['canonicalUrl'], 'canonicalUrl');
        }

        if (!array_key_exists('canonicalBaseUrl', $options)) {
            return null;
        }

        $base = rtrim(self::expectString($options['canonicalBaseUrl'], 'canonicalBaseUrl'), '/');
        $path = array_key_exists('canonicalPath', $options)
            ? '/' . ltrim(self::expectString($options['canonicalPath'], 'canonicalPath'), '/')
            : '';

        return $base . $path;
    }

    /**
     * @param array<string, mixed> $options
     * @param JsonLdSchemaDTO|array<string, mixed> $schema
     * @return array<string, mixed>
     */
    public static function appendExtraSchema(array $options, JsonLdSchemaDTO|array $schema): array
    {
        $extraSchemas = $options['extraSchemas'] ?? [];
        if (!is_array($extraSchemas) || !array_is_list($extraSchemas)) {
            throw SeoInvalidArgumentException::invalidSchemaEntry('extraSchemas');
        }

        $extraSchemas[] = $schema;
        $options['extraSchemas'] = $extraSchemas;

        return $options;
    }

    /** @param array<string, mixed> $data */
    public static function optionalString(array $data, string $key, string $field): ?string
    {
        if (!array_key_exists($key, $data)) {
            return null;
        }

        return self::expectString($data[$key], $field);
    }

    /** @return array<string, mixed> */
    public static function associativeArray(mixed $value, string $field): array
    {
        if (!is_array($value) || $value === [] || array_is_list($value)) {
            throw SeoInvalidArgumentException::invalidSchemaEntry($field);
        }

        return $value;
    }
}
