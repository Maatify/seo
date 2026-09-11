<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\Profile\Internal;

final class AbsoluteAuthorityUrlLexicalProfile
{
    public static function accepts(string $value, bool $fragmentAllowed): bool
    {
        if (preg_match('//u', $value) !== 1) {
            return false;
        }

        if (preg_match('/[\x00-\x20\x7F]/', $value) === 1 || str_contains($value, '\\')) {
            return false;
        }

        if (preg_match('/%(?![0-9A-Fa-f]{2})/', $value) === 1) {
            return false;
        }

        $schemeSeparator = strpos($value, '://');
        if ($schemeSeparator === false) {
            return false;
        }

        $scheme = substr($value, 0, $schemeSeparator);
        if (preg_match('/\A[A-Za-z][A-Za-z0-9+.-]*\z/', $scheme) !== 1) {
            return false;
        }

        $remainder = substr($value, $schemeSeparator + 3);
        if ($remainder === '') {
            return false;
        }

        $authorityEnd = strlen($remainder);
        foreach (['/', '?', '#'] as $delimiter) {
            $position = strpos($remainder, $delimiter);
            if ($position !== false && $position < $authorityEnd) {
                $authorityEnd = $position;
            }
        }

        $authority = substr($remainder, 0, $authorityEnd);
        if (!self::acceptsAuthority($authority)) {
            return false;
        }

        $suffix = substr($remainder, $authorityEnd);
        $fragmentPosition = strpos($suffix, '#');
        if ($fragmentPosition !== false) {
            if (!$fragmentAllowed) {
                return false;
            }

            $fragment = substr($suffix, $fragmentPosition + 1);
            $suffix = substr($suffix, 0, $fragmentPosition);
            if (!self::acceptsQueryOrFragment($fragment)) {
                return false;
            }
        }

        $queryPosition = strpos($suffix, '?');
        if ($queryPosition !== false) {
            $path = substr($suffix, 0, $queryPosition);
            $query = substr($suffix, $queryPosition + 1);
            return self::acceptsPath($path) && self::acceptsQueryOrFragment($query);
        }

        return self::acceptsPath($suffix);
    }

    private static function acceptsAuthority(string $authority): bool
    {
        if ($authority === '') {
            return false;
        }

        if (substr_count($authority, '@') > 1) {
            return false;
        }

        $atPosition = strpos($authority, '@');
        if ($atPosition !== false) {
            $userinfo = substr($authority, 0, $atPosition);
            $authority = substr($authority, $atPosition + 1);
            if ($userinfo === '' || !self::acceptsUserinfo($userinfo)) {
                return false;
            }
        }

        if ($authority === '') {
            return false;
        }

        if (str_starts_with($authority, '[')) {
            $closingBracket = strpos($authority, ']');
            if ($closingBracket === false || $closingBracket === 1) {
                return false;
            }

            $bracketHost = substr($authority, 1, $closingBracket - 1);
            if (str_contains($bracketHost, '[') || str_contains($bracketHost, ']') || !self::acceptsBracketHost($bracketHost)) {
                return false;
            }

            $suffix = substr($authority, $closingBracket + 1);
            return self::acceptsPortSuffix($suffix);
        }

        $colonPosition = strpos($authority, ':');
        if ($colonPosition === false) {
            return self::acceptsHost($authority);
        }

        $host = substr($authority, 0, $colonPosition);
        $port = substr($authority, $colonPosition + 1);

        return self::acceptsHost($host) && self::acceptsPort($port);
    }

    private static function acceptsPortSuffix(string $suffix): bool
    {
        if ($suffix === '') {
            return true;
        }

        if (!str_starts_with($suffix, ':')) {
            return false;
        }

        return self::acceptsPort(substr($suffix, 1));
    }

    private static function acceptsPort(string $port): bool
    {
        return preg_match('/\A[0-9]+\z/', $port) === 1 && (int) $port <= 65535;
    }

    private static function acceptsUserinfo(string $userinfo): bool
    {
        return self::matches(
            $userinfo,
            "A-Za-z0-9\\-._~!$&'()*+,;=:"
        );
    }

    private static function acceptsHost(string $host): bool
    {
        return $host !== '' && self::matches(
            $host,
            "A-Za-z0-9\\-._~!$&'()*+,;="
        );
    }

    private static function acceptsBracketHost(string $host): bool
    {
        return self::matchesAsciiAndPercent(
            $host,
            "A-Za-z0-9:._~!$&'()*+,;=\\-"
        );
    }

    private static function acceptsPath(string $path): bool
    {
        return self::matches(
            $path,
            "A-Za-z0-9\\-._~!$&'()*+,;=:@/"
        );
    }

    private static function acceptsQueryOrFragment(string $value): bool
    {
        return self::matches(
            $value,
            "A-Za-z0-9\\-._~!$&'()*+,;=:@/?"
        );
    }

    private static function matches(string $value, string $asciiCharacterClass): bool
    {
        return preg_match('#\A(?:[' . $asciiCharacterClass . ']|%[0-9A-Fa-f]{2}|[^\x00-\x7F])*\z#u', $value) === 1;
    }

    private static function matchesAsciiAndPercent(string $value, string $asciiCharacterClass): bool
    {
        return preg_match('#\A(?:[' . $asciiCharacterClass . ']|%[0-9A-Fa-f]{2})*\z#', $value) === 1;
    }
}
