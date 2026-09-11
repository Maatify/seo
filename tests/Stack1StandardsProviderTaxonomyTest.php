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

use Maatify\Seo\Exception\SeoInvalidArgumentException;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapUrlDTO;
use Maatify\Seo\Web\Robots\DTO\RobotsTxtDTO;
use Maatify\Seo\Web\Validation\DTO\SeoCompanionDiagnosticDTO;
use Maatify\Seo\Web\Validation\DTO\SeoCompanionValidationResultDTO;
use Maatify\Seo\Web\Validation\DTO\SeoDiagnosticTargetDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationContextDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationIssueDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationResultDTO;
use Maatify\Seo\Web\Validation\Input\Hreflang\HreflangValidationClusterDTO;
use Maatify\Seo\Web\Validation\Input\Hreflang\HreflangValidationLinkDTO;
use Maatify\Seo\Web\Validation\Input\Hreflang\HreflangValidationPageDTO;
use Maatify\Seo\Web\Validation\Input\RobotsMetaValidationInputDTO;
use Maatify\Seo\Web\Validation\Input\RobotsTxtValidationInputDTO;
use Maatify\Seo\Web\Validation\Input\Sitemap\SitemapImageValidationInputDTO;
use Maatify\Seo\Web\Validation\Input\Sitemap\SitemapIndexEntryValidationInputDTO;
use Maatify\Seo\Web\Validation\Input\Sitemap\SitemapNewsValidationInputDTO;
use Maatify\Seo\Web\Validation\Input\Sitemap\SitemapUrlValidationInputDTO;
use Maatify\Seo\Web\Validation\Input\Sitemap\SitemapValidationDocumentDTO;
use Maatify\Seo\Web\Validation\Input\Sitemap\SitemapValidationLocationDTO;
use Maatify\Seo\Web\Validation\Input\Sitemap\SitemapVideoValidationInputDTO;

function stack1AssertSame(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        throw new RuntimeException("Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true));
    }
}

function stack1AssertTrue(string $label, bool $actual): void
{
    if (!$actual) {
        throw new RuntimeException("Assertion failed: {$label}");
    }
}

function stack1AssertThrows(string $label, callable $callback): void
{
    try {
        $callback();
    } catch (SeoInvalidArgumentException) {
        return;
    } catch (Throwable $exception) {
        throw new RuntimeException("Assertion failed: {$label}\nExpected SeoInvalidArgumentException, got " . get_class($exception) . '.');
    }

    throw new RuntimeException("Assertion failed: {$label}\nExpected SeoInvalidArgumentException.");
}

$videoTarget = new SeoDiagnosticTargetDTO('sitemap_video', 0, 1);
$diagnostic = new SeoCompanionDiagnosticDTO(
    code: 'google_video_title_missing',
    severity: 'warning',
    message: 'Video title is missing.',
    field: 'title',
    origin: 'provider',
    profile: 'google',
    target: $videoTarget,
);
$expectedDiagnostic = [
    'code' => 'google_video_title_missing',
    'severity' => 'warning',
    'message' => 'Video title is missing.',
    'field' => 'title',
    'origin' => 'provider',
    'profile' => 'google',
    'evidence_state' => null,
    'related_legacy_code' => null,
    'target' => [
        'scope' => 'sitemap_video',
        'entry_index' => 0,
        'item_index' => 1,
        'line' => null,
    ],
];
stack1AssertSame('diagnostic exact serialization', $expectedDiagnostic, $diagnostic->toArray());
stack1AssertSame('diagnostic jsonSerialize matches toArray', $expectedDiagnostic, $diagnostic->jsonSerialize());

$companion = new SeoCompanionValidationResultDTO(diagnostics: [$diagnostic]);
stack1AssertSame('standalone companion result exact serialization', ['legacy' => null, 'diagnostics' => [$expectedDiagnostic]], $companion->toArray());
stack1AssertTrue('companion result has no legacy validity or score fields', !array_key_exists('is_valid', $companion->toArray()) && !array_key_exists('has_warnings', $companion->toArray()) && !array_key_exists('score', $companion->toArray()));

$legacy = new SeoValidationResultDTO([new SeoValidationIssueDTO('legacy_warning', 'warning', 'Legacy warning.', 'title')]);
$paired = new SeoCompanionValidationResultDTO(legacy: $legacy, diagnostics: []);
stack1AssertSame('existing legacy result is carried unchanged', $legacy->toArray(), $paired->toArray()['legacy']);
stack1AssertSame('empty companion diagnostics remain empty', [], $paired->toArray()['diagnostics']);

