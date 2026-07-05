<?php

declare(strict_types=1);

namespace Maatify\Seo\Admin\DTO;

final readonly class SocialPreviewDTO implements \JsonSerializable
{
    /** @param list<string> $warnings */
    public function __construct(
        public string $title,
        public ?string $description,
        public ?string $imageUrl,
        public ?string $url,
        public string $type = 'website',
        public ?string $siteName = null,
        public ?string $twitterCard = null,
        public array $warnings = [],
    ) {
    }

    /** @return array{title:string, description:?string, image_url:?string, url:?string, type:string, site_name:?string, twitter_card:?string, warnings:list<string>} */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    /** @return array{title:string, description:?string, image_url:?string, url:?string, type:string, site_name:?string, twitter_card:?string, warnings:list<string>} */
    public function jsonSerialize(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'image_url' => $this->imageUrl,
            'url' => $this->url,
            'type' => $this->type,
            'site_name' => $this->siteName,
            'twitter_card' => $this->twitterCard,
            'warnings' => $this->warnings,
        ];
    }
}
