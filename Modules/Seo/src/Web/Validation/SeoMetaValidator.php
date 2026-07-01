<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation;

use Maatify\Seo\Exception\SeoInvalidArgumentException;
use Maatify\Seo\Web\Validation\DTO\SeoValidationIssueDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationResultDTO;

final class SeoMetaValidator
{
    /**
     * @param array<string, mixed>|object $meta
     * @param array<string, mixed> $options
     */
    public static function validate(array|object $meta, array $options = []): SeoValidationResultDTO
    {
        $titleMinLength = self::intOption($options, 'titleMinLength', 10);
        $titleMaxLength = self::intOption($options, 'titleMaxLength', 60);
        $descriptionMinLength = self::intOption($options, 'descriptionMinLength', 50);
        $descriptionMaxLength = self::intOption($options, 'descriptionMaxLength', 160);
        $requireCanonical = self::boolOption($options, 'requireCanonical', false);

        if ($titleMinLength < 0 || $titleMaxLength < 1 || $titleMinLength > $titleMaxLength) {
            throw SeoInvalidArgumentException::invalidValue('title length limits', 'Expected non-negative minimum less than or equal to maximum.');
        }

        if ($descriptionMinLength < 0 || $descriptionMaxLength < 1 || $descriptionMinLength > $descriptionMaxLength) {
            throw SeoInvalidArgumentException::invalidValue('description length limits', 'Expected non-negative minimum less than or equal to maximum.');
        }

        $issues = [];
        $title = self::stringValue(self::value($meta, ['title', 'metaTitle']));
        if ($title === null || $title === '') {
            $issues[] = self::issue('missing_title', 'error', 'Meta title is required.', 'title');
        } else {
            self::validateLength($issues, $title, 'title', $titleMinLength, $titleMaxLength, 'title');
        }

        $description = self::stringValue(self::value($meta, ['description', 'metaDescription']));
        if ($description === null || $description === '') {
            $issues[] = self::issue('missing_description', 'warning', 'Meta description is recommended.', 'description');
        } else {
            self::validateLength($issues, $description, 'description', $descriptionMinLength, $descriptionMaxLength, 'description');
        }

        $canonical = self::stringValue(self::value($meta, ['canonical', 'canonicalUrl', 'canonical_url']));
        if ($canonical === null || $canonical === '') {
            if ($requireCanonical) {
                $issues[] = self::issue('missing_canonical', 'warning', 'Canonical URL is recommended.', 'canonical');
            }
        } elseif (filter_var($canonical, FILTER_VALIDATE_URL) === false) {
            $issues[] = self::issue('invalid_canonical', 'error', 'Canonical URL must be a valid absolute URL.', 'canonical');
        }

        self::validateRobots($issues, self::value($meta, ['robots']));
        self::validateOpenGraph($issues, $meta);
        self::validateTwitter($issues, $meta);
        self::validateJsonLd($issues, self::value($meta, ['jsonLd', 'json_ld', 'schema', 'schemas']));

        return new SeoValidationResultDTO($issues);
    }

    /** @param array<string, mixed> $options */
    private static function intOption(array $options, string $key, int $default): int
    {
        if (!array_key_exists($key, $options)) {
            return $default;
        }

        if (!is_int($options[$key])) {
            throw SeoInvalidArgumentException::invalidValue($key, 'Expected integer.');
        }

        return $options[$key];
    }

    /** @param array<string, mixed> $options */
    private static function boolOption(array $options, string $key, bool $default): bool
    {
        if (!array_key_exists($key, $options)) {
            return $default;
        }

        if (!is_bool($options[$key])) {
            throw SeoInvalidArgumentException::invalidValue($key, 'Expected boolean.');
        }

        return $options[$key];
    }

    /** @param array<string, mixed>|object $source @param list<string> $names */
    private static function value(array|object $source, array $names): mixed
    {
        foreach ($names as $name) {
            if (is_array($source) && array_key_exists($name, $source)) {
                return $source[$name];
            }
            if (is_object($source) && isset($source->{$name})) {
                return $source->{$name};
            }
        }
        return null;
    }

