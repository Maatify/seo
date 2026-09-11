<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\Profile\Internal;

use Maatify\Seo\Web\Validation\DTO\SeoCompanionDiagnosticDTO;
use Maatify\Seo\Web\Validation\DTO\SeoCompanionValidationResultDTO;
use Maatify\Seo\Web\Validation\DTO\SeoDiagnosticTargetDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationContextDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationResultDTO;

final class OpenGraphValidationSupport
{
    /**
     * @var array<string, array{field: string, origin: string, profile: string}>
     */
    private const LEGACY_CLASSIFICATIONS = [
        'title_too_short' => ['field' => 'title', 'origin' => 'heuristic', 'profile' => 'seo-default'],
        'title_too_long' => ['field' => 'title', 'origin' => 'heuristic', 'profile' => 'seo-default'],
        'description_too_short' => ['field' => 'description', 'origin' => 'heuristic', 'profile' => 'seo-default'],
        'description_too_long' => ['field' => 'description', 'origin' => 'heuristic', 'profile' => 'seo-default'],
        'missing_og_title' => ['field' => 'og:title', 'origin' => 'protocol', 'profile' => 'ogp'],
        'missing_og_description' => ['field' => 'og:description', 'origin' => 'heuristic', 'profile' => 'seo-default'],
        'missing_og_image' => ['field' => 'og:image', 'origin' => 'protocol', 'profile' => 'ogp'],
    ];

    /**
     * Build the one shared companion implementation for the legacy/meta OGP path.
     *
     * @param array<string, mixed>|object $meta
     */
    public static function build(
        array|object $meta,
        SeoValidationResultDTO $legacy,
        ?SeoValidationContextDTO $context = null,
    ): SeoCompanionValidationResultDTO {
        unset($context);

        /** @var list<SeoCompanionDiagnosticDTO> $diagnostics */
        $diagnostics = [];
        $classified = [];

        foreach ($legacy->issues as $issue) {
            $classification = self::classificationFor($issue->code);
            if ($classification === null || isset($classified[$issue->code])) {
                continue;
            }

            $classified[$issue->code] = true;
            $diagnostics[] = new SeoCompanionDiagnosticDTO(
                code: $issue->code,
                severity: $issue->severity,
                message: $issue->message,
                field: $classification['field'],
                origin: $classification['origin'],
                profile: $classification['profile'],
                target: new SeoDiagnosticTargetDTO('meta'),
                relatedLegacyCode: $issue->code,
            );
        }

        if (!self::isActive($meta)) {
            return new SeoCompanionValidationResultDTO($legacy, $diagnostics);
        }

        if (self::isMissing(self::openGraphValue($meta, ['og:type', 'type', 'openGraphType', 'open_graph_type']))) {
            $diagnostics[] = new SeoCompanionDiagnosticDTO(
                code: 'missing_og_type',
                severity: 'warning',
                message: 'og:type is required when Open Graph data is provided.',
                field: 'og:type',
                origin: 'protocol',
                profile: 'ogp',
                target: new SeoDiagnosticTargetDTO('meta'),
            );
        }

        if (self::isMissing(self::openGraphValue($meta, ['og:url', 'url', 'openGraphUrl', 'open_graph_url']))) {
            $diagnostics[] = new SeoCompanionDiagnosticDTO(
                code: 'missing_og_url',
                severity: 'warning',
                message: 'og:url is required when Open Graph data is provided.',
                field: 'og:url',
                origin: 'protocol',
                profile: 'ogp',
                target: new SeoDiagnosticTargetDTO('meta'),
            );
        }

        return new SeoCompanionValidationResultDTO($legacy, $diagnostics);
    }

    /** @return array{field: string, origin: string, profile: string}|null */
    private static function classificationFor(string $code): ?array
    {
        return self::LEGACY_CLASSIFICATIONS[$code] ?? null;
    }

    /** @param array<string, mixed>|object $meta */
    private static function isActive(array|object $meta): bool
    {
        $og = self::value($meta, ['openGraph', 'og']);

        return is_array($og)
            || is_object($og)
            || self::value($meta, ['openGraphTitle', 'open_graph_title']) !== null
            || self::value($meta, ['openGraphDescription', 'open_graph_description']) !== null
            || self::value($meta, ['openGraphImage', 'open_graph_image']) !== null;
    }

    /**
     * @param array<string, mixed>|object $meta
     * @param list<string> $names
     */
    private static function openGraphValue(array|object $meta, array $names): mixed
    {
        $og = self::value($meta, ['openGraph', 'og']);
        if (is_array($og) || is_object($og)) {
            return self::value($og, $names);
        }

        return self::value($meta, $names);
    }

    /**
     * @param array<mixed, mixed>|object $source
     * @param list<string> $names
     */
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

    private static function isMissing(mixed $value): bool
    {
        $stringValue = self::stringValue($value);

        return $stringValue === null || $stringValue === '';
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
}
