<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\Input\Hreflang;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class HreflangValidationPageDTO
{
    /**
     * @param list<HreflangValidationLinkDTO> $links
     * @phpstan-param array<int|string, mixed> $links
     */
    public function __construct(
        public string $pageUrl,
        public array $links,
    ) {
        if ($this->pageUrl === '') {
            throw SeoInvalidArgumentException::emptyField('pageUrl');
        }

        if (!array_is_list($this->links)) {
            throw SeoInvalidArgumentException::invalidValue('links', 'Expected a list of HreflangValidationLinkDTO objects.');
        }

        foreach ($this->links as $index => $link) {
            if (!$link instanceof HreflangValidationLinkDTO) {
                throw SeoInvalidArgumentException::invalidValue("links.{$index}", 'Expected a HreflangValidationLinkDTO.');
            }
        }
    }
}
