<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Robots\DTO;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class RobotsTxtDTO
{
    /**
     * @param list<RobotsRuleDTO> $rules
     * @param list<string> $sitemaps
     * @param list<string> $comments
     */
    public function __construct(
        public array $rules = [],
        public array $sitemaps = [],
        public array $comments = [],
    ) {
        foreach ($this->sitemaps as $index => $sitemapUrl) {
            self::assertSafeValue($sitemapUrl, "sitemap {$index}");
            if (filter_var($sitemapUrl, FILTER_VALIDATE_URL) === false) {
                throw SeoInvalidArgumentException::invalidUrl($sitemapUrl);
            }
        }

        foreach ($this->comments as $index => $comment) {
            self::assertSafeValue($comment, "comment {$index}");
        }
    }

    private static function assertSafeValue(string $value, string $field): void
    {
        if (preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            throw SeoInvalidArgumentException::invalidValue($field, 'Control characters are not allowed.');
        }
    }
}
