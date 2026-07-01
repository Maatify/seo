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

use Maatify\Seo\Shared\DTO\Sitemap\SitemapUrlDTO;
use Maatify\Seo\Shared\Service\SitemapGeneratorService;
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

$renderer = new SitemapXmlStringRenderer();

printSection('Render Single URL Entry (DTO)', $renderer->renderUrlEntry($urlDto));
printSection('Render Single URL Entry (Array)', $renderer->renderUrlEntry($arrayEntry));
printSection('Render Full URL Set', $renderer->renderUrlSet([$urlDto, $arrayEntry]));


$generator = new SitemapGeneratorService();
$urls = [
    new SitemapUrlDTO('https://example.com/generated-1'),
    new SitemapUrlDTO('https://example.com/generated-2'),
];
$result = $generator->generateUrlSitemap($urls);

printSection('SitemapGeneratorService Output Result->xml', $result->xml);
