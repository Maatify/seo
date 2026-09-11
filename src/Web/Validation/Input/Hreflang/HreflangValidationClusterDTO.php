<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\Input\Hreflang;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class HreflangValidationClusterDTO
{
    /**
     * @param list<HreflangValidationPageDTO> $pages
     */
    public function __construct(
        public array $pages,
    ) {
        self::assertPages($this->pages);
    }

    private static function assertPages(mixed $pages): void
    {
        if (!is_array($pages) || !array_is_list($pages)) {
            throw SeoInvalidArgumentException::invalidValue('pages', 'Expected a list of HreflangValidationPageDTO objects.');
        }

        $seenPageUrls = [];
        foreach ($pages as $index => $page) {
            if (!$page instanceof HreflangValidationPageDTO) {
                throw SeoInvalidArgumentException::invalidValue("pages.{$index}", 'Expected a HreflangValidationPageDTO.');
            }

            $identity = strlen($page->pageUrl) . ':' . $page->pageUrl;
            if (isset($seenPageUrls[$identity])) {
                throw SeoInvalidArgumentException::invalidValue("pages.{$index}.pageUrl", 'Page identities must be unique within the cluster.');
            }

            $seenPageUrls[$identity] = true;
        }
    }
}
