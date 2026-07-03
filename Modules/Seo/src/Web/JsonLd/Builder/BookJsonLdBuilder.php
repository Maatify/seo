<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

use Maatify\Seo\Web\JsonLd\Builder\Concerns\HasTypedValueNormalization;

final class BookJsonLdBuilder extends AbstractJsonLdBuilder
{
    use HasTypedValueNormalization;

    public function __construct()
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => 'Book',
        ]);
    }

    public function setName(string $name): static { return $this->set('name', $name); }
    public function setUrl(string $url): static { return $this->set('url', $url); }
    /** @param string|array<int, string>|array<string, mixed> $image */
    public function setImage(string|array $image): static { return $this->set('image', $image); }
    public function setDescription(string $description): static { return $this->set('description', $description); }
    /** @param string|array<string, mixed> $author */
    public function setAuthor(string|array $author): static { return $this->set('author', $this->normalizeTypedValue($author, 'Person', 'name')); }
    /** @param string|array<string, mixed> $publisher */
    public function setPublisher(string|array $publisher): static { return $this->set('publisher', $this->normalizeTypedValue($publisher, 'Organization', 'name')); }
    public function setIsbn(string $isbn): static { return $this->set('isbn', $isbn); }
    public function setBookFormat(string $bookFormat): static { return $this->set('bookFormat', $bookFormat); }
    public function setDatePublished(string $datePublished): static { return $this->set('datePublished', $datePublished); }
    public function setNumberOfPages(int $numberOfPages): static { return $this->set('numberOfPages', $numberOfPages); }
    public function setInLanguage(string $inLanguage): static { return $this->set('inLanguage', $inLanguage); }
    /** @param array<string, mixed> $aggregateRating */
    public function setAggregateRating(array $aggregateRating): static { return $this->set('aggregateRating', $this->defaultTypedValue($aggregateRating, 'AggregateRating')); }
    /** @param array<string, mixed>|list<array<string, mixed>> $offers */
    public function setOffers(array $offers): static { return $this->set('offers', $offers); }
}
