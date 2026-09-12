<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\Profile\Internal;

use Maatify\Seo\Web\Validation\DTO\SeoCompanionDiagnosticDTO;
use Maatify\Seo\Web\Validation\DTO\SeoCompanionValidationResultDTO;
use Maatify\Seo\Web\Validation\DTO\SeoDiagnosticTargetDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationContextDTO;
use Maatify\Seo\Web\Validation\Input\Sitemap\SitemapValidationLocationDTO;

final class SitemapValidationSupport
{
    /** @param list<SeoCompanionDiagnosticDTO> $diagnostics */
    public static function result(array $diagnostics): SeoCompanionValidationResultDTO
    {
        return new SeoCompanionValidationResultDTO(diagnostics: $diagnostics);
    }

    public static function diagnostic(
        string $code,
        string $severity,
        string $message,
        ?string $field,
        string $targetScope,
        ?int $entryIndex = null,
        ?int $itemIndex = null,
        ?string $evidenceState = null,
    ): SeoCompanionDiagnosticDTO {
        return new SeoCompanionDiagnosticDTO(
            code: $code,
            severity: $severity,
            message: $message,
            field: $field,
            origin: $evidenceState === null ? self::originFor($code) : 'provider',
            profile: self::profileFor($code),
            evidenceState: $evidenceState,
            target: new SeoDiagnosticTargetDTO($targetScope, $entryIndex, $itemIndex),
        );
    }

    public static function isMissing(?string $value): bool
    {
        return $value === null || trim($value) === '';
    }

    public static function isAbsoluteUrl(?string $value, bool $fragmentAllowed): bool
    {
        return $value !== null
            && !self::isMissing($value)
            && AbsoluteAuthorityUrlLexicalProfile::accepts($value, $fragmentAllowed);
    }

    /** @return array{scheme: string, host: string, port: int|null, path: string}|null */
    public static function urlComponents(?string $value, bool $fragmentAllowed): ?array
    {
        return $value === null ? null : AbsoluteAuthorityUrlLexicalProfile::components($value, $fragmentAllowed);
    }

    public static function isSitemapLastmod(string $value): bool
    {
        if (preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $value) === 1) {
            $parts = explode('-', $value);

            return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
        }

        if (preg_match('/\A(\d{4}-\d{2}-\d{2})T(\d{2}):(\d{2}):(\d{2})(?:\.\d+)?(Z|[+-]\d{2}:\d{2})\z/', $value, $matches) !== 1) {
            return false;
        }

        if (!self::isValidTimezone((string) $matches[5])) {
            return false;
        }

        $dateParts = explode('-', $matches[1]);
        if (!checkdate((int) $dateParts[1], (int) $dateParts[2], (int) $dateParts[0])) {
            return false;
        }

        $timezone = $matches[5] === 'Z' ? '+00:00' : $matches[5];
        $parsed = \DateTimeImmutable::createFromFormat(
            '!Y-m-d\\TH:i:sP',
            $matches[1] . 'T' . $matches[2] . ':' . $matches[3] . ':' . $matches[4] . $timezone,
        );
        $errors = \DateTimeImmutable::getLastErrors();

