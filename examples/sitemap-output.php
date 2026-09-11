<?php

declare(strict_types=1);

$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
} else {
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
}

use Maatify\Seo\Shared\DTO\Sitemap\SitemapAlternateUrlDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapImageDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapNewsDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapUrlDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapVideoDTO;
use Maatify\Seo\Shared\Service\SitemapGeneratorService;
use Maatify\Seo\Web\Sitemap\DTO\SitemapIndexEntryDTO;
use Maatify\Seo\Web\Sitemap\SitemapIndexXmlStringRenderer;
use Maatify\Seo\Web\Sitemap\SitemapXmlStringRenderer;

function printSection(string $title, mixed $output): void
{
    echo "\n==============================\n";
    echo $title . "\n";
    echo "==============================\n";
    if (is_string($output)) {
        echo $output . "\n";
    } elseif (is_array($output)) {
        echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }
}

$urlDto = new SitemapUrlDTO('https://example.com/page-1', '2023-11-01', 'daily', 1.0);

$arrayEntry = [
    'loc' => 'https://example.com/page-2',
    'lastmod' => '2023-11-02',
    'changefreq' => 'weekly',
    'priority' => '0.8',
];

$extendedDto = new SitemapUrlDTO(
    loc: 'https://example.com/en/article',
    lastmod: '2026-07-01T10:00:00+00:00',
    changefreq: 'weekly',
    priority: 0.7,
    alternates: [
        new SitemapAlternateUrlDTO('en', 'https://example.com/en/article'),
        new SitemapAlternateUrlDTO('x-default', 'https://example.com/article'),
    ],
    images: [
        new SitemapImageDTO(
            loc: 'https://cdn.example.com/article.jpg',
        ),
    ],
    videos: [
        new SitemapVideoDTO(
            thumbnailLoc: 'https://cdn.example.com/article-video.jpg',
            title: 'Article video',
            description: 'A representative article video',
            contentLoc: 'https://cdn.example.com/article-video.mp4',
            duration: 120,
            publicationDate: '2026-07-01',
        ),
    ],
    news: [
        new SitemapNewsDTO(
            publicationName: 'Example Daily',
            publicationLanguage: 'en',
            publicationDate: '2026-07-01',
            title: 'Example article',
        ),
    ],
);

$renderer = new SitemapXmlStringRenderer();

printSection('Render Single URL Entry (DTO)', $renderer->renderUrlEntry($urlDto));
printSection('Render Single URL Entry (Array)', $renderer->renderUrlEntry($arrayEntry));
printSection('Render Extended URL Entry (DTO)', $renderer->renderUrlEntry($extendedDto));
printSection('Render Full URL Set', $renderer->renderUrlSet([$urlDto, $arrayEntry]));

$indexRenderer = new SitemapIndexXmlStringRenderer();
$indexEntries = [
    new SitemapIndexEntryDTO('https://example.com/sitemap-pages.xml', '2026-07-01'),
    ['loc' => 'https://example.com/sitemap-news.xml', 'lastmod' => '2026-07-02'],
];

printSection('Render Sitemap Index', $indexRenderer->renderIndex($indexEntries));


$generator = new SitemapGeneratorService();
$urls = [
    new SitemapUrlDTO('https://example.com/generated-1'),
    new SitemapUrlDTO('https://example.com/generated-2'),
];
$result = $generator->generateUrlSitemap($urls);

printSection('SitemapGeneratorService Output Result->xml', $result->xml);
