<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\Input\Hreflang;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class HreflangValidationPageDTO
{
    /**
     * @param list<HreflangValidationLinkDTO> $links
     */
    public function __construct(
        public string $pageUrl,
        public array $links,
    ) {
        if ($this->pageUrl === '') {
            throw SeoInvalidArgumentException::emptyField('pageUrl');
        }

        self::assertLinks($this->links);
    }

    private static function assertLinks(mixed $links): void
    {
        if (!is_array($links) || !array_is_list($links)) {
            throw SeoInvalidArgumentException::invalidValue('links', 'Expected a list of HreflangValidationLinkDTO objects.');
        }

        foreach ($links as $index => $link) {
            if (!$link instanceof HreflangValidationLinkDTO) {
                throw SeoInvalidArgumentException::invalidValue("links.{$index}", 'Expected a HreflangValidationLinkDTO.');
            }
        }
    }
}
