<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Social;

final readonly class SocialMetaRenderOutput
{
    public function __construct(
        private SocialMetaCollection $collection,
    ) {
    }

    public function getCollection(): SocialMetaCollection
    {
        return $this->collection;
    }

    /**
     * @return list<SocialMetaTag>
     */
    public function getTags(): array
    {
        return $this->collection->all();
    }

    /**
     * @return list<array{name: string, content: string, attribute: string}>
     */
    public function toArray(): array
    {
        return $this->collection->toArray();
    }

    public function toHtml(string $separator = "\n"): string
    {
        return $this->collection->toHtml($separator);
    }

    public function isEmpty(): bool
    {
        return $this->collection->isEmpty();
    }
}
