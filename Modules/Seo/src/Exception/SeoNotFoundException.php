<?php

declare(strict_types=1);

namespace Maatify\Seo\Exception;

final class SeoNotFoundException extends \RuntimeException implements SeoExceptionInterface
{
    public static function withId(int|string $id): self
    {
        return new self("Seo record with id [{$id}] not found.");
    }

    public static function withCode(string $code): self
    {
        return new self("Seo record with code [{$code}] not found.");
    }
}
