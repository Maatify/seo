<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\DTO;

final readonly class MetaTagsDTO implements \JsonSerializable
{
    public function __construct(
        public string $title,
        public ?string $description,
        public ?string $canonicalUrl,
        public string $robots = 'index,follow',
        public ?string $openGraphTitle = null,
        public ?string $openGraphDescription = null,
        public ?string $openGraphUrl = null,
        public ?string $twitterTitle = null,
        public ?string $twitterDescription = null,
        public ?string $openGraphType = null,
        public ?string $openGraphImage = null,
        public ?string $twitterCard = null,
        public ?string $twitterImage = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'canonical_url' => $this->canonicalUrl,
            'robots' => $this->robots,
            'open_graph_title' => $this->openGraphTitle,
            'open_graph_description' => $this->openGraphDescription,
            'open_graph_url' => $this->openGraphUrl,
            'twitter_title' => $this->twitterTitle,
            'twitter_description' => $this->twitterDescription,
            'open_graph_type' => $this->openGraphType,
            'open_graph_image' => $this->openGraphImage,
            'twitter_card' => $this->twitterCard,
            'twitter_image' => $this->twitterImage,
        ];
    }
}
