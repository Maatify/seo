<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\JsonLd;

use Maatify\Seo\Web\Validation\DTO\SeoValidationIssueDTO;

final class JsonLdSemanticValidator
{
    /**
     * @param array<array-key, mixed> $node
     * @param list<SeoValidationIssueDTO> $issues
     */
    public static function validate(array $node, string $field, array &$issues): void
    {
        $types = self::normalizedTypes($node['@type'] ?? null);

        if (in_array('Product', $types, true)) {
            self::validateProduct($node, $field, $issues);
        }

        if (in_array('Offer', $types, true)) {
            self::validateOffer($node, $field, $issues);
        }
    }

    /**
     * @param array<array-key, mixed> $node
     * @param list<SeoValidationIssueDTO> $issues
     */
    private static function validateProduct(array $node, string $field, array &$issues): void
    {
        foreach ($node as $property => $value) {
            switch ($property) {
                case 'name':
                    self::validateProperty($issues, $value, $field . '.name', ['Text']);
                    break;
                case 'description':
                    self::validateProperty($issues, $value, $field . '.description', ['Text', 'TextObject']);
                    break;
                case 'sku':
                    self::validateProperty($issues, $value, $field . '.sku', ['Text']);
                    break;
                case 'gtin':
                    self::validateProperty($issues, $value, $field . '.gtin', ['Text', 'URL']);
                    break;
                case 'mpn':
                    self::validateProperty($issues, $value, $field . '.mpn', ['Text']);
                    break;
                case 'brand':
                    self::validateProperty($issues, $value, $field . '.brand', ['Brand', 'Organization']);
                    break;
                case 'image':
                    self::validateProperty($issues, $value, $field . '.image', ['ImageObject', 'URL']);
                    break;
                case 'category':
                    self::validateProperty($issues, $value, $field . '.category', ['CategoryCode', 'PhysicalActivityCategory', 'Text', 'Thing', 'URL']);
                    break;
                case 'url':
                    self::validateProperty($issues, $value, $field . '.url', ['URL']);
                    break;
                case 'color':
                    self::validateProperty($issues, $value, $field . '.color', ['Text']);
                    break;
                case 'size':
                    self::validateProperty($issues, $value, $field . '.size', ['DefinedTerm', 'QuantitativeValue', 'SizeSpecification', 'Text']);
                    break;
                case 'material':
                    self::validateProperty($issues, $value, $field . '.material', ['Product', 'Text', 'URL']);
                    break;
                case 'pattern':
                    self::validateProperty($issues, $value, $field . '.pattern', ['DefinedTerm', 'Text']);
                    break;
                case 'offers':
                    self::validateProperty($issues, $value, $field . '.offers', ['Demand', 'Offer', 'AggregateOffer', 'OfferForLease', 'OfferForPurchase']);
                    break;
                case 'aggregateRating':
                    self::validateProperty($issues, $value, $field . '.aggregateRating', ['AggregateRating']);
                    break;
                case 'review':
                    self::validateProperty($issues, $value, $field . '.review', ['Review']);
                    break;
                case 'isVariantOf':
                    self::validateProperty($issues, $value, $field . '.isVariantOf', ['ProductGroup', 'ProductModel']);
                    break;
                case 'inProductGroupWithID':
                    self::validateProperty($issues, $value, $field . '.inProductGroupWithID', ['Text']);
                    break;
            }
        }
    }

    /**
     * @param array<array-key, mixed> $node
     * @param list<SeoValidationIssueDTO> $issues
     */
    private static function validateOffer(array $node, string $field, array &$issues): void
    {
        foreach ($node as $property => $value) {
            switch ($property) {
                case 'price':
                    self::validateProperty($issues, $value, $field . '.price', ['Number', 'Text']);
                    break;
                case 'priceCurrency':
                    self::validateProperty($issues, $value, $field . '.priceCurrency', ['Text']);
                    break;
                case 'availability':
                    self::validateProperty($issues, $value, $field . '.availability', ['ItemAvailability']);
                    break;
                case 'url':
                    self::validateProperty($issues, $value, $field . '.url', ['URL']);
                    break;
                case 'validFrom':
                    self::validateProperty($issues, $value, $field . '.validFrom', ['Date', 'DateTime']);
                    break;
                case 'priceValidUntil':
                    self::validateProperty($issues, $value, $field . '.priceValidUntil', ['Date']);
                    break;
                case 'itemCondition':
                    self::validateProperty($issues, $value, $field . '.itemCondition', ['OfferItemCondition']);
                    break;
                case 'seller':
                    self::validateProperty($issues, $value, $field . '.seller', ['Organization', 'Person']);
                    break;
            }
        }
    }

