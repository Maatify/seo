<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Social;

final class SocialImage
{
    private ?string $secureUrl = null;
    private ?string $type = null;
    private ?int $width = null;
    private ?int $height = null;
    private ?string $alt = null;

    public function __construct(
        private readonly string $url,
    ) {
    }

    public function setSecureUrl(string $secureUrl): static
    {
        $this->secureUrl = $secureUrl;

        return $this;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function setWidth(int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function setHeight(int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function setAlt(string $alt): static
    {
        $this->alt = $alt;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getSecureUrl(): ?string
    {
        return $this->secureUrl;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function getAlt(): ?string
    {
        return $this->alt;
    }

    /**
     * @return array{url: string, secure_url?: string, type?: string, width?: int, height?: int, alt?: string}
     */
    public function toArray(): array
    {
        $image = ['url' => $this->url];

        if ($this->secureUrl !== null) {
            $image['secure_url'] = $this->secureUrl;
        }

        if ($this->type !== null) {
            $image['type'] = $this->type;
        }

        if ($this->width !== null) {
            $image['width'] = $this->width;
        }

        if ($this->height !== null) {
            $image['height'] = $this->height;
        }

        if ($this->alt !== null) {
            $image['alt'] = $this->alt;
        }

        return $image;
    }
}
