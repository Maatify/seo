<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

use Maatify\Seo\Web\JsonLd\Builder\Concerns\HasTypedValueNormalization;

final class MusicAlbumJsonLdBuilder extends AbstractJsonLdBuilder
{
    use HasTypedValueNormalization;

    public function __construct()
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => 'MusicAlbum',
        ]);
    }

    public function setName(string $name): static { return $this->set('name', $name); }
    public function setUrl(string $url): static { return $this->set('url', $url); }
    /** @param string|array<int, string>|array<string, mixed> $image */
    public function setImage(string|array $image): static { return $this->set('image', $image); }
    public function setDescription(string $description): static { return $this->set('description', $description); }
    /** @param string|array<string, mixed> $artist */
    public function setByArtist(string|array $artist): static { return $this->set('byArtist', $this->normalizeTypedValue($artist, 'MusicGroup', 'name')); }
    public function setAlbumProductionType(string $albumProductionType): static { return $this->set('albumProductionType', $albumProductionType); }
    public function setAlbumReleaseType(string $albumReleaseType): static { return $this->set('albumReleaseType', $albumReleaseType); }
    public function setDatePublished(string $datePublished): static { return $this->set('datePublished', $datePublished); }
    /** @param string|array<int, string> $genre */
    public function setGenre(string|array $genre): static { return $this->set('genre', $genre); }
    /** @param array<int, string|array<string, mixed>> $tracks */
    public function setTracks(array $tracks): static
    {
        $normalizedTracks = [];
        foreach ($tracks as $track) {
            $normalizedTracks[] = $this->normalizeTrack($track);
        }

        return $this->set('track', $normalizedTracks);
    }
    /** @param string|array<string, mixed> $track */
    public function addTrack(string|array $track): static { return $this->appendValue('track', $this->normalizeTrack($track)); }
    public function setNumTracks(int $numTracks): static { return $this->set('numTracks', $numTracks); }

    /**
     * @param string|array<string, mixed> $track
     * @return array<string, mixed>
     */
    private function normalizeTrack(string|array $track): array
    {
        return $this->normalizeTypedValue($track, 'MusicRecording', 'name');
    }

    /** @param array<string, mixed> $value */
    private function appendValue(string $key, array $value): static
    {
        $values = $this->get($key);
        if (!is_array($values)) {
            $values = [];
        } elseif (isset($values['@type'])) {
            /** @var array<string, mixed> $existingValue */
            $existingValue = $values;
            $values = [$existingValue];
        } else {
            /** @var list<array<string, mixed>> $values */
            $values = array_values($values);
        }

        $values[] = $value;

        return $this->set($key, $values);
    }
}
