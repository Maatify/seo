<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

final class VideoObjectJsonLdBuilder extends AbstractJsonLdBuilder
{
    public function __construct()
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => 'VideoObject',
        ]);
    }

    public function setName(string $name): static { return $this->set('name', $name); }
    public function setDescription(string $description): static { return $this->set('description', $description); }
    /** @param string|array<int, string> $thumbnailUrl */
    public function setThumbnailUrl(string|array $thumbnailUrl): static { return $this->set('thumbnailUrl', $thumbnailUrl); }
    public function setUploadDate(string $uploadDate): static { return $this->set('uploadDate', $uploadDate); }
    public function setDuration(string $duration): static { return $this->set('duration', $duration); }
    public function setContentUrl(string $contentUrl): static { return $this->set('contentUrl', $contentUrl); }
    public function setEmbedUrl(string $embedUrl): static { return $this->set('embedUrl', $embedUrl); }
    /** @param string|array<string, mixed> $publisher */
    public function setPublisher(string|array $publisher): static { return $this->set('publisher', $this->normalizeTypedValue($publisher, 'Organization')); }
    public function setTranscript(string $transcript): static { return $this->set('transcript', $transcript); }
    /** @param array<int, string> $regionsAllowed */
    public function setRegionsAllowed(array $regionsAllowed): static { return $this->set('regionsAllowed', $regionsAllowed); }

    public function addRegionAllowed(string $regionCode): static
    {
        $regionsAllowed = $this->get('regionsAllowed');
        if (!is_array($regionsAllowed)) {
            $regionsAllowed = [];
        }

        /** @var array<int, string> $normalizedRegionsAllowed */
        $normalizedRegionsAllowed = array_values(array_filter($regionsAllowed, 'is_string'));
        $normalizedRegionsAllowed[] = $regionCode;

        return $this->setRegionsAllowed($normalizedRegionsAllowed);
    }

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
