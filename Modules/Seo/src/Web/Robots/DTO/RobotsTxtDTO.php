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
        foreach ($this->sitemaps as $sitemapUrl) {
            if (filter_var($sitemapUrl, FILTER_VALIDATE_URL) === false) {
                throw SeoInvalidArgumentException::invalidUrl($sitemapUrl);
            }
        }
    }
}
