<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Social;

final class OpenGraphBuilder
{
    private ?string $title = null;
    private ?string $description = null;
    private ?string $type = null;
    private ?string $url = null;
    private ?string $siteName = null;
    private ?string $locale = null;
    private ?string $determiner = null;
    private ?string $audio = null;
    private ?string $video = null;

    /** @var list<SocialImage> */
    private array $images = [];

    public function __construct()
    {
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

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function setSiteName(string $siteName): static
    {
        $this->siteName = $siteName;

        return $this;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function setDeterminer(string $determiner): static
    {
        $this->determiner = $determiner;

        return $this;
    }

    public function setAudio(string $audio): static
    {
        $this->audio = $audio;

        return $this;
    }

    public function setVideo(string $video): static
    {
        $this->video = $video;

        return $this;
    }

    public function setImage(string|SocialImage $image): static
    {
        $this->images = [$this->normalizeImage($image)];

        return $this;
    }

    public function addImage(string|SocialImage $image): static
    {
        $this->images[] = $this->normalizeImage($image);

        return $this;
    }

    /**
     * @param list<string|SocialImage> $images
     */
    public function setImages(array $images): static
    {
        $this->images = [];

        foreach ($images as $image) {
            $this->images[] = $this->normalizeImage($image);
        }

        return $this;
    }

    public function toCollection(): SocialMetaCollection
    {
        $collection = new SocialMetaCollection();

        $this->addScalarTags($collection);
        $this->addImageTags($collection);

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

    private function addScalarTags(SocialMetaCollection $collection): void
    {
        $this->addTagWhenPresent($collection, 'og:title', $this->title);
        $this->addTagWhenPresent($collection, 'og:description', $this->description);
        $this->addTagWhenPresent($collection, 'og:type', $this->type);
        $this->addTagWhenPresent($collection, 'og:url', $this->url);
        $this->addTagWhenPresent($collection, 'og:site_name', $this->siteName);
        $this->addTagWhenPresent($collection, 'og:locale', $this->locale);
        $this->addTagWhenPresent($collection, 'og:determiner', $this->determiner);
        $this->addTagWhenPresent($collection, 'og:audio', $this->audio);
        $this->addTagWhenPresent($collection, 'og:video', $this->video);
    }

    private function addTagWhenPresent(SocialMetaCollection $collection, string $name, ?string $content): void
    {
        if ($content !== null) {
            $collection->add(new SocialMetaTag($name, $content, 'property'));
        }
    }

    private function addImageTags(SocialMetaCollection $collection): void
    {
        foreach ($this->images as $image) {
            $collection->add(new SocialMetaTag('og:image', $image->getUrl(), 'property'));
            $this->addTagWhenPresent($collection, 'og:image:secure_url', $image->getSecureUrl());
            $this->addTagWhenPresent($collection, 'og:image:type', $image->getType());

            if ($image->getWidth() !== null) {
                $collection->add(new SocialMetaTag('og:image:width', (string) $image->getWidth(), 'property'));
            }

            if ($image->getHeight() !== null) {
                $collection->add(new SocialMetaTag('og:image:height', (string) $image->getHeight(), 'property'));
            }

            $this->addTagWhenPresent($collection, 'og:image:alt', $image->getAlt());
        }
    }
}
