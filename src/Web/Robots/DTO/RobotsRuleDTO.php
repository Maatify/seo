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
        self::assertSafeValue($this->userAgent, 'userAgent');

        foreach ($this->allow as $index => $path) {
            if (trim($path) === '') {
                throw SeoInvalidArgumentException::emptyField('allow path');
            }
            self::assertSafeValue($path, "allow path {$index}");
        }

        foreach ($this->disallow as $index => $path) {
            if (trim($path) === '') {
                throw SeoInvalidArgumentException::emptyField('disallow path');
            }
            self::assertSafeValue($path, "disallow path {$index}");
        }

        foreach ($this->comments as $index => $comment) {
            self::assertSafeValue($comment, "comment {$index}");
        }

        if ($this->crawlDelay !== null && $this->crawlDelay < 0) {
            throw SeoInvalidArgumentException::invalidValue('crawlDelay', 'must be greater than or equal to 0.');
        }
    }

    private static function assertSafeValue(string $value, string $field): void
    {
        if (preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            throw SeoInvalidArgumentException::invalidValue($field, 'Control characters are not allowed.');
        }
    }
}