        return $parsed instanceof \DateTimeImmutable
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0));
    }

    public static function isVideoPublicationDate(string $value): bool
    {
        return self::isSitemapDate($value, false);
    }

    public static function isNewsPublicationDate(string $value): bool
    {
        return self::isSitemapDate($value, true);
    }

    public static function isNewsLanguage(string $value): bool
    {
        return preg_match('/\A[A-Za-z]{2,3}\z/', $value) === 1 || in_array($value, ['zh-cn', 'zh-tw'], true);
    }

    /** @param array{scheme: string, host: string, port: int|null, path: string} $candidate */
    public static function isSameAuthority(
        array $candidate,
        SitemapValidationLocationDTO $location,
    ): bool {
        return self::asciiEqual($candidate['scheme'], $location->scheme)
            && self::asciiEqual($candidate['host'], $location->host)
            && self::effectivePort($candidate['scheme'], $candidate['port']) === self::effectivePort($location->scheme, $location->port);
    }

    /** @param array{scheme: string, host: string, port: int|null, path: string} $candidate */
    public static function isWithinUrlScope(
        array $candidate,
        SitemapValidationLocationDTO $location,
    ): bool {
        if (!self::isSameAuthority($candidate, $location)) {
            return false;
        }

        $lastSlash = strrpos($location->path, '/');
        $prefix = $lastSlash === false ? '/' : substr($location->path, 0, $lastSlash + 1);
        $candidatePath = $candidate['path'] === '' ? '/' : $candidate['path'];

        return str_starts_with($candidatePath, $prefix);
    }

    public static function crossSubmissionState(?SeoValidationContextDTO $context, int $entryIndex): string
    {
        $evidence = $context?->evidence['sitemaps.cross_submission_authority'] ?? null;

        return is_array($evidence) && isset($evidence[$entryIndex]) && is_string($evidence[$entryIndex])
            ? $evidence[$entryIndex]
            : 'unknown';
    }

    public static function indexedEvidence(
        ?SeoValidationContextDTO $context,
        string $key,
        int $entryIndex,
        ?int $itemIndex = null,
    ): string {
        $evidence = $context?->evidence[$key] ?? null;
        if (!is_array($evidence) || !array_key_exists($entryIndex, $evidence)) {
            return 'unknown';
        }

        $entryEvidence = $evidence[$entryIndex];
        if ($itemIndex === null) {
            return is_string($entryEvidence) ? $entryEvidence : 'unknown';
        }

        return is_array($entryEvidence) && array_key_exists($itemIndex, $entryEvidence) && is_string($entryEvidence[$itemIndex])
            ? $entryEvidence[$itemIndex]
            : 'unknown';
    }

    public static function scalarEvidence(?SeoValidationContextDTO $context, string $key): string
    {
        $evidence = $context?->evidence[$key] ?? null;

        return is_string($evidence) ? $evidence : 'unknown';
    }

    private static function effectivePort(string $scheme, ?int $port): ?int
    {
        if ($port !== null) {
            return $port;
        }

        return self::asciiEqual($scheme, 'http') ? 80 : (self::asciiEqual($scheme, 'https') ? 443 : null);
    }

    private static function asciiEqual(string $left, string $right): bool
    {
        if (strlen($left) !== strlen($right)) {
            return false;
        }

        for ($index = 0, $length = strlen($left); $index < $length; $index++) {
            $leftByte = ord($left[$index]);
            $rightByte = ord($right[$index]);
            if ($leftByte >= 65 && $leftByte <= 90) {
                $leftByte += 32;
            }
            if ($rightByte >= 65 && $rightByte <= 90) {
                $rightByte += 32;
            }
            if ($leftByte !== $rightByte) {
                return false;
            }
        }

        return true;
    }

    private static function isSitemapDate(string $value, bool $news): bool
    {
        if (preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $value) === 1) {
            $parts = explode('-', $value);

            return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]);
        }

        $timePattern = $news
            ? '(?:T\d{2}:\d{2}(?::\d{2}(?:\.\d+)?)?)'
            : 'T\d{2}:\d{2}:\d{2}';
        if (preg_match('/\A(\d{4}-\d{2}-\d{2})' . $timePattern . '(Z|[+-]\d{2}:\d{2})\z/', $value, $matches) !== 1) {
            return false;
        }

        $dateParts = explode('-', $matches[1]);
        if (!checkdate((int) $dateParts[1], (int) $dateParts[2], (int) $dateParts[0])) {
            return false;
        }

        $normalized = preg_replace('/\.\d+/', '', $value);
        if (!is_string($normalized)) {
            return false;
        }
        $hasZuluTimezone = str_ends_with($normalized, 'Z');
        $timezoneToken = $hasZuluTimezone ? 'Z' : substr($normalized, -6);
        if (!self::isValidTimezone($timezoneToken)) {
            return false;
        }
        $timezone = $hasZuluTimezone ? '+00:00' : $timezoneToken;
        $base = substr($normalized, 0, -($hasZuluTimezone ? 1 : strlen($timezone)));
        if ($news && preg_match('/T\d{2}:\d{2}(Z|[+-]\d{2}:\d{2})\z/', $normalized) === 1) {
            $base .= ':00';
        }
        $parsed = \DateTimeImmutable::createFromFormat(
            '!Y-m-d\\TH:i:sP',
            $base . $timezone,
        );
        $errors = \DateTimeImmutable::getLastErrors();

        return $parsed instanceof \DateTimeImmutable
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0));
    }

    private static function originFor(string $code): string
    {
        return str_starts_with($code, 'sitemap_') ? 'protocol' : 'provider';
    }

    private static function profileFor(string $code): string
    {
        return str_starts_with($code, 'sitemap_') ? 'sitemaps' : 'google';
    }

    private static function isValidTimezone(string $timezone): bool
    {
        if ($timezone === 'Z') {
            return true;
        }

        if (preg_match('/\A[+-](\d{2}):(\d{2})\z/', $timezone, $matches) !== 1) {
            return false;
        }

        return (int) $matches[1] <= 23 && (int) $matches[2] <= 59;
    }
}
