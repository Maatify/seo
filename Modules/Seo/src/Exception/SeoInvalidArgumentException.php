<?php

declare(strict_types=1);

namespace Maatify\Seo\Exception;

final class SeoInvalidArgumentException extends \RuntimeException implements SeoExceptionInterface
{
    public static function emptyField(string $field): self
    {
        return new self("Field [{$field}] must not be empty.");
    }
}
