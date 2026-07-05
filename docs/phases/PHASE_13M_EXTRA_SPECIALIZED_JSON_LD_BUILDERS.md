# Phase 13M: Extra Specialized JSON-LD Builders

This document demonstrates the usage of the four extra specialized JSON-LD Builders implemented in Phase 13M:
- `BookJsonLdBuilder`
- `MovieJsonLdBuilder`
- `MusicAlbumJsonLdBuilder`
- `DatasetJsonLdBuilder`

These builders are framework-neutral and output structured arrays compatible with Maatify SEO renderers.

## 1. BookJsonLdBuilder

Builds `Book` structured data representing a book, including its author, publisher, ISBN, and available offers.

### Example Usage

```php
use Maatify\Seo\Web\JsonLd\Builder\BookJsonLdBuilder;

$builder = (new BookJsonLdBuilder())
    ->setName('The Hobbit')
    ->setUrl('https://example.com/books/the-hobbit')
    ->setImage(['https://example.com/book1.jpg', 'https://example.com/book2.jpg'])
    ->setDescription('A great book about a hobbit.')
    ->setAuthor('J.R.R. Tolkien')
    ->setPublisher(['@type' => 'Organization', 'name' => 'George Allen & Unwin'])
    ->setIsbn('978-0547928227')
    ->setBookFormat('Hardcover')
    ->setDatePublished('1937-09-21')
    ->setNumberOfPages(310)
    ->setInLanguage('en')
    ->setAggregateRating(['ratingValue' => '4.8', 'reviewCount' => '150'])
    ->setOffers([
        ['@type' => 'Offer', 'price' => '15.99', 'priceCurrency' => 'USD'],
        ['@type' => 'Offer', 'price' => '12.99', 'priceCurrency' => 'GBP']
    ]);

$jsonLdArray = $builder->toArray();
```

## 2. MovieJsonLdBuilder

Builds `Movie` structured data representing a movie, including its director, actors, production company, and runtime.

### Example Usage

```php
use Maatify\Seo\Web\JsonLd\Builder\MovieJsonLdBuilder;

$builder = (new MovieJsonLdBuilder())
    ->setName('Inception')
    ->setUrl('https://example.com/movies/inception')
    ->setImage('https://example.com/inception.jpg')
    ->setDescription('A thief who steals corporate secrets.')
    ->setDirector(['@type' => 'Person', 'name' => 'Christopher Nolan'])
    ->setActors(['Leonardo DiCaprio', 'Joseph Gordon-Levitt'])
    ->addActor(['@type' => 'Person', 'name' => 'Elliot Page'])
    ->setProductionCompany('Legendary Pictures')
    ->setDatePublished('2010-07-16')
    ->setDuration('PT2H28M')
    ->setGenre(['Action', 'Sci-Fi'])
    ->setAggregateRating(['ratingValue' => '8.8', 'reviewCount' => '2M']);

$jsonLdArray = $builder->toArray();
```

## 3. MusicAlbumJsonLdBuilder

Builds `MusicAlbum` structured data representing a music album, including its artist, album type, and list of tracks.

### Example Usage

```php
use Maatify\Seo\Web\JsonLd\Builder\MusicAlbumJsonLdBuilder;

$builder = (new MusicAlbumJsonLdBuilder())
    ->setName('Abbey Road')
    ->setUrl('https://example.com/albums/abbey-road')
    ->setImage('https://example.com/abbey-road.jpg')
    ->setDescription('The Beatles eleventh studio album.')
    ->setByArtist('The Beatles')
    ->setAlbumProductionType('StudioAlbum')
    ->setAlbumReleaseType('AlbumRelease')
    ->setDatePublished('1969-09-26')
    ->setGenre(['Rock', 'Pop'])
    ->setTracks(['Come Together', 'Something'])
    ->addTrack(['@type' => 'MusicRecording', 'name' => 'Maxwell\'s Silver Hammer'])
    ->setNumTracks(17);

$jsonLdArray = $builder->toArray();
```

## 4. DatasetJsonLdBuilder

Builds `Dataset` structured data representing a dataset, including its creator, licensing, keywords, distributions, and coverages.

### Example Usage

```php
use Maatify\Seo\Web\JsonLd\Builder\DatasetJsonLdBuilder;

$builder = (new DatasetJsonLdBuilder())
    ->setName('Global Temperature Data')
    ->setUrl('https://example.com/datasets/global-temperature')
    ->setDescription('Dataset showing global temperature anomalies.')
    ->setCreator('John Doe')
    ->setPublisher('Climate Org')
    ->setDatePublished('2023-01-01')
    ->setDateModified('2023-10-01')
    ->setLicense('https://creativecommons.org/licenses/by/4.0/')
    ->setKeywords(['climate', 'temperature'])
    ->setDistribution(['contentUrl' => 'https://example.com/data.csv', 'encodingFormat' => 'text/csv'])
    ->addDistribution(['@type' => 'DataDownload', 'contentUrl' => 'https://example.com/data.json', 'encodingFormat' => 'application/json'])
    ->setSpatialCoverage('Global')
    ->setTemporalCoverage('1880/2023');

$jsonLdArray = $builder->toArray();
```
