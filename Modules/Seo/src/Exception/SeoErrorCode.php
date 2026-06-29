<?php

declare(strict_types=1);

namespace Maatify\Seo\Exception;

final class SeoErrorCode
{
    public const INVALID_EMPTY_FIELD = 1001;
    public const INVALID_ID = 1002;
    public const NOT_FOUND_BY_ID = 2001;
    public const NOT_FOUND_BY_CODE = 2002;
    public const CODE_ALREADY_EXISTS = 3001;
    public const CONFLICT_GENERIC = 4001;
}
