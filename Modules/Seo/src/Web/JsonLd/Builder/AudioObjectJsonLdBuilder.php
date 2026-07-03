<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

final class AudioObjectJsonLdBuilder extends AbstractJsonLdBuilder
{
    public function __construct()
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => 'AudioObject',
        ]);
    }

    public function setName(string $name): static { return $this->set('name', $name); }
    public function setDescription(string $description): static { return $this->set('description', $description); }
    public function setContentUrl(string $contentUrl): static { return $this->set('contentUrl', $contentUrl); }
    public function setEmbedUrl(string $embedUrl): static { return $this->set('embedUrl', $embedUrl); }
    public function setUploadDate(string $uploadDate): static { return $this->set('uploadDate', $uploadDate); }
    public function setDuration(string $duration): static { return $this->set('duration', $duration); }
    public function setTranscript(string $transcript): static { return $this->set('transcript', $transcript); }
    public function setEncodingFormat(string $encodingFormat): static { return $this->set('encodingFormat', $encodingFormat); }
    /** @param string|array<string, mixed> $creator */
    public function setCreator(string|array $creator): static { return $this->set('creator', $this->normalizeTypedValue($creator, 'Person')); }
    /** @param string|array<string, mixed> $publisher */
    public function setPublisher(string|array $publisher): static { return $this->set('publisher', $this->normalizeTypedValue($publisher, 'Organization')); }

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
