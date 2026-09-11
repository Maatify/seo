<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\Service\Internal;

final class HreflangTagNormalizer
{
    public static function normalize(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (strcasecmp($value, 'x-default') === 0) {
            return 'x-default';
        }

        if (preg_match('/\A([A-Za-z]{2,3})(?:-([A-Za-z]{4})(?:-([A-Za-z]{2}|[0-9]{3}))?|-([A-Za-z]{2}|[0-9]{3}))?\z/', $value, $matches) === 1) {
            $normalized = strtolower($matches[1]);
            $script = $matches[2] ?? '';
            $region = ($matches[3] ?? '') !== '' ? $matches[3] : ($matches[4] ?? '');

            if ($script !== '') {
                $normalized .= '-' . ucfirst(strtolower($script));
            }
            if ($region !== '') {
                $normalized .= '-' . (ctype_digit($region) ? $region : strtoupper($region));
            }

            return $normalized;
        }

        // Preserve the historical strict Web acceptance surface for values
        // outside the fixed candidate grammar while sharing normalization.
        $parts = preg_split('/[-_]+/', $value) ?: [];
        $normalized = [];
        foreach ($parts as $index => $part) {
            if ($part === '') {
                continue;
            }

            $normalized[] = $index === 0 ? strtolower($part) : strtoupper($part);
        }

        return implode('-', $normalized);
    }

    public static function isValidSyntax(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        return preg_match('/\Ax-default\z/i', $value) === 1
            || preg_match('/\A[A-Za-z]{2,3}(?:-[A-Za-z]{4}(?:-[A-Za-z]{2}|-[0-9]{3})?|-([A-Za-z]{2}|[0-9]{3}))?\z/', $value) === 1;
    }

    public static function isStrictDomainCompatible(string $value): bool
    {
        $value = strtolower(trim($value));

        return $value === 'x-default' || preg_match('/\A[a-z]{2,3}(?:-[a-z0-9]{2,8})*\z/', $value) === 1;
    }
}
