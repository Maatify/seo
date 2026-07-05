<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Social;

final class TwitterCardBuilder
{
    private ?string $card = null;
    private ?string $site = null;
    private ?string $creator = null;
    private ?string $title = null;
    private ?string $description = null;
    private ?SocialImage $image = null;
    private ?string $imageAlt = null;
    private bool $imageAltExplicit = false;
    private ?string $player = null;
    private ?int $playerWidth = null;
    private ?int $playerHeight = null;
    private ?string $appNameIphone = null;
    private ?string $appIdIphone = null;
    private ?string $appUrlIphone = null;
    private ?string $appNameIpad = null;
    private ?string $appIdIpad = null;
    private ?string $appUrlIpad = null;
    private ?string $appNameGoogleplay = null;
    private ?string $appIdGoogleplay = null;
    private ?string $appUrlGoogleplay = null;

    public function __construct()
    {
    }

    public function setCard(string $card): static
    {
        $this->card = $card;

        return $this;
    }

    public function setSite(string $site): static
    {
        $this->site = $site;

        return $this;
    }

    public function setCreator(string $creator): static
    {
        $this->creator = $creator;

        return $this;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function setImage(string|SocialImage $image): static
    {
        $this->image = $this->normalizeImage($image);

        if (! $this->imageAltExplicit) {
            $this->imageAlt = $this->image->getAlt();
        }

        return $this;
    }

    public function setImageAlt(string $alt): static
    {
        $this->imageAlt = $alt;
        $this->imageAltExplicit = true;

        return $this;
    }

    public function setPlayer(string $player): static
    {
        $this->player = $player;

        return $this;
    }

    public function setPlayerWidth(int $width): static
    {
        $this->playerWidth = $width;

        return $this;
    }

    public function setPlayerHeight(int $height): static
    {
        $this->playerHeight = $height;

        return $this;
    }

    public function setAppNameIphone(string $name): static
    {
        $this->appNameIphone = $name;

        return $this;
    }

    public function setAppIdIphone(string $id): static
    {
        $this->appIdIphone = $id;

        return $this;
    }

    public function setAppUrlIphone(string $url): static
    {
        $this->appUrlIphone = $url;

        return $this;
    }

    public function setAppNameIpad(string $name): static
    {
        $this->appNameIpad = $name;

        return $this;
    }

    public function setAppIdIpad(string $id): static
    {
        $this->appIdIpad = $id;

        return $this;
    }

    public function setAppUrlIpad(string $url): static
    {
        $this->appUrlIpad = $url;

        return $this;
    }

    public function setAppNameGoogleplay(string $name): static
    {
        $this->appNameGoogleplay = $name;

        return $this;
    }

    public function setAppIdGoogleplay(string $id): static
    {
        $this->appIdGoogleplay = $id;

        return $this;
    }

    public function setAppUrlGoogleplay(string $url): static
    {
        $this->appUrlGoogleplay = $url;

        return $this;
    }

    public function toCollection(): SocialMetaCollection
    {
        $collection = new SocialMetaCollection();

        $this->addTagWhenPresent($collection, 'twitter:card', $this->card);
        $this->addTagWhenPresent($collection, 'twitter:site', $this->site);
        $this->addTagWhenPresent($collection, 'twitter:creator', $this->creator);
        $this->addTagWhenPresent($collection, 'twitter:title', $this->title);
        $this->addTagWhenPresent($collection, 'twitter:description', $this->description);

        if ($this->image !== null) {
            $collection->add(new SocialMetaTag('twitter:image', $this->image->getUrl(), 'name'));
        }

        $this->addTagWhenPresent($collection, 'twitter:image:alt', $this->imageAlt);
        $this->addTagWhenPresent($collection, 'twitter:player', $this->player);

        if ($this->playerWidth !== null) {
            $collection->add(new SocialMetaTag('twitter:player:width', (string) $this->playerWidth, 'name'));
        }

        if ($this->playerHeight !== null) {
            $collection->add(new SocialMetaTag('twitter:player:height', (string) $this->playerHeight, 'name'));
        }

        $this->addTagWhenPresent($collection, 'twitter:app:name:iphone', $this->appNameIphone);
        $this->addTagWhenPresent($collection, 'twitter:app:id:iphone', $this->appIdIphone);
        $this->addTagWhenPresent($collection, 'twitter:app:url:iphone', $this->appUrlIphone);
        $this->addTagWhenPresent($collection, 'twitter:app:name:ipad', $this->appNameIpad);
        $this->addTagWhenPresent($collection, 'twitter:app:id:ipad', $this->appIdIpad);
        $this->addTagWhenPresent($collection, 'twitter:app:url:ipad', $this->appUrlIpad);
        $this->addTagWhenPresent($collection, 'twitter:app:name:googleplay', $this->appNameGoogleplay);
        $this->addTagWhenPresent($collection, 'twitter:app:id:googleplay', $this->appIdGoogleplay);
        $this->addTagWhenPresent($collection, 'twitter:app:url:googleplay', $this->appUrlGoogleplay);

        return $collection;
    }

    public function toRenderOutput(): SocialMetaRenderOutput
    {
        return new SocialMetaRenderOutput($this->toCollection());
    }

    /**
     * @return list<array{name: string, content: string, attribute: string}>
     */
    public function toArray(): array
    {
        return $this->toCollection()->toArray();
    }

    public function toHtml(string $separator = "\n"): string
    {
        return $this->toCollection()->toHtml($separator);
    }

    private function normalizeImage(string|SocialImage $image): SocialImage
    {
        if ($image instanceof SocialImage) {
            return $image;
        }

        return new SocialImage($image);
    }

    private function addTagWhenPresent(SocialMetaCollection $collection, string $name, ?string $content): void
    {
        if ($content !== null) {
            $collection->add(new SocialMetaTag($name, $content, 'name'));
        }
    }
}
