<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\Input;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class RobotsMetaValidationInputDTO
{
    /**
     * @param list<string> $directives
     */
    public function __construct(
        public array $directives,
    ) {
        self::assertDirectives($this->directives);
    }

    private static function assertDirectives(mixed $directives): void
    {
        if (!is_array($directives) || !array_is_list($directives)) {
            throw SeoInvalidArgumentException::invalidValue('directives', 'Expected a list of strings.');
        }

        foreach ($directives as $index => $directive) {
            if (!is_string($directive)) {
                throw SeoInvalidArgumentException::invalidValue("directives.{$index}", 'Expected a string.');
            }
        }
    }
}
