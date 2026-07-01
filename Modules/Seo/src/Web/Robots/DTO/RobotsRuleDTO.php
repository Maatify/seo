<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Robots\DTO;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class RobotsRuleDTO
{
    /**
     * @param string $userAgent
     * @param list<string> $allow
     * @param list<string> $disallow
     * @param int|float|null $crawlDelay
     * @param list<string> $comments
     */
    public function __construct(
        public string $userAgent,
        public array $allow = [],
        public array $disallow = [],
        public int|float|null $crawlDelay = null,
        public array $comments = [],
    ) {
        if (trim($this->userAgent) === '') {
            throw SeoInvalidArgumentException::emptyField('userAgent');
        }

        foreach ($this->allow as $path) {
            if (trim($path) === '') {
                throw SeoInvalidArgumentException::emptyField('allow path');
            }
        }

        foreach ($this->disallow as $path) {
            if (trim($path) === '') {
                throw SeoInvalidArgumentException::emptyField('disallow path');
            }
        }

        if ($this->crawlDelay !== null && $this->crawlDelay < 0) {
            throw SeoInvalidArgumentException::invalidValue('crawlDelay', 'must be greater than or equal to 0.');
        }
    }
}
