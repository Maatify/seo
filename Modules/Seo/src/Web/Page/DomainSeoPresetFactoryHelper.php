<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Page;

use Maatify\Seo\Exception\SeoInvalidArgumentException;
use Maatify\Seo\Shared\DTO\Schema\JsonLdSchemaDTO;
use Maatify\Seo\Web\Indexing\CanonicalUrlBuilder;

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

        if (!array_key_exists('canonicalBaseUrl', $options) && !array_key_exists('canonicalPath', $options)) {
            return null;
        }

        $baseUrl = array_key_exists('canonicalBaseUrl', $options)
            ? self::expectString($options['canonicalBaseUrl'], 'canonicalBaseUrl')
            : null;

        $builder = new CanonicalUrlBuilder($baseUrl);

        if (array_key_exists('canonicalPath', $options)) {
            $builder->setPath(self::expectString($options['canonicalPath'], 'canonicalPath'));
        }

        if (array_key_exists('queryParams', $options)) {
            $builder->setQueryParams(self::queryParams($options['queryParams']));
        }

        if (array_key_exists('allowedQueryParams', $options)) {
            $builder->preserveQueryParams(self::stringList($options['allowedQueryParams'], 'allowedQueryParams'));
        }

        return $builder->build();
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

        $normalized = [];
        foreach ($value as $key => $item) {
            if (!is_string($key) || $key === '') {
                throw SeoInvalidArgumentException::invalidValue($field, 'Expected associative array with non-empty string keys.');
            }

            $normalized[$key] = $item;
        }

        return $normalized;
    }

    /**
     * @return array<string, string|int|float|bool|null>
     */
    private static function queryParams(mixed $value): array
    {
        if (!is_array($value)) {
            throw SeoInvalidArgumentException::invalidValue('queryParams', 'Expected associative query parameter array.');
        }

        if ($value === []) {
            return [];
        }

        if (array_is_list($value)) {
            throw SeoInvalidArgumentException::invalidValue('queryParams', 'Expected associative query parameter array.');
        }

        $normalized = [];
        foreach ($value as $key => $item) {
            if (!is_string($key) || $key === '') {
                throw SeoInvalidArgumentException::invalidValue('queryParams', 'Expected non-empty string query parameter names.');
            }

            if (!is_string($item) && !is_int($item) && !is_float($item) && !is_bool($item) && $item !== null) {
                throw SeoInvalidArgumentException::invalidValue('queryParams', 'Expected scalar string, integer, float, boolean, or null values.');
            }

            $normalized[$key] = $item;
        }

        return $normalized;
    }

    /** @return list<string> */
    private static function stringList(mixed $value, string $field): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw SeoInvalidArgumentException::invalidValue($field, 'Expected a list of strings.');
        }

        $normalized = [];
        foreach ($value as $item) {
            if (!is_string($item) || $item === '') {
                throw SeoInvalidArgumentException::invalidValue($field, 'Expected a list of non-empty strings.');
            }

            $normalized[] = $item;
        }

        return $normalized;
    }
}
