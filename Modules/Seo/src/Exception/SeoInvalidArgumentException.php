<?php

declare(strict_types=1);

namespace Maatify\Seo\Exception;

final class SeoInvalidArgumentException extends \RuntimeException implements SeoExceptionInterface
{
    public static function emptyField(string $field): self
    {
        return new self("Field [{$field}] must not be empty.", SeoErrorCode::INVALID_EMPTY_FIELD);
    }

    public static function invalidId(string $field): self
    {
        return new self("Field [{$field}] must be a positive integer >= 1.", SeoErrorCode::INVALID_ID);
    }

    public static function invalidHttpStatus(int $status): self
    {
        return new self("HTTP status [{$status}] is invalid for SEO redirects.", SeoErrorCode::INVALID_HTTP_STATUS);
    }

    public static function invalidSchemaEntry(string $field): self
    {
        return new self("Field [{$field}] must be a non-empty associative JSON-LD schema array or JsonLdSchemaDTO.", SeoErrorCode::INVALID_EMPTY_FIELD);
    }

    public static function invalidUrl(string $url): self
    {
        return new self("URL [{$url}] is invalid.", SeoErrorCode::INVALID_URL);
    }

    public static function invalidValue(string $field, string $reason): self
    {
        return new self("Field [{$field}] is invalid: {$reason}", SeoErrorCode::INVALID_VALUE);
    }
}
