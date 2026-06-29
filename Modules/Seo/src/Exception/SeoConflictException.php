<?php

declare(strict_types=1);

namespace Maatify\Seo\Exception;

final class SeoConflictException extends \RuntimeException implements SeoExceptionInterface
{
    public static function dueToReason(string $reason): self
    {
        return new self("Seo conflict occurred: {$reason}", SeoErrorCode::CONFLICT_GENERIC);
    }
}