stack1AssertThrows('unknown diagnostic code is rejected', static fn() => new SeoCompanionDiagnosticDTO('invented_code', 'warning', 'Message.', target: new SeoDiagnosticTargetDTO('meta'), origin: 'provider', profile: 'google'));
stack1AssertThrows('ordinary diagnostic severity mismatch is rejected', static fn() => new SeoCompanionDiagnosticDTO('google_video_title_missing', 'info', 'Message.', field: 'title', origin: 'provider', profile: 'google', target: new SeoDiagnosticTargetDTO('sitemap_video', 0, 0)));
stack1AssertThrows('diagnostic origin mismatch is rejected', static fn() => new SeoCompanionDiagnosticDTO('google_video_title_missing', 'warning', 'Message.', field: 'title', origin: 'protocol', profile: 'google', target: new SeoDiagnosticTargetDTO('sitemap_video', 0, 0)));
stack1AssertThrows('ordinary evidence state is rejected', static fn() => new SeoCompanionDiagnosticDTO('google_video_title_missing', 'warning', 'Message.', field: 'title', origin: 'provider', profile: 'google', evidenceState: 'unknown', target: new SeoDiagnosticTargetDTO('sitemap_video', 0, 0)));
stack1AssertThrows('evidence severity mismatch is rejected', static fn() => new SeoCompanionDiagnosticDTO('google_sitemap_lastmod_accuracy', 'warning', 'Message.', field: 'lastmod', origin: 'provider', profile: 'google', evidenceState: 'accurate', target: new SeoDiagnosticTargetDTO('sitemap_url', 0)));
stack1AssertThrows('new ordinary diagnostic rejects non-null legacy correlation', static fn() => new SeoCompanionDiagnosticDTO('google_video_title_missing', 'warning', 'Message.', field: 'title', origin: 'provider', profile: 'google', relatedLegacyCode: 'legacy_code', target: new SeoDiagnosticTargetDTO('sitemap_video', 0, 0)));
stack1AssertThrows('new evidence diagnostic rejects non-null legacy correlation', static fn() => new SeoCompanionDiagnosticDTO('google_sitemap_lastmod_accuracy', 'info', 'Message.', field: 'lastmod', origin: 'provider', profile: 'google', evidenceState: 'accurate', relatedLegacyCode: 'legacy_code', target: new SeoDiagnosticTargetDTO('sitemap_url', 0)));
stack1AssertThrows('missing_og_type rejects non-null legacy correlation', static fn() => new SeoCompanionDiagnosticDTO('missing_og_type', 'warning', 'Message.', field: 'og:type', origin: 'protocol', profile: 'ogp', relatedLegacyCode: 'missing_og_type', target: new SeoDiagnosticTargetDTO('meta')));
stack1AssertThrows('missing_og_url rejects non-null legacy correlation', static fn() => new SeoCompanionDiagnosticDTO('missing_og_url', 'warning', 'Message.', field: 'og:url', origin: 'protocol', profile: 'ogp', relatedLegacyCode: 'missing_og_url', target: new SeoDiagnosticTargetDTO('meta')));
stack1AssertThrows('legacy classification must correlate its own code', static fn() => new SeoCompanionDiagnosticDTO('title_too_short', 'warning', 'Message.', field: 'title', origin: 'heuristic', profile: 'seo-default', target: new SeoDiagnosticTargetDTO('meta')));
stack1AssertThrows('legacy classification rejects a different correlation', static fn() => new SeoCompanionDiagnosticDTO('title_too_short', 'warning', 'Message.', field: 'title', origin: 'heuristic', profile: 'seo-default', relatedLegacyCode: 'title_too_long', target: new SeoDiagnosticTargetDTO('meta')));
$legacyClassification = new SeoCompanionDiagnosticDTO('title_too_short', 'warning', 'Message.', field: 'title', origin: 'heuristic', profile: 'seo-default', relatedLegacyCode: 'title_too_short', target: new SeoDiagnosticTargetDTO('meta'));
stack1AssertSame('legacy classification is accepted', 'title_too_short', $legacyClassification->relatedLegacyCode);

