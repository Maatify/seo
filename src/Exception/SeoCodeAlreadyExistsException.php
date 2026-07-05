<?php

declare(strict_types=1);

namespace Maatify\Seo\Exception;

final class SeoCodeAlreadyExistsException extends \RuntimeException implements SeoExceptionInterface
{
    public static function forCode(string $code): self
    {
        return new self("Seo record with code [{$code}] already exists.", SeoErrorCode::CODE_ALREADY_EXISTS);
    }

    public static function forUniqueKey(string $key): self
    {
        return new self("Seo record with unique key [{$key}] already exists.", SeoErrorCode::CODE_ALREADY_EXISTS);
    }
}
