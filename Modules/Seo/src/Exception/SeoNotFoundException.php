<?php

declare(strict_types=1);

namespace Maatify\Seo\Exception;

final class SeoNotFoundException extends \RuntimeException implements SeoExceptionInterface
{
    public static function withId(int|string $id): self
    {
        return new self("Seo record with id [{$id}] not found.", SeoErrorCode::NOT_FOUND_BY_ID);
    }

    public static function withCode(string $code): self
    {
        return new self("Seo record with code [{$code}] not found.", SeoErrorCode::NOT_FOUND_BY_CODE);
    }
}
