<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\Input;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class RobotsMetaValidationInputDTO
{
    /** @param array<mixed> $directives */
    public function __construct(
        public array $directives,
    ) {
        if (!array_is_list($this->directives)) {
            throw SeoInvalidArgumentException::invalidValue('directives', 'Expected a list of strings.');
        }

        foreach ($this->directives as $index => $directive) {
            if (!is_string($directive)) {
                throw SeoInvalidArgumentException::invalidValue("directives.{$index}", 'Expected a string.');
            }
        }
    }
}