$validTargets = [
    new SeoDiagnosticTargetDTO('robots_document'),
    new SeoDiagnosticTargetDTO('robots_rule', line: 1),
    new SeoDiagnosticTargetDTO('robots_meta'),
    new SeoDiagnosticTargetDTO('sitemap_document'),
    new SeoDiagnosticTargetDTO('sitemap_url', entryIndex: 0),
    new SeoDiagnosticTargetDTO('sitemap_index_entry', entryIndex: 0),
    new SeoDiagnosticTargetDTO('sitemap_image', entryIndex: 0, itemIndex: 0),
    new SeoDiagnosticTargetDTO('sitemap_video', entryIndex: 0, itemIndex: 0),
    new SeoDiagnosticTargetDTO('sitemap_news', entryIndex: 0, itemIndex: 0),
    new SeoDiagnosticTargetDTO('meta'),
    new SeoDiagnosticTargetDTO('canonical'),
    new SeoDiagnosticTargetDTO('hreflang_page', entryIndex: 0),
    new SeoDiagnosticTargetDTO('hreflang_link', entryIndex: 0, itemIndex: 0),
];
stack1AssertSame('all closed target scopes are constructible', 13, count($validTargets));
stack1AssertSame('target exact JSON shape', ['scope' => 'robots_rule', 'entry_index' => null, 'item_index' => null, 'line' => 3], (new SeoDiagnosticTargetDTO('robots_rule', line: 3))->toArray());
stack1AssertThrows('unknown target scope is rejected', static fn() => new SeoDiagnosticTargetDTO('arbitrary'));
stack1AssertThrows('negative target entry index is rejected', static fn() => new SeoDiagnosticTargetDTO('sitemap_url', -1));
stack1AssertThrows('negative target child index is rejected', static fn() => new SeoDiagnosticTargetDTO('sitemap_image', 0, -1));
stack1AssertThrows('zero target line is rejected', static fn() => new SeoDiagnosticTargetDTO('robots_rule', line: 0));
stack1AssertThrows('robots rule requires only a line', static fn() => new SeoDiagnosticTargetDTO('robots_rule', 0, line: 1));
stack1AssertThrows('sitemap video requires both indexes', static fn() => new SeoDiagnosticTargetDTO('sitemap_video', entryIndex: 0));
stack1AssertThrows('meta rejects indexes', static fn() => new SeoDiagnosticTargetDTO('meta', entryIndex: 0));

$context = new SeoValidationContextDTO(evidence: [
    'google_sitemap.lastmod_accuracy' => [0 => 'accurate'],
    'google_sitemap.host_verification' => 'verified_host',
    'sitemaps.cross_submission_authority' => [0 => 'authorized'],
    'google_image.cross_domain_verification' => [0 => [0 => 'verified']],
    'google_image.crawlability' => [0 => [0 => 'accessible']],
    'google_video.relevance' => [0 => [0 => 'unknown']],
    'google_video.title_host_page_match' => [0 => [0 => 'matches']],
    'google_video.description_host_page_match' => [0 => [0 => 'differs']],
    'google_news.original_publication' => [0 => [0 => 'original']],
    'google_news.publication_name_match' => [0 => [0 => 'mismatched']],
    'google_news.freshness' => [0 => [0 => 'within_window']],
    'google_news.title_content_conformance' => [0 => [0 => 'conforming']],
    'robots_meta.unavailable_after_recognizability' => 'recognized',
    'future.ignored' => ['anything' => 'is ignored'],
]);
stack1AssertTrue('authorized context can be constructed', $context->evidence !== null);
stack1AssertThrows('recognized scalar invalid state is rejected', static fn() => new SeoValidationContextDTO(['google_sitemap.host_verification' => 'maybe']));
stack1AssertThrows('recognized indexed invalid state is rejected', static fn() => new SeoValidationContextDTO(['google_sitemap.lastmod_accuracy' => [0 => 'maybe']]));
stack1AssertThrows('recognized nested invalid state is rejected', static fn() => new SeoValidationContextDTO(['google_image.crawlability' => [0 => [0 => 'maybe']]]));
stack1AssertThrows('recognized evidence rejects negative index', static fn() => new SeoValidationContextDTO(['google_sitemap.lastmod_accuracy' => [-1 => 'unknown']]));
stack1AssertThrows('recognized evidence rejects non-integer index', static fn() => new SeoValidationContextDTO(['google_sitemap.lastmod_accuracy' => ['zero' => 'unknown']]));
stack1AssertThrows('recognized nested evidence rejects non-map child', static fn() => new SeoValidationContextDTO(['google_image.crawlability' => [0 => 'unknown']]));
stack1AssertThrows('recognized evidence rejects wrong shape', static fn() => new SeoValidationContextDTO(['google_sitemap.lastmod_accuracy' => 'unknown']));

$rawContent = " Allow\t /raw\r\n";
$robotsCandidate = new RobotsTxtValidationInputDTO($rawContent);
stack1AssertSame('robots candidate preserves raw bytes', $rawContent, $robotsCandidate->content);
$metaCandidate = new RobotsMetaValidationInputDTO(['  noindex  ', '']);
stack1AssertSame('robots meta candidate preserves raw directives', ['  noindex  ', ''], $metaCandidate->directives);
stack1AssertThrows('robots meta candidate requires a list', static fn() => new RobotsMetaValidationInputDTO(['directive' => 'noindex']));
stack1AssertThrows('robots meta candidate requires string elements', static fn() => new RobotsMetaValidationInputDTO(['noindex', 1]));

