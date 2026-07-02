<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

use Maatify\Seo\Exception\SeoExceptionInterface;

final class JsonLdBuildException extends \RuntimeException implements SeoExceptionInterface
{
    public static function encodingFailed(\JsonException $exception): self
    {
        return new self(
            'JSON-LD schema encoding failed: ' . $exception->getMessage(),
            $exception->getCode(),
            $exception
        );
    }
}
