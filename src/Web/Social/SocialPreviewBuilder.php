<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Social;

final class SocialPreviewBuilder
{
    private OpenGraphBuilder $openGraph;
    private TwitterCardBuilder $twitter;

    public function __construct()
    {
        $this->openGraph = new OpenGraphBuilder();
        $this->twitter = new TwitterCardBuilder();
    }

    public function openGraph(): OpenGraphBuilder
    {
        return $this->openGraph;
    }

    public function twitter(): TwitterCardBuilder
    {
        return $this->twitter;
    }

    public function setTitle(string $title): static
    {
        $this->openGraph->setTitle($title);
        $this->twitter->setTitle($title);

        return $this;
    }

    public function setDescription(string $description): static
    {
        $this->openGraph->setDescription($description);
        $this->twitter->setDescription($description);

        return $this;
    }

    public function setUrl(string $url): static
    {
        $this->openGraph->setUrl($url);

        return $this;
    }

    public function setImage(string|SocialImage $image): static
    {
        $this->openGraph->setImage($image);
        $this->twitter->setImage($image);

        return $this;
    }

    public function setSiteName(string $siteName): static
    {
        $this->openGraph->setSiteName($siteName);

        return $this;
    }

    public function setLocale(string $locale): static
    {
        $this->openGraph->setLocale($locale);

        return $this;
    }

    public function setTwitterCard(string $card): static
    {
        $this->twitter->setCard($card);

        return $this;
    }

    public function setTwitterSite(string $site): static
    {
        $this->twitter->setSite($site);

        return $this;
    }

    public function setTwitterCreator(string $creator): static
    {
        $this->twitter->setCreator($creator);

        return $this;
    }

    public function toCollection(): SocialMetaCollection
    {
        $collection = new SocialMetaCollection();

        foreach ($this->openGraph->toCollection()->all() as $tag) {
            $collection->add($tag);
        }

        foreach ($this->twitter->toCollection()->all() as $tag) {
            $collection->add($tag);
        }

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
}