    private static function stringValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value) || is_numeric($value)) {
            return trim((string) $value);
        }
        return null;
    }

    /** @param list<SeoValidationIssueDTO> $issues */
    private static function validateLength(array &$issues, string $value, string $codePrefix, int $min, int $max, string $field): void
    {
        $length = strlen($value);
        if ($length < $min) {
            $issues[] = self::issue($codePrefix . '_too_short', 'warning', ucfirst($field) . ' is shorter than the recommended length.', $field);
        }
        if ($length > $max) {
            $issues[] = self::issue($codePrefix . '_too_long', 'warning', ucfirst($field) . ' is longer than the recommended length.', $field);
        }
    }

    /** @param list<SeoValidationIssueDTO> $issues */
    private static function validateRobots(array &$issues, mixed $robots): void
    {
        $directives = [];
        if (is_string($robots)) {
            $parts = preg_split('/[\s,]+/', strtolower($robots));
            $directives = is_array($parts) ? $parts : [];
        } elseif (is_array($robots)) {
            foreach ($robots as $directive) {
                if (is_string($directive) || is_numeric($directive)) {
                    $directives[] = strtolower(trim((string) $directive));
                }
            }
        }

        $directives = array_values(array_filter($directives, static fn (string $directive): bool => $directive !== ''));
        if (in_array('index', $directives, true) && in_array('noindex', $directives, true)) {
            $issues[] = self::issue('robots_index_conflict', 'warning', 'Robots directives include conflicting index values.', 'robots');
        }
        if (in_array('follow', $directives, true) && in_array('nofollow', $directives, true)) {
            $issues[] = self::issue('robots_follow_conflict', 'warning', 'Robots directives include conflicting follow values.', 'robots');
        }
    }

    /** @param list<SeoValidationIssueDTO> $issues @param array<string, mixed>|object $meta */
    private static function validateOpenGraph(array &$issues, array|object $meta): void
    {
        $og = self::value($meta, ['openGraph', 'og']);
        $hasOg = is_array($og) || is_object($og) || self::value($meta, ['openGraphTitle', 'open_graph_title']) !== null || self::value($meta, ['openGraphDescription', 'open_graph_description']) !== null || self::value($meta, ['openGraphImage', 'open_graph_image']) !== null;
        if (!$hasOg) {
            return;
        }
        if (is_array($og) || is_object($og)) {
            self::requireOpenGraphField($issues, $og, 'og:title', ['og:title', 'title', 'openGraphTitle', 'open_graph_title']);
            self::requireOpenGraphField($issues, $og, 'og:description', ['og:description', 'description', 'openGraphDescription', 'open_graph_description']);
            self::requireOpenGraphField($issues, $og, 'og:image', ['og:image', 'image', 'openGraphImage', 'open_graph_image']);
            return;
        }

        self::requireOpenGraphField($issues, $meta, 'og:title', ['openGraphTitle', 'open_graph_title']);
        self::requireOpenGraphField($issues, $meta, 'og:description', ['openGraphDescription', 'open_graph_description']);
        self::requireOpenGraphField($issues, $meta, 'og:image', ['openGraphImage', 'open_graph_image']);
    }

    /** @param list<SeoValidationIssueDTO> $issues @param array<string, mixed>|object $meta */
    private static function validateTwitter(array &$issues, array|object $meta): void
    {
        $twitter = self::value($meta, ['twitter']);
        $hasTwitter = is_array($twitter) || is_object($twitter) || self::value($meta, ['twitterCard', 'twitter_card']) !== null || self::value($meta, ['twitterTitle', 'twitter_title']) !== null || self::value($meta, ['twitterDescription', 'twitter_description']) !== null;
        if (!$hasTwitter) {
            return;
        }
        if (is_array($twitter) || is_object($twitter)) {
            self::requireTwitterField($issues, $twitter, 'card', ['card', 'twitterCard', 'twitter_card']);
            self::requireTwitterField($issues, $twitter, 'title', ['title', 'twitterTitle', 'twitter_title']);
            self::requireTwitterField($issues, $twitter, 'description', ['description', 'twitterDescription', 'twitter_description']);
            return;
        }

        self::requireTwitterField($issues, $meta, 'card', ['twitterCard', 'twitter_card']);
        self::requireTwitterField($issues, $meta, 'title', ['twitterTitle', 'twitter_title']);
        self::requireTwitterField($issues, $meta, 'description', ['twitterDescription', 'twitter_description']);
    }


    /** @param list<SeoValidationIssueDTO> $issues @param array<string, mixed>|object $source @param list<string> $fieldNames */
    private static function requireOpenGraphField(array &$issues, array|object $source, string $field, array $fieldNames): void
    {
        $value = self::stringValue(self::value($source, $fieldNames));
        if ($value === null || $value === '') {
            $issues[] = self::issue('missing_' . str_replace(':', '_', $field), 'warning', $field . ' is recommended when OpenGraph data is provided.', $field);
        }
    }

    /** @param list<SeoValidationIssueDTO> $issues @param array<string, mixed>|object $source @param list<string> $fieldNames */
    private static function requireTwitterField(array &$issues, array|object $source, string $field, array $fieldNames): void
    {
        $value = self::stringValue(self::value($source, $fieldNames));
        if ($value === null || $value === '') {
            $issues[] = self::issue('missing_twitter_' . $field, 'warning', 'Twitter ' . $field . ' is recommended when Twitter data is provided.', 'twitter.' . $field);
        }
    }

    /** @param list<SeoValidationIssueDTO> $issues */
    private static function validateJsonLd(array &$issues, mixed $jsonLd): void
    {
        if ($jsonLd === null) {
            return;
        }
        if (!is_array($jsonLd) || $jsonLd === []) {
            $issues[] = self::issue('invalid_json_ld', 'warning', 'JSON-LD schema data should be a non-empty array.', 'jsonLd');
            return;
        }
        if (!array_is_list($jsonLd)) {
            return;
        }

        foreach ($jsonLd as $key => $schema) {
            if (!is_array($schema) || $schema === []) {
                $issues[] = self::issue('invalid_json_ld_schema', 'warning', 'JSON-LD schema entries should be non-empty arrays.', 'jsonLd.' . $key);
            }
        }
    }

    private static function issue(string $code, string $severity, string $message, ?string $field): SeoValidationIssueDTO
    {
        return new SeoValidationIssueDTO($code, $severity, $message, $field);
    }
}
