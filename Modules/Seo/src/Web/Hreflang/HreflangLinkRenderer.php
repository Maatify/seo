<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Hreflang;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class HreflangLinkRenderer
{
    /**
     * @param HreflangLinkDTO|array<int, HreflangLinkDTO>|array{hreflang: string, url: string} $links
     */
    public function render(HreflangLinkDTO|array $links): string
    {
        $normalizedLinks = $this->normalizeLinks($links);
        $tags = [];

        foreach ($normalizedLinks as $link) {
            $tags[] = '<link rel="alternate" hreflang="'
                . $this->escapeAttribute($link->hreflang)
                . '" href="'
                . $this->escapeAttribute($link->url)
                . '">';
        }

        return implode("\n", $tags);
    }

    /**
     * @param HreflangLinkDTO|array<int, HreflangLinkDTO>|array{hreflang: string, url: string} $links
     * @return list<HreflangLinkDTO>
     */
    private function normalizeLinks(HreflangLinkDTO|array $links): array
    {
        if ($links instanceof HreflangLinkDTO) {
            return [$links];
        }

        if (isset($links['hreflang'], $links['url'])) {
            /** @var array{hreflang: string, url: string} $links */
            return [new HreflangLinkDTO($links['hreflang'], $links['url'])];
        }

        $normalized = [];
        foreach ($links as $index => $link) {
            if (!$link instanceof HreflangLinkDTO) {
                throw SeoInvalidArgumentException::invalidValue('links.' . (string) $index, 'Expected HreflangLinkDTO.');
            }

            $normalized[] = $link;
        }

        return $normalized;
    }

    private function escapeAttribute(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