    /**
     * @param list<SeoValidationIssueDTO> $issues
     * @param list<string> $allowedTypes
     */
    private static function validateProperty(array &$issues, mixed $value, string $field, array $allowedTypes): void
    {
        if (is_array($value) && array_is_list($value)) {
            if ($value === []) {
                self::invalidProperty($issues, $field, 'Repeated JSON-LD properties should be non-empty numeric lists.');
                return;
            }

            foreach ($value as $key => $item) {
                self::validatePropertyValue($issues, $item, $field . '.' . $key, $allowedTypes);
            }

            return;
        }

        self::validatePropertyValue($issues, $value, $field, $allowedTypes);
    }

    /**
     * @param list<SeoValidationIssueDTO> $issues
     * @param list<string> $allowedTypes
     */
    private static function validatePropertyValue(array &$issues, mixed $value, string $field, array $allowedTypes): void
    {
        if (is_array($value)) {
            if ($value === [] || array_is_list($value)) {
                self::invalidProperty($issues, $field, 'JSON-LD property value has an invalid shape.');
                return;
            }

            if (!self::hasValidType($value)) {
                return;
            }

            if (self::matchesNodeType($value['@type'], $allowedTypes)) {
                return;
            }

            if (self::containsNodeType($allowedTypes)) {
                self::invalidRelationship($issues, $field, 'JSON-LD node type is outside the property range.');
                return;
            }

            self::invalidProperty($issues, $field, 'JSON-LD property expects a scalar value.');
            return;
        }

        if (self::matchesScalar($value, $allowedTypes)) {
            return;
        }

        self::invalidProperty($issues, $field, 'JSON-LD property value does not match its declared representation.');
    }

    /** @param array<array-key, mixed> $node */
    private static function hasValidType(array $node): bool
    {
        if (!array_key_exists('@type', $node)) {
            return false;
        }

        return self::isValidType($node['@type']);
    }

    private static function isValidType(mixed $type): bool
    {
        if (is_string($type)) {
            return trim($type) !== '';
        }

        if (!is_array($type) || $type === [] || !array_is_list($type)) {
            return false;
        }

        foreach ($type as $item) {
            if (!is_string($item) || trim($item) === '') {
                return false;
            }
        }

        return true;
    }

    /** @return list<string> */
    private static function normalizedTypes(mixed $type): array
    {
        if (is_string($type)) {
            return [self::normalizeType($type)];
        }

        if (!is_array($type) || !array_is_list($type)) {
            return [];
        }

        $types = [];
        foreach ($type as $item) {
            if (is_string($item)) {
                $types[] = self::normalizeType($item);
            }
        }

        return $types;
    }

    private static function normalizeType(string $type): string
    {
        $type = trim($type);
        if (preg_match('#^https?://schema\.org/(.+)$#', $type, $matches) === 1) {
            return $matches[1];
        }

        return $type;
    }

    /** @param list<string> $allowedTypes */
    private static function matchesNodeType(mixed $type, array $allowedTypes): bool
    {
        $normalizedTypes = self::normalizedTypes($type);
        foreach ($allowedTypes as $allowedType) {
            if (!self::isScalarType($allowedType) && in_array($allowedType, $normalizedTypes, true)) {
                return true;
            }
        }

        return false;
    }

    /** @param list<string> $allowedTypes */
    private static function containsNodeType(array $allowedTypes): bool
    {
        foreach ($allowedTypes as $allowedType) {
            if (!self::isScalarType($allowedType)) {
                return true;
            }
        }

        return false;
    }

    private static function isScalarType(string $type): bool
    {
        return in_array($type, ['Text', 'Number', 'Integer', 'URL', 'Date', 'DateTime', 'ItemAvailability', 'OfferItemCondition'], true);
    }

    /** @param list<string> $allowedTypes */
    private static function matchesScalar(mixed $value, array $allowedTypes): bool
    {
        foreach ($allowedTypes as $allowedType) {
            if ($allowedType === 'Text' && is_string($value)) {
                return true;
            }

            if (in_array($allowedType, ['URL', 'Date', 'DateTime', 'ItemAvailability', 'OfferItemCondition'], true)
                && is_string($value)
                && trim($value) !== '') {
                return true;
            }

            if ($allowedType === 'Number' && (is_int($value) || is_float($value))) {
                return true;
            }

            if ($allowedType === 'Integer' && is_int($value)) {
                return true;
            }
        }

        return false;
    }

    /** @param list<SeoValidationIssueDTO> $issues */
    private static function invalidProperty(array &$issues, string $field, string $message): void
    {
        $issues[] = new SeoValidationIssueDTO('json_ld_invalid_property', 'error', $message, $field);
    }

    /** @param list<SeoValidationIssueDTO> $issues */
    private static function invalidRelationship(array &$issues, string $field, string $message): void
    {
        $issues[] = new SeoValidationIssueDTO('json_ld_invalid_relationship', 'error', $message, $field);
    }
}
