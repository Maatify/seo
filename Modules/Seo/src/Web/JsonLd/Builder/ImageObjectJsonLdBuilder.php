<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

final class ImageObjectJsonLdBuilder extends AbstractJsonLdBuilder
{
    public function __construct()
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => 'ImageObject',
        ]);
    }

    public function setContentUrl(string $contentUrl): static { return $this->set('contentUrl', $contentUrl); }
    public function setUrl(string $url): static { return $this->set('url', $url); }
    public function setName(string $name): static { return $this->set('name', $name); }
    public function setDescription(string $description): static { return $this->set('description', $description); }
    public function setCaption(string $caption): static { return $this->set('caption', $caption); }
    public function setThumbnailUrl(string $thumbnailUrl): static { return $this->set('thumbnailUrl', $thumbnailUrl); }
    public function setWidth(int|string $width): static { return $this->set('width', $width); }
    public function setHeight(int|string $height): static { return $this->set('height', $height); }
    public function setUploadDate(string $uploadDate): static { return $this->set('uploadDate', $uploadDate); }
    /** @param string|array<string, mixed> $creator */
    public function setCreator(string|array $creator): static { return $this->set('creator', $this->normalizeTypedValue($creator, 'Person')); }
    /** @param string|array<string, mixed> $copyrightHolder */
    public function setCopyrightHolder(string|array $copyrightHolder): static { return $this->set('copyrightHolder', $this->normalizeTypedValue($copyrightHolder, 'Organization')); }
    public function setLicense(string $license): static { return $this->set('license', $license); }

    /**
     * @param string|array<string, mixed> $value
     * @return array<string, mixed>
     */
    private function normalizeTypedValue(string|array $value, string $type): array
    {
        if (is_string($value)) {
            return ['@type' => $type, 'name' => $value];
        }

        if (!isset($value['@type'])) {
            $value['@type'] = $type;
        }

        return $value;
    }
}
