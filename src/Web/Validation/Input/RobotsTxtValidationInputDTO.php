<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\Input;

final readonly class RobotsTxtValidationInputDTO
{
    public function __construct(
        public string $content,
    ) {
    }
}
