<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Hreflang;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final class HreflangLinkBuilder
{
    /** @var array<string, HreflangLinkDTO> */
    private array $links = [];

    public function add(string $hreflang, string $url): self
    {
        $normalizedHreflang = HreflangLinkDTO::normalizeHreflang($hreflang);
        if ($normalizedHreflang === '') {
            throw SeoInvalidArgumentException::emptyField('hreflang');
        }

        if (isset($this->links[$normalizedHreflang])) {
            return $this;
        }

        $this->links[$normalizedHreflang] = new HreflangLinkDTO($normalizedHreflang, $url);

        return $this;
    }

    /**
     * @param array<int|string, HreflangLinkDTO|array{hreflang?: mixed, url?: mixed}|string> $links
     */
    public function addMany(array $links): self
    {
        foreach ($links as $key => $link) {
            if ($link instanceof HreflangLinkDTO) {
                $this->add($link->hreflang, $link->url);
                continue;
            }

            if (is_string($key) && is_string($link)) {
                $this->add($key, $link);
                continue;
            }

            if (!is_array($link)) {
                throw SeoInvalidArgumentException::invalidValue('links.' . (string) $key, 'Expected array with hreflang and url values, HreflangLinkDTO, or hreflang => url pair.');
            }

            if (!array_key_exists('hreflang', $link) || !is_string($link['hreflang'])) {
                throw SeoInvalidArgumentException::invalidValue('links.' . (string) $key . '.hreflang', 'Expected a non-empty string.');
            }

            if (!array_key_exists('url', $link) || !is_string($link['url'])) {
                throw SeoInvalidArgumentException::invalidValue('links.' . (string) $key . '.url', 'Expected a non-empty string URL.');
            }

            $this->add($link['hreflang'], $link['url']);
        }

        return $this;
    }

    public function xDefault(string $url): self
    {
        return $this->replace('x-default', $url);
    }

    public function replace(string $hreflang, string $url): self
    {
        $link = new HreflangLinkDTO($hreflang, $url);
        $this->links[$link->hreflang] = $link;

        return $this;
    }

    /**
     * @return list<HreflangLinkDTO>
     */
    public function all(): array
    {
        return array_values($this->links);
    }

    /**
     * @return list<array{hreflang: string, url: string}>
     */
    public function toArray(): array
    {
        return array_map(
            static fn (HreflangLinkDTO $link): array => $link->toArray(),
            $this->all()
        );
    }

    public function render(): string
    {
        return (new HreflangLinkRenderer())->render($this->all());
    }

    public static function normalizeHreflang(string $hreflang): string
    {
        return HreflangLinkDTO::normalizeHreflang($hreflang);
    }
}
