<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'Maatify\\Seo\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $path = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

use Maatify\Seo\Web\JsonLd\Builder\BookJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\DatasetJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\MovieJsonLdBuilder;
use Maatify\Seo\Web\JsonLd\Builder\MusicAlbumJsonLdBuilder;

function recursiveKsort(array &$array): void {
    ksort($array);
    foreach ($array as &$value) {
        if (is_array($value)) {
            recursiveKsort($value);
        }
    }
}

function assertSameValue13M(string $label, mixed $expected, mixed $actual): void
{
    // Recursive sort for arrays to ensure order doesn't matter if we need to do exact match
    if (is_array($expected)) {
        recursiveKsort($expected);
    }
    if (is_array($actual)) {
        recursiveKsort($actual);
    }

    $expectedStr = json_encode($expected, JSON_THROW_ON_ERROR);
    $actualStr = json_encode($actual, JSON_THROW_ON_ERROR);

    if ($expectedStr !== $actualStr) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

// 1. BookJsonLdBuilder
$bookBuilder = (new BookJsonLdBuilder())
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

$expectedBook = [
    '@context' => 'https://schema.org',
    '@type' => 'Book',
    'name' => 'The Hobbit',
    'url' => 'https://example.com/books/the-hobbit',
    'image' => [
        'https://example.com/book1.jpg',
        'https://example.com/book2.jpg',
    ],
    'description' => 'A great book about a hobbit.',
    'author' => [
        '@type' => 'Person',
        'name' => 'J.R.R. Tolkien',
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'George Allen & Unwin',
    ],
    'isbn' => '978-0547928227',
    'bookFormat' => 'Hardcover',
    'datePublished' => '1937-09-21',
    'numberOfPages' => 310,
    'inLanguage' => 'en',
    'aggregateRating' => [
        '@type' => 'AggregateRating',
        'ratingValue' => '4.8',
        'reviewCount' => '150',
    ],
    'offers' => [
        [
            '@type' => 'Offer',
            'price' => '15.99',
            'priceCurrency' => 'USD',
        ],
        [
            '@type' => 'Offer',
            'price' => '12.99',
            'priceCurrency' => 'GBP',
        ],
    ],
];
assertSameValue13M('BookJsonLdBuilder full structure', $expectedBook, $bookBuilder->toArray());

// 2. MovieJsonLdBuilder
$movieBuilder = (new MovieJsonLdBuilder())
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

$expectedMovie = [
    '@context' => 'https://schema.org',
    '@type' => 'Movie',
    'name' => 'Inception',
    'url' => 'https://example.com/movies/inception',
    'image' => 'https://example.com/inception.jpg',
    'description' => 'A thief who steals corporate secrets.',
    'director' => [
        '@type' => 'Person',
        'name' => 'Christopher Nolan',
    ],
    'actor' => [
        [
            '@type' => 'Person',
            'name' => 'Leonardo DiCaprio',
        ],
        [
            '@type' => 'Person',
            'name' => 'Joseph Gordon-Levitt',
        ],
        [
            '@type' => 'Person',
            'name' => 'Elliot Page',
        ],
    ],
    'productionCompany' => [
        '@type' => 'Organization',
        'name' => 'Legendary Pictures',
    ],
    'datePublished' => '2010-07-16',
    'duration' => 'PT2H28M',
    'genre' => [
        'Action',
        'Sci-Fi',
    ],
    'aggregateRating' => [
        '@type' => 'AggregateRating',
        'ratingValue' => '8.8',
        'reviewCount' => '2M',
    ],
];
assertSameValue13M('MovieJsonLdBuilder full structure', $expectedMovie, $movieBuilder->toArray());

// 3. MusicAlbumJsonLdBuilder
$musicAlbumBuilder = (new MusicAlbumJsonLdBuilder())
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

$expectedMusicAlbum = [
    '@context' => 'https://schema.org',
    '@type' => 'MusicAlbum',
    'name' => 'Abbey Road',
    'url' => 'https://example.com/albums/abbey-road',
    'image' => 'https://example.com/abbey-road.jpg',
    'description' => 'The Beatles eleventh studio album.',
    'byArtist' => [
        '@type' => 'MusicGroup',
        'name' => 'The Beatles',
    ],
    'albumProductionType' => 'StudioAlbum',
    'albumReleaseType' => 'AlbumRelease',
    'datePublished' => '1969-09-26',
    'genre' => [
        'Rock',
        'Pop',
    ],
    'track' => [
        [
            '@type' => 'MusicRecording',
            'name' => 'Come Together',
        ],
        [
            '@type' => 'MusicRecording',
            'name' => 'Something',
        ],
        [
            '@type' => 'MusicRecording',
            'name' => 'Maxwell\'s Silver Hammer',
        ],
    ],
    'numTracks' => 17,
];
assertSameValue13M('MusicAlbumJsonLdBuilder full structure', $expectedMusicAlbum, $musicAlbumBuilder->toArray());

// 4. DatasetJsonLdBuilder
$datasetBuilder = (new DatasetJsonLdBuilder())
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

$expectedDataset = [
    '@context' => 'https://schema.org',
    '@type' => 'Dataset',
    'name' => 'Global Temperature Data',
    'url' => 'https://example.com/datasets/global-temperature',
    'description' => 'Dataset showing global temperature anomalies.',
    'creator' => [
        '@type' => 'Person',
        'name' => 'John Doe',
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'Climate Org',
    ],
    'datePublished' => '2023-01-01',
    'dateModified' => '2023-10-01',
    'license' => 'https://creativecommons.org/licenses/by/4.0/',
    'keywords' => [
        'climate',
        'temperature',
    ],
    'distribution' => [
        [
            '@type' => 'DataDownload',
            'contentUrl' => 'https://example.com/data.csv',
            'encodingFormat' => 'text/csv',
        ],
        [
            '@type' => 'DataDownload',
            'contentUrl' => 'https://example.com/data.json',
            'encodingFormat' => 'application/json',
        ],
    ],
    'spatialCoverage' => [
        '@type' => 'Place',
        'name' => 'Global',
    ],
    'temporalCoverage' => '1880/2023',
];

assertSameValue13M('DatasetJsonLdBuilder full structure', $expectedDataset, $datasetBuilder->toArray());

echo "Phase 13M: Extra Specialized JSON-LD Builders tests passed.\n";
