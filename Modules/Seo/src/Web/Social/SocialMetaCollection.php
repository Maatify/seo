<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Social;

final class SocialMetaCollection
{
    /** @var list<SocialMetaTag> */
    private array $tags = [];

    public function add(SocialMetaTag $tag): static
    {
        $this->tags[] = $tag;

        return $this;
    }

    public function addTag(string $name, string $content, string $attribute = 'property'): static
    {
        return $this->add(new SocialMetaTag($name, $content, $attribute));
    }

    /**
     * @return list<SocialMetaTag>
     */
    public function all(): array
    {
        return $this->tags;
    }

    /**
     * @return list<array{name: string, content: string, attribute: string}>
     */
    public function toArray(): array
    {
        return array_map(static fn (SocialMetaTag $tag): array => $tag->toArray(), $this->tags);
    }

    public function toHtml(string $separator = "\n"): string
    {
        return implode($separator, array_map(static fn (SocialMetaTag $tag): string => $tag->toHtml(), $this->tags));
    }

    public function isEmpty(): bool
    {
        return $this->tags === [];
    }

    public function count(): int
    {
        return count($this->tags);
    }
}