$image = new SitemapImageValidationInputDTO('  relative image  ');
$video = new SitemapVideoValidationInputDTO(duration: 0, publicationDate: 'not-a-date');
$news = new SitemapNewsValidationInputDTO(publicationLanguage: 'not-an-ISO-code');
$urlCandidate = new SitemapUrlValidationInputDTO(
    loc: '  not-a-url  ',
    lastmod: 'not-a-date',
    priority: INF,
    images: [$image],
    videos: [$video],
    news: [$news],
);
stack1AssertSame('sitemap candidate keeps raw loc', '  not-a-url  ', $urlCandidate->loc);
stack1AssertSame('sitemap candidate keeps malformed lastmod', 'not-a-date', $urlCandidate->lastmod);
stack1AssertSame('sitemap candidate keeps non-finite priority', INF, $urlCandidate->priority);
stack1AssertSame('video candidate keeps zero duration', 0, $video->duration);
stack1AssertSame('image candidate keeps raw loc', '  relative image  ', $image->loc);
stack1AssertSame('news candidate keeps malformed language', 'not-an-ISO-code', $news->publicationLanguage);
stack1AssertThrows('sitemap URL child list requires exact DTOs', static fn() => new SitemapUrlValidationInputDTO(images: [new stdClass()]));

$location = new SitemapValidationLocationDTO('https', 'Example.COM', 443, '/sitemaps/index.xml');
$urlDocument = new SitemapValidationDocumentDTO('urlset', [$urlCandidate], $location, 0);
$indexCandidate = new SitemapIndexEntryValidationInputDTO('not-a-url', 'not-a-date');
$indexDocument = new SitemapValidationDocumentDTO('sitemapindex', [$indexCandidate]);
stack1AssertSame('sitemap document keeps location and byte size', [$location, 0], [$urlDocument->location, $urlDocument->uncompressedSizeBytes]);
stack1AssertSame('sitemap index candidate keeps malformed values', ['not-a-url', 'not-a-date'], [$indexCandidate->loc, $indexCandidate->lastmod]);
stack1AssertThrows('sitemap location requires non-empty scheme', static fn() => new SitemapValidationLocationDTO('', 'example.com', null, '/'));
stack1AssertThrows('sitemap location requires non-empty host', static fn() => new SitemapValidationLocationDTO('https', '', null, '/'));
stack1AssertThrows('sitemap location rejects non-positive port', static fn() => new SitemapValidationLocationDTO('https', 'example.com', 0, '/'));
stack1AssertThrows('sitemap location requires slash-prefixed path', static fn() => new SitemapValidationLocationDTO('https', 'example.com', null, 'relative'));
stack1AssertThrows('sitemap document rejects unknown type', static fn() => new SitemapValidationDocumentDTO('other', []));
stack1AssertThrows('sitemap document rejects mixed entry type', static fn() => new SitemapValidationDocumentDTO('urlset', [$indexCandidate]));
stack1AssertThrows('sitemap document rejects associative entries', static fn() => new SitemapValidationDocumentDTO('urlset', ['url' => $urlCandidate]));
stack1AssertThrows('sitemap document rejects negative byte size', static fn() => new SitemapValidationDocumentDTO('urlset', [], uncompressedSizeBytes: -1));
stack1AssertSame('empty sitemap documents are valid candidate inputs', [], (new SitemapValidationDocumentDTO('sitemapindex', []))->entries);

$link = new HreflangValidationLinkDTO('EN_us', ' /relative path ');
$page = new HreflangValidationPageDTO('  page identity  ', [$link]);
$cluster = new HreflangValidationClusterDTO([$page]);
stack1AssertSame('hreflang candidate preserves raw link values', ['EN_us', ' /relative path '], [$link->hreflang, $link->url]);
stack1AssertSame('hreflang page identity remains exact', '  page identity  ', $cluster->pages[0]->pageUrl);
stack1AssertThrows('hreflang page identity cannot be empty', static fn() => new HreflangValidationPageDTO('', []));
stack1AssertThrows('hreflang page links must be a list', static fn() => new HreflangValidationPageDTO('page', ['link' => $link]));
stack1AssertThrows('hreflang page links require exact DTOs', static fn() => new HreflangValidationPageDTO('page', [new stdClass()]));
stack1AssertThrows('hreflang cluster pages must be a list', static fn() => new HreflangValidationClusterDTO(['page' => $page]));
stack1AssertThrows('hreflang cluster rejects duplicate exact page identity', static fn() => new HreflangValidationClusterDTO([$page, new HreflangValidationPageDTO('  page identity  ', [])]));

stack1AssertThrows('existing strict sitemap URL DTO remains strict', static fn() => new SitemapUrlDTO('not-a-url'));
stack1AssertThrows('existing strict RobotsTxtDTO remains strict for Unicode URL', static fn() => new RobotsTxtDTO(sitemaps: ['https://مثال.com/sitemap.xml']));

fwrite(STDOUT, "Stack 1 standards/provider taxonomy tests passed.\n");
