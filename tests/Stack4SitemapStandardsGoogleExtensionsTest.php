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

use Maatify\Seo\Shared\DTO\Sitemap\SitemapIndexEntryDTO as SharedSitemapIndexEntryDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapUrlDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapVideoDTO;
use Maatify\Seo\Exception\SeoInvalidArgumentException;
use Maatify\Seo\Web\Sitemap\DTO\SitemapIndexEntryDTO as WebSitemapIndexEntryDTO;
use Maatify\Seo\Web\Validation\DTO\SeoCompanionValidationResultDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationContextDTO;
use Maatify\Seo\Web\Validation\Input\Sitemap\SitemapImageValidationInputDTO;
use Maatify\Seo\Web\Validation\Input\Sitemap\SitemapIndexEntryValidationInputDTO;
use Maatify\Seo\Web\Validation\Input\Sitemap\SitemapNewsValidationInputDTO;
use Maatify\Seo\Web\Validation\Input\Sitemap\SitemapUrlValidationInputDTO;
use Maatify\Seo\Web\Validation\Input\Sitemap\SitemapValidationDocumentDTO;
use Maatify\Seo\Web\Validation\Input\Sitemap\SitemapValidationLocationDTO;
use Maatify\Seo\Web\Validation\Input\Sitemap\SitemapVideoValidationInputDTO;
use Maatify\Seo\Web\Validation\Profile\GoogleImageSitemapValidator;
use Maatify\Seo\Web\Validation\Profile\GoogleNewsSitemapValidator;
use Maatify\Seo\Web\Validation\Profile\GoogleSitemapValidator;
use Maatify\Seo\Web\Validation\Profile\GoogleVideoSitemapValidator;
use Maatify\Seo\Web\Validation\Profile\Internal\AbsoluteAuthorityUrlLexicalProfile;
use Maatify\Seo\Web\Validation\Profile\SitemapProtocolValidator;

function stack4AssertTrue(string $label, bool $actual): void
{
    if (!$actual) {
        throw new RuntimeException("Assertion failed: {$label}");
    }
}

function stack4AssertSame(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        throw new RuntimeException("Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true));
    }
}

function stack4AssertThrows(string $label, callable $callback): void
{
    try {
        $callback();
    } catch (Throwable) {
        return;
    }

    throw new RuntimeException("Assertion failed: {$label}: expected an exception.");
}

function stack4AssertThrowsClass(string $label, callable $callback, string $expectedClass): void
{
    try {
        $callback();
    } catch (Throwable $exception) {
        stack4AssertSame($label . ' exception class', $expectedClass, $exception::class);

        return;
    }

    throw new RuntimeException("Assertion failed: {$label}: expected an exception.");
}

/** @return list<array<string, mixed>> */
function stack4Diagnostics(SeoCompanionValidationResultDTO $result): array
{
    return $result->toArray()['diagnostics'];
}

/** @return list<string> */
function stack4Codes(SeoCompanionValidationResultDTO $result): array
{
    return array_map(static fn (array $diagnostic): string => $diagnostic['code'], stack4Diagnostics($result));
}

function stack4AssertHasCode(string $label, SeoCompanionValidationResultDTO $result, string $code): void
{
    stack4AssertTrue($label, in_array($code, stack4Codes($result), true));
}

function stack4AssertNotHasCode(string $label, SeoCompanionValidationResultDTO $result, string $code): void
{
    stack4AssertTrue($label, !in_array($code, stack4Codes($result), true));
}

function stack4AssertDiagnostic(
    string $label,
    SeoCompanionValidationResultDTO $result,
    string $code,
    string $severity,
    ?string $field,
    ?string $evidenceState,
    string $scope,
    ?int $entryIndex,
    ?int $itemIndex,
): void {
    foreach (stack4Diagnostics($result) as $diagnostic) {
        if ($diagnostic['code'] !== $code
            || $diagnostic['target']['scope'] !== $scope
            || $diagnostic['target']['entry_index'] !== $entryIndex
            || $diagnostic['target']['item_index'] !== $itemIndex
        ) {
            continue;
        }

        stack4AssertSame($label . ' severity', $severity, $diagnostic['severity']);
        stack4AssertSame($label . ' field', $field, $diagnostic['field']);
        stack4AssertSame($label . ' evidence state', $evidenceState, $diagnostic['evidence_state']);
        stack4AssertSame($label . ' origin', str_starts_with($code, 'sitemap_') ? 'protocol' : 'provider', $diagnostic['origin']);
        stack4AssertSame($label . ' profile', str_starts_with($code, 'sitemap_') ? 'sitemaps' : 'google', $diagnostic['profile']);
        stack4AssertSame($label . ' target', [
            'scope' => $scope,
            'entry_index' => $entryIndex,
            'item_index' => $itemIndex,
            'line' => null,
        ], $diagnostic['target']);
        stack4AssertSame($label . ' legacy correlation', null, $diagnostic['related_legacy_code']);
        return;
    }

    throw new RuntimeException("Assertion failed: {$label}: missing {$code}");
}

function stack4AssertNoLegacy(SeoCompanionValidationResultDTO $result): void
{
    stack4AssertSame('standalone companion result has no legacy result', null, $result->toArray()['legacy']);
    foreach (stack4Diagnostics($result) as $diagnostic) {
        stack4AssertSame('all Stack 4 diagnostics have no legacy correlation', null, $diagnostic['related_legacy_code']);
    }
}

function stack4Url(?string $loc = 'https://example.com/page', array $options = []): SitemapUrlValidationInputDTO
{
    return new SitemapUrlValidationInputDTO(
        loc: $loc,
        lastmod: $options['lastmod'] ?? null,
        changefreq: $options['changefreq'] ?? null,
        priority: $options['priority'] ?? null,
        images: $options['images'] ?? [],
        videos: $options['videos'] ?? [],
        news: $options['news'] ?? [],
    );
}

function stack4UrlDocument(array $entries, ?SitemapValidationLocationDTO $location = null, ?int $size = null): SitemapValidationDocumentDTO
{
    return new SitemapValidationDocumentDTO('urlset', $entries, $location, $size);
}

function stack4IndexDocument(array $entries, ?SitemapValidationLocationDTO $location = null, ?int $size = null): SitemapValidationDocumentDTO
{
    return new SitemapValidationDocumentDTO('sitemapindex', $entries, $location, $size);
}

function stack4Evidence(array $evidence): SeoValidationContextDTO
{
    return new SeoValidationContextDTO($evidence);
}

$protocol = new SitemapProtocolValidator();
$google = new GoogleSitemapValidator();
$images = new GoogleImageSitemapValidator();
$videos = new GoogleVideoSitemapValidator();
$newsValidator = new GoogleNewsSitemapValidator();

foreach ([
    SitemapProtocolValidator::class,
    GoogleSitemapValidator::class,
    GoogleImageSitemapValidator::class,
    GoogleVideoSitemapValidator::class,
    GoogleNewsSitemapValidator::class,
] as $validatorClass) {
    $method = new ReflectionMethod($validatorClass, 'validate');
    stack4AssertSame($validatorClass . ' method name', 'validate', $method->getName());
    stack4AssertSame($validatorClass . ' validate parameter count', 2, $method->getNumberOfParameters());
    $parameters = $method->getParameters();
    stack4AssertSame($validatorClass . ' document parameter name', 'document', $parameters[0]->getName());
    stack4AssertSame($validatorClass . ' context parameter name', 'context', $parameters[1]->getName());
    stack4AssertSame($validatorClass . ' document parameter type', SitemapValidationDocumentDTO::class, $parameters[0]->getType()?->getName());
    stack4AssertSame($validatorClass . ' context parameter type', SeoValidationContextDTO::class, $parameters[1]->getType()?->getName());
    stack4AssertTrue($validatorClass . ' context is nullable', $parameters[1]->getType()?->allowsNull() === true);
    stack4AssertTrue($validatorClass . ' context has a default value', $parameters[1]->isDefaultValueAvailable());
    stack4AssertSame($validatorClass . ' context default value', null, $parameters[1]->getDefaultValue());
    stack4AssertSame($validatorClass . ' return type', SeoCompanionValidationResultDTO::class, $method->getReturnType()?->getName());
}

$googleIndexDocument = stack4IndexDocument([]);
stack4AssertThrowsClass(
    'Google Sitemap rejects sitemapindex at validator start',
    static fn (): SeoCompanionValidationResultDTO => $google->validate($googleIndexDocument),
    SeoInvalidArgumentException::class,
);

$lexical = 'https://مثال.com/مسار?q=1%20two';
stack4AssertTrue('common profile accepts valid raw Unicode absolute URL', AbsoluteAuthorityUrlLexicalProfile::accepts($lexical, false));
stack4AssertTrue('common profile rejects raw space', !AbsoluteAuthorityUrlLexicalProfile::accepts('https://example.com/a b', false));
stack4AssertTrue('common profile rejects raw backslash', !AbsoluteAuthorityUrlLexicalProfile::accepts('https://example.com/a\\b', false));
stack4AssertTrue('common profile rejects malformed percent encoding', !AbsoluteAuthorityUrlLexicalProfile::accepts('https://example.com/%ZZ', false));
stack4AssertTrue('Sitemap loc surface rejects fragments', !AbsoluteAuthorityUrlLexicalProfile::accepts('https://example.com/page#fragment', false));
stack4AssertTrue('Google Video surface allows fragments', AbsoluteAuthorityUrlLexicalProfile::accepts('https://example.com/video#fragment', true));
$components = AbsoluteAuthorityUrlLexicalProfile::components('HTTPS://EXAMPLE.com/folder/page?x=1', false);
stack4AssertSame('common profile exposes scope components without parse_url semantics', [
    'scheme' => 'HTTPS',
    'host' => 'EXAMPLE.com',
    'port' => null,
    'path' => '/folder/page',
], $components);

$acceptedCommonUrls = [
    'https://example.com/path',
    'HTTP+X://example.com/path',
    'custom-scheme://example.com/path',
    'https://user:pass@example.com/path',
    'https://[2001:db8::1]/path',
    'https://example.com:0/path',
    'https://example.com:80/path',
    'https://example.com:443/path',
    'https://example.com:0080/path',
    'https://example.com:65535/path',
    'https://example.com/a%2Fb?x=1%20two',
    'https://مثال.إختبار/مسار?بحث=قيمة',
];
foreach ($acceptedCommonUrls as $candidate) {
    stack4AssertTrue('common URL lexical matrix accepts ' . $candidate, AbsoluteAuthorityUrlLexicalProfile::accepts($candidate, false));
    stack4AssertSame('URL protocol accepts lexical matrix candidate ' . $candidate, [], stack4Codes($protocol->validate(stack4UrlDocument([stack4Url($candidate)]))));
    stack4AssertSame('Index protocol accepts lexical matrix candidate ' . $candidate, [], stack4Codes($protocol->validate(stack4IndexDocument([new SitemapIndexEntryValidationInputDTO($candidate)]))));
}

$rejectedCommonUrls = [
    'https:///path',
    'https://:443/path',
    'https://example.com:/path',
    'https://example.com:abc/path',
    'https://example.com:65536/path',
    'https://[2001:db8::1/path',
    'https://[2001:db8::1]x/path',
    'https://example.com/a b',
    'https://example.com/a\\b',
    'https://example.com/%ZZ',
    'https://example.com/?%',
    'https://example.com/path#fragment',
];
foreach ($rejectedCommonUrls as $candidate) {
    stack4AssertTrue('common URL lexical matrix rejects ' . $candidate, !AbsoluteAuthorityUrlLexicalProfile::accepts($candidate, false));
    $urlResult = $protocol->validate(stack4UrlDocument([stack4Url($candidate)]));
    stack4AssertDiagnostic('URL invalid lexical target ' . $candidate, $urlResult, 'sitemap_loc_invalid_uri_iri', 'error', 'loc', null, 'sitemap_url', 0, null);
    $indexResult = $protocol->validate(stack4IndexDocument([new SitemapIndexEntryValidationInputDTO($candidate)]));
    stack4AssertDiagnostic('Index invalid lexical target ' . $candidate, $indexResult, 'sitemap_loc_invalid_uri_iri', 'error', 'sitemap', null, 'sitemap_index_entry', 0, null);
}

$validUrl = stack4Url();
stack4AssertSame('50,000 URL entries are accepted at the limit', [], stack4Codes($protocol->validate(stack4UrlDocument(array_fill(0, 50000, $validUrl)))));
stack4AssertHasCode('50,001 URL entries exceed the limit', $protocol->validate(stack4UrlDocument(array_fill(0, 50001, $validUrl))), 'sitemap_url_count_exceeds_limit');
stack4AssertSame('50 MB is accepted at the size boundary', [], stack4Codes($protocol->validate(stack4UrlDocument([], size: 52428800))));
stack4AssertHasCode('size above 50 MB emits the document boundary diagnostic', $protocol->validate(stack4UrlDocument([], size: 52428801)), 'sitemap_document_size_exceeds_boundary');
stack4AssertSame('document size is sourced only from the candidate size field', [], stack4Codes($protocol->validate(stack4UrlDocument(array_fill(0, 50000, $validUrl)))));

$missingLoc = $protocol->validate(stack4UrlDocument([stack4Url(null)]));
stack4AssertSame('missing loc emits only missing loc', ['sitemap_loc_missing'], stack4Codes($missingLoc));
stack4AssertDiagnostic('missing URL loc target', $missingLoc, 'sitemap_loc_missing', 'error', 'loc', null, 'sitemap_url', 0, null);
$invalidLoc = $protocol->validate(stack4UrlDocument([stack4Url('not-an-absolute-url')]));
stack4AssertSame('malformed non-empty loc emits only invalid loc', ['sitemap_loc_invalid_uri_iri'], stack4Codes($invalidLoc));
stack4AssertDiagnostic('invalid URL loc target', $invalidLoc, 'sitemap_loc_invalid_uri_iri', 'error', 'loc', null, 'sitemap_url', 0, null);
$loc2047 = 'https://example.com/' . str_repeat('a', 2047 - strlen('https://example.com/'));
$loc2048 = 'https://example.com/' . str_repeat('a', 2048 - strlen('https://example.com/'));
stack4AssertNotHasCode('2,047-byte loc is below the conservative boundary', $protocol->validate(stack4UrlDocument([stack4Url($loc2047)])), 'sitemap_loc_length_exceeds_measure_boundary');
stack4AssertHasCode('2,048-byte loc reaches the conservative boundary', $protocol->validate(stack4UrlDocument([stack4Url($loc2048)])), 'sitemap_loc_length_exceeds_measure_boundary');
$locAtBytes = static function (int $targetBytes): string {
    $prefix = 'https://example.com/';
    $remaining = $targetBytes - strlen($prefix);
    $value = $prefix . str_repeat('é', intdiv($remaining, 2));
    if (strlen($value) < $targetBytes) {
        $value .= 'a';
    }

    return $value;
};
$unicodeLoc2047 = $locAtBytes(2047);
$unicodeLoc2048 = $locAtBytes(2048);
stack4AssertSame('multibyte 2,047-byte loc is measured by bytes', 2047, strlen($unicodeLoc2047));
stack4AssertSame('multibyte 2,048-byte loc is measured by bytes', 2048, strlen($unicodeLoc2048));
stack4AssertNotHasCode('multibyte 2,047-byte loc is below the boundary', $protocol->validate(stack4UrlDocument([stack4Url($unicodeLoc2047)])), 'sitemap_loc_length_exceeds_measure_boundary');
stack4AssertHasCode('multibyte 2,048-byte loc reaches the boundary', $protocol->validate(stack4UrlDocument([stack4Url($unicodeLoc2048)])), 'sitemap_loc_length_exceeds_measure_boundary');
$longInvalidLoc = 'https://example.com/' . str_repeat('a', 2048 - strlen('https://example.com/') - 1) . '%';
$longInvalidResult = $protocol->validate(stack4UrlDocument([stack4Url($longInvalidLoc)]));
stack4AssertHasCode('long malformed loc retains invalid URI diagnostic', $longInvalidResult, 'sitemap_loc_invalid_uri_iri');
stack4AssertHasCode('long malformed loc also receives length boundary diagnostic', $longInvalidResult, 'sitemap_loc_length_exceeds_measure_boundary');

$validFractional = '2026-07-01T10:00:00.123456789+00:00';
stack4AssertSame('fractional seconds are accepted by the shared strict lastmod helper', true, SitemapUrlDTO::isValidLastmod($validFractional));
stack4AssertTrue('strict URL DTO accepts fractional lastmod', new SitemapUrlDTO('https://example.com/fractional', $validFractional) instanceof SitemapUrlDTO);
stack4AssertTrue('strict Shared index DTO accepts fractional lastmod', new SharedSitemapIndexEntryDTO('https://example.com/fractional.xml', $validFractional) instanceof SharedSitemapIndexEntryDTO);
stack4AssertTrue('strict Web index DTO accepts fractional lastmod', new WebSitemapIndexEntryDTO('https://example.com/fractional.xml', $validFractional) instanceof WebSitemapIndexEntryDTO);
stack4AssertThrows('strict Video publication date keeps its no-fraction contract', static function () use ($validFractional): void {
    new SitemapVideoDTO(
        'https://cdn.example.com/thumb.jpg',
        'Video title',
        'Video description',
        'https://cdn.example.com/video.mp4',
        publicationDate: $validFractional,
    );
});
foreach ([
    '2026',
    '2026-07',
    '2026-07-01T10:00Z',
    '2026-07-01T10:00:00',
    '2026-07-01T10:00:00+25:00',
    '2026-02-30T10:00:00Z',
] as $invalidLastmod) {
    stack4AssertHasCode(
        'out-of-contract lastmod is rejected: ' . $invalidLastmod,
        $protocol->validate(stack4UrlDocument([stack4Url(options: ['lastmod' => $invalidLastmod])])),
        'sitemap_lastmod_invalid_lexical',
    );
}
foreach (['2026-07-01', '2026-07-01T10:00:00Z', $validFractional] as $acceptedLastmod) {
    stack4AssertNotHasCode(
        'accepted URL lastmod form: ' . $acceptedLastmod,
        $protocol->validate(stack4UrlDocument([stack4Url(options: ['lastmod' => $acceptedLastmod])])),
        'sitemap_lastmod_invalid_lexical',
    );
}
$invalidIndexLastmod = $protocol->validate(stack4IndexDocument([new SitemapIndexEntryValidationInputDTO(
    'https://example.com/child.xml',
    '2026-02-30T10:00:00Z',
)]));
stack4AssertDiagnostic('invalid Index lastmod target', $invalidIndexLastmod, 'sitemap_lastmod_invalid_lexical', 'error', 'lastmod', null, 'sitemap_index_entry', 0, null);

$entryRules = $protocol->validate(stack4UrlDocument([stack4Url('https://example.com/page', [
    'lastmod' => $validFractional,
    'changefreq' => 'fortnightly',
    'priority' => NAN,
])]));
stack4AssertHasCode('invalid changefreq is diagnosed', $entryRules, 'sitemap_changefreq_invalid');
stack4AssertHasCode('non-finite priority is diagnosed', $entryRules, 'sitemap_priority_out_of_range');
stack4AssertNotHasCode('valid fractional lastmod is not diagnosed', $entryRules, 'sitemap_lastmod_invalid_lexical');
foreach ([-0.1, 1.1, INF, -INF] as $invalidPriority) {
    $priorityResult = $protocol->validate(stack4UrlDocument([stack4Url(options: ['priority' => $invalidPriority])]));
    stack4AssertDiagnostic('priority boundary is diagnosed: ' . (string) $invalidPriority, $priorityResult, 'sitemap_priority_out_of_range', 'error', 'priority', null, 'sitemap_url', 0, null);
}
foreach (['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'] as $allowedChangefreq) {
    stack4AssertNotHasCode(
        'allowed changefreq remains accepted: ' . $allowedChangefreq,
        $protocol->validate(stack4UrlDocument([stack4Url(options: ['changefreq' => $allowedChangefreq])])),
        'sitemap_changefreq_invalid',
    );
}
stack4AssertNotHasCode('blank changefreq is not diagnosed', $protocol->validate(stack4UrlDocument([stack4Url(options: ['changefreq' => ' '])])), 'sitemap_changefreq_invalid');

$location = new SitemapValidationLocationDTO('https', 'example.com', null, '/folder/index.xml');
$withinScope = $protocol->validate(stack4UrlDocument([stack4Url('HTTPS://EXAMPLE.com/folder/page')], $location));
stack4AssertNotHasCode('same authority and path prefix are in scope', $withinScope, 'sitemap_location_scope_violation');
$unauthorizedScope = $protocol->validate(
    stack4UrlDocument([stack4Url('https://other.example.com/other')], $location),
    stack4Evidence(['sitemaps.cross_submission_authority' => [0 => 'unauthorized']]),
);
stack4AssertDiagnostic('unauthorized cross-submission emits URL scope violation', $unauthorizedScope, 'sitemap_location_scope_violation', 'error', 'loc', null, 'sitemap_url', 0, null);
$authorizedScope = $protocol->validate(
    stack4UrlDocument([stack4Url('https://other.example.com/other')], $location),
    stack4Evidence(['sitemaps.cross_submission_authority' => [0 => 'authorized']]),
);
stack4AssertNotHasCode('authorized cross-submission suppresses URL scope violation', $authorizedScope, 'sitemap_location_scope_violation');
$unknownScope = $protocol->validate(stack4UrlDocument([stack4Url('https://other.example.com/other')], $location));
stack4AssertNotHasCode('unknown cross-submission evidence does not fabricate scope failure', $unknownScope, 'sitemap_location_scope_violation');
$defaultHttpLocation = new SitemapValidationLocationDTO('http', 'example.com', null, '/folder/index.xml');
foreach ([
    'http://example.com/folder/page',
    'http://example.com:80/folder/page',
    'HTTP://EXAMPLE.COM/folder/page',
    'http://user:pass@example.com/folder/page?source=1',
] as $sameScopeUrl) {
    stack4AssertNotHasCode(
        'scope accepts default/equivalent authority and ignored userinfo/query: ' . $sameScopeUrl,
        $protocol->validate(stack4UrlDocument([stack4Url($sameScopeUrl)], $defaultHttpLocation)),
        'sitemap_location_scope_violation',
    );
}
stack4AssertDiagnostic(
    'explicit non-default port is outside the scope',
    $protocol->validate(
        stack4UrlDocument([stack4Url('http://example.com:0/folder/page')], $defaultHttpLocation),
        stack4Evidence(['sitemaps.cross_submission_authority' => [0 => 'unauthorized']]),
    ),
    'sitemap_location_scope_violation',
    'error',
    'loc',
    null,
    'sitemap_url',
    0,
    null,
);
$unicodeLocation = new SitemapValidationLocationDTO('https', '例子.com', null, '/folder/index.xml');
stack4AssertNotHasCode(
    'Unicode authority matches exactly',
    $protocol->validate(stack4UrlDocument([stack4Url('https://例子.com/folder/page')], $unicodeLocation)),
    'sitemap_location_scope_violation',
);
stack4AssertDiagnostic(
    'scope does not perform punycode normalization',
    $protocol->validate(
        stack4UrlDocument([stack4Url('https://xn--fsqu00a.xn--0zwm56d/folder/page')], $unicodeLocation),
        stack4Evidence(['sitemaps.cross_submission_authority' => [0 => 'unauthorized']]),
    ),
    'sitemap_location_scope_violation',
    'error',
    'loc',
    null,
    'sitemap_url',
    0,
    null,
);
foreach ([
    'https://example.com./folder/page',
    'https://example.com/foldered/page',
    'https://example.com/outside/../folder/page',
] as $notNormalizedUrl) {
    stack4AssertDiagnostic(
        'scope does not normalize authority/path: ' . $notNormalizedUrl,
        $protocol->validate(
            stack4UrlDocument([stack4Url($notNormalizedUrl)], $location),
            stack4Evidence(['sitemaps.cross_submission_authority' => [0 => 'unauthorized']]),
        ),
        'sitemap_location_scope_violation',
        'error',
        'loc',
        null,
        'sitemap_url',
        0,
        null,
    );
}
stack4AssertNotHasCode(
    'scope uses the raw path prefix without dot-segment normalization',
    $protocol->validate(stack4UrlDocument([stack4Url('https://example.com/folder/../outside')], $location)),
    'sitemap_location_scope_violation',
);
$indexBoundary = $protocol->validate(stack4IndexDocument([new SitemapIndexEntryValidationInputDTO('https://other.example.com/child.xml')], $location));
stack4AssertDiagnostic('Sitemap Index same-site violation target', $indexBoundary, 'sitemap_location_scope_violation', 'error', 'sitemap', null, 'sitemap_index_entry', 0, null);
stack4AssertNotHasCode(
    'Sitemap Index scope ignores child path and query',
    $protocol->validate(stack4IndexDocument([new SitemapIndexEntryValidationInputDTO('https://example.com/any/deep/path?x=1')], $location)),
    'sitemap_location_scope_violation',
);
stack4AssertDiagnostic(
    'Sitemap Index scope ignores cross-submission evidence',
    $protocol->validate(
        stack4IndexDocument([new SitemapIndexEntryValidationInputDTO('https://other.example.com/child.xml')], $location),
        stack4Evidence(['sitemaps.cross_submission_authority' => [0 => 'authorized']]),
    ),
    'sitemap_location_scope_violation',
    'error',
    'sitemap',
    null,
    'sitemap_index_entry',
    0,
    null,
);
stack4AssertSame('50,000 Sitemap Index entries are accepted at the limit', [], stack4Codes($protocol->validate(stack4IndexDocument(array_fill(0, 50000, new SitemapIndexEntryValidationInputDTO('https://example.com/child.xml'))))));
stack4AssertHasCode('50,001 Sitemap Index entries exceed the limit', $protocol->validate(stack4IndexDocument(array_fill(0, 50001, new SitemapIndexEntryValidationInputDTO('https://example.com/child.xml')))), 'sitemap_index_count_exceeds_limit');

$googleDocument = stack4UrlDocument([stack4Url('https://example.com/page', [
    'priority' => 0.5,
    'changefreq' => 'daily',
    'lastmod' => '2026-07-01',
])]);
$googleResult = $google->validate($googleDocument, stack4Evidence([
    'google_sitemap.host_verification' => 'unverified_host',
    'google_sitemap.lastmod_accuracy' => [0 => 'inaccurate'],
]));
stack4AssertDiagnostic('Google host evidence', $googleResult, 'google_sitemap_host_context', 'warning', null, 'unverified_host', 'sitemap_document', null, null);
stack4AssertDiagnostic('Google lastmod evidence', $googleResult, 'google_sitemap_lastmod_accuracy', 'warning', 'lastmod', 'inaccurate', 'sitemap_url', 0, null);
stack4AssertHasCode('Google priority ignored info diagnostic', $googleResult, 'google_sitemap_priority_ignored');
stack4AssertHasCode('Google changefreq ignored info diagnostic', $googleResult, 'google_sitemap_changefreq_ignored');
stack4AssertNoLegacy($googleResult);
$googleUnknownLastmod = $google->validate(stack4UrlDocument([stack4Url(options: ['lastmod' => '2026-07-01'])]));
stack4AssertDiagnostic('missing lastmod evidence maps to unknown', $googleUnknownLastmod, 'google_sitemap_lastmod_accuracy', 'info', 'lastmod', 'unknown', 'sitemap_url', 0, null);
$googleMixedResult = $google->validate(
    stack4UrlDocument([
        stack4Url('https://example.com/with-lastmod', ['lastmod' => '2026-07-01']),
        stack4Url('https://example.com/without-lastmod'),
        stack4Url('https://example.com/blank-lastmod', ['lastmod' => ' ']),
    ]),
    stack4Evidence([
        'google_sitemap.host_verification' => 'verified_host',
        'google_sitemap.lastmod_accuracy' => [0 => 'accurate', 1 => 'inaccurate', 2 => 'inaccurate'],
    ]),
);
stack4AssertDiagnostic('present accurate lastmod evidence', $googleMixedResult, 'google_sitemap_lastmod_accuracy', 'info', 'lastmod', 'accurate', 'sitemap_url', 0, null);
stack4AssertTrue('missing or blank lastmod has no accuracy diagnostic', count(array_filter(
    stack4Diagnostics($googleMixedResult),
    static fn (array $diagnostic): bool => $diagnostic['code'] === 'google_sitemap_lastmod_accuracy' && $diagnostic['target']['entry_index'] !== 0,
)) === 0);
stack4AssertDiagnostic('verified Google host evidence', $googleMixedResult, 'google_sitemap_host_context', 'info', null, 'verified_host', 'sitemap_document', null, null);
foreach ([null, 'unverified_host'] as $hostState) {
    $hostContext = $hostState === null ? null : stack4Evidence(['google_sitemap.host_verification' => $hostState]);
    $hostResult = $google->validate(stack4UrlDocument([]), $hostContext);
    stack4AssertDiagnostic(
        'Google host state remains caller evidence: ' . ($hostState ?? 'unknown'),
        $hostResult,
        'google_sitemap_host_context',
        $hostState === 'unverified_host' ? 'warning' : 'info',
        null,
        $hostState ?? 'unknown',
        'sitemap_document',
        null,
        null,
    );
}

$protocolScopeForGoogle = $protocol->validate(
    stack4UrlDocument([stack4Url('https://other.example.com/page')], $location),
    stack4Evidence(['sitemaps.cross_submission_authority' => [0 => 'unauthorized']]),
);
stack4AssertDiagnostic('Google host evidence does not alter protocol scope', $protocolScopeForGoogle, 'sitemap_location_scope_violation', 'error', 'loc', null, 'sitemap_url', 0, null);

$oneImage = new SitemapImageValidationInputDTO('https://cdn.example.com/image.jpg');
$imageEvidenceResult = $images->validate(
    stack4UrlDocument([stack4Url(options: ['images' => [$oneImage, $oneImage]])]),
    stack4Evidence([
        'google_image.cross_domain_verification' => [0 => [0 => 'verified']],
        'google_image.crawlability' => [0 => [0 => 'accessible']],
    ]),
);
stack4AssertDiagnostic('verified image evidence', $imageEvidenceResult, 'google_image_cross_domain_verification', 'info', 'image', 'verified', 'sitemap_image', 0, 0);
stack4AssertDiagnostic('missing second image evidence maps to unknown', $imageEvidenceResult, 'google_image_cross_domain_verification', 'info', 'image', 'unknown', 'sitemap_image', 0, 1);
stack4AssertDiagnostic('accessible image evidence', $imageEvidenceResult, 'google_image_crawlability_context', 'info', 'image', 'accessible', 'sitemap_image', 0, 0);
stack4AssertDiagnostic('missing second crawlability evidence maps to unknown', $imageEvidenceResult, 'google_image_crawlability_context', 'info', 'image', 'unknown', 'sitemap_image', 0, 1);
stack4AssertTrue('evidence for a non-existent child index creates no diagnostic', !array_filter(stack4Diagnostics($imageEvidenceResult), static fn (array $diagnostic): bool => $diagnostic['target']['item_index'] === 9));
stack4AssertHasCode('1,001 images exceed the per-URL limit', $images->validate(stack4UrlDocument([stack4Url(options: ['images' => array_fill(0, 1001, $oneImage)])])), 'google_image_count_exceeds_limit');
stack4AssertNotHasCode('1,000 images remain at the per-URL limit', $images->validate(stack4UrlDocument([stack4Url(options: ['images' => array_fill(0, 1000, $oneImage)])])), 'google_image_count_exceeds_limit');
$deprecatedImage = new SitemapImageValidationInputDTO(
    'https://cdn.example.com/compatibility.jpg',
    'Deprecated title',
    'Deprecated caption',
    'Somewhere',
    'https://example.com/license',
);
$deprecatedImageResult = $images->validate(stack4UrlDocument([stack4Url(options: ['images' => [$deprecatedImage]])]));
stack4AssertSame('deprecated Image compatibility fields have no runtime diagnostics', [
    'google_image_cross_domain_verification',
    'google_image_crawlability_context',
], stack4Codes($deprecatedImageResult));
stack4AssertNoLegacy($deprecatedImageResult);

$baseVideo = new SitemapVideoValidationInputDTO(
    thumbnailLoc: 'https://cdn.example.com/thumb.jpg',
    title: 'Video title',
    description: 'Video description',
    contentLoc: 'https://cdn.example.com/video.mp4#part',
    playerLoc: 'https://cdn.example.com/player',
    duration: 28800,
    publicationDate: '2026-07-01T10:00:00Z',
);
$validVideoResult = $videos->validate(stack4UrlDocument([stack4Url('https://example.com/page', ['videos' => [$baseVideo]])]));
stack4AssertSame('valid video has only unknown evidence context records', [
    'google_video_relevance_context',
    'google_video_title_host_page_match',
    'google_video_description_host_page_match',
], stack4Codes($validVideoResult));
stack4AssertNoLegacy($validVideoResult);
$missingVideo = $videos->validate(stack4UrlDocument([stack4Url(options: ['videos' => [new SitemapVideoValidationInputDTO()]])]));
foreach (['google_video_title_missing', 'google_video_description_missing', 'google_video_thumbnail_loc_missing', 'google_video_content_or_player_loc_missing'] as $code) {
    stack4AssertHasCode('missing video field: ' . $code, $missingVideo, $code);
}
stack4AssertNotHasCode('missing thumbnail does not cascade to invalid thumbnail URL', $missingVideo, 'google_video_thumbnail_loc_invalid_url');
stack4AssertNotHasCode('missing title does not cascade to title-match evidence', $missingVideo, 'google_video_title_host_page_match');
stack4AssertNotHasCode('missing description does not cascade to description-match evidence', $missingVideo, 'google_video_description_host_page_match');
stack4AssertHasCode('video relevance remains independent when title and description are missing', $missingVideo, 'google_video_relevance_context');
$missingVideoWithEvidence = $videos->validate(
    stack4UrlDocument([stack4Url(options: ['videos' => [new SitemapVideoValidationInputDTO()]])]),
    stack4Evidence([
        'google_video.relevance' => [0 => [0 => 'irrelevant']],
        'google_video.title_host_page_match' => [0 => [0 => 'differs']],
        'google_video.description_host_page_match' => [0 => [0 => 'differs']],
    ]),
);
stack4AssertNotHasCode('missing title suppresses supplied title-match evidence', $missingVideoWithEvidence, 'google_video_title_host_page_match');
stack4AssertNotHasCode('missing description suppresses supplied description-match evidence', $missingVideoWithEvidence, 'google_video_description_host_page_match');
$missingTitleVideo = new SitemapVideoValidationInputDTO(
    thumbnailLoc: 'https://cdn.example.com/thumb.jpg',
    description: 'Description',
    contentLoc: 'https://cdn.example.com/video.mp4',
);
$missingTitleResult = $videos->validate(stack4UrlDocument([stack4Url(options: ['videos' => [$missingTitleVideo]])]));
stack4AssertHasCode('only the missing title field is diagnosed', $missingTitleResult, 'google_video_title_missing');
stack4AssertNotHasCode('missing title has no title-match evidence', $missingTitleResult, 'google_video_title_host_page_match');
stack4AssertHasCode('present description retains independent description evidence', $missingTitleResult, 'google_video_description_host_page_match');
$missingThumbnailVideo = new SitemapVideoValidationInputDTO(
    title: 'Title',
    description: 'Description',
    contentLoc: 'https://cdn.example.com/video.mp4',
);
$missingThumbnailResult = $videos->validate(stack4UrlDocument([stack4Url(options: ['videos' => [$missingThumbnailVideo]])]));
stack4AssertHasCode('missing Video thumbnail is diagnosed', $missingThumbnailResult, 'google_video_thumbnail_loc_missing');
stack4AssertNotHasCode('missing Video thumbnail does not cascade to URL invalidity', $missingThumbnailResult, 'google_video_thumbnail_loc_invalid_url');
$missingMediaVideo = new SitemapVideoValidationInputDTO(
    thumbnailLoc: 'https://cdn.example.com/thumb.jpg',
    title: 'Title',
    description: 'Description',
);
$missingMediaResult = $videos->validate(stack4UrlDocument([stack4Url(options: ['videos' => [$missingMediaVideo]])]));
stack4AssertHasCode('missing Video content and player locations are diagnosed', $missingMediaResult, 'google_video_content_or_player_loc_missing');
stack4AssertNotHasCode('missing Video media does not cascade to content URL invalidity', $missingMediaResult, 'google_video_content_loc_invalid_url');
stack4AssertNotHasCode('missing Video media does not cascade to player URL invalidity', $missingMediaResult, 'google_video_player_loc_invalid_url');
$missingDescriptionVideo = new SitemapVideoValidationInputDTO(
    thumbnailLoc: 'https://cdn.example.com/thumb.jpg',
    title: 'Title',
    contentLoc: 'https://cdn.example.com/video.mp4',
);
$missingDescriptionResult = $videos->validate(stack4UrlDocument([stack4Url(options: ['videos' => [$missingDescriptionVideo]])]));
stack4AssertHasCode('only the missing description field is diagnosed', $missingDescriptionResult, 'google_video_description_missing');
stack4AssertNotHasCode('missing description has no description-match evidence', $missingDescriptionResult, 'google_video_description_host_page_match');
stack4AssertHasCode('present title retains independent title evidence', $missingDescriptionResult, 'google_video_title_host_page_match');
$malformedVideo = new SitemapVideoValidationInputDTO(
    thumbnailLoc: 'not-a-url',
    title: 'Title',
    description: str_repeat('a', 2049),
    contentLoc: 'not-a-url',
    playerLoc: 'https://cdn.example.com/player',
    duration: 28801,
    publicationDate: '2026-07-01T10:00:00.123Z',
);
$malformedVideoResult = $videos->validate(stack4UrlDocument([stack4Url(options: ['videos' => [$malformedVideo]])]));
foreach (['google_video_thumbnail_loc_invalid_url', 'google_video_content_loc_invalid_url', 'google_video_description_length_exceeds_measure_boundary', 'google_video_duration_out_of_range', 'google_video_publication_date_invalid'] as $code) {
    stack4AssertHasCode('malformed video field: ' . $code, $malformedVideoResult, $code);
}
stack4AssertNotHasCode('malformed content location does not create data URL diagnostic', $malformedVideoResult, 'google_video_data_url_unsupported');
stack4AssertNotHasCode('malformed media does not create parent equality evidence', $malformedVideoResult, 'google_video_media_loc_equals_parent_loc');
foreach ([
    'thumbnailLoc' => 'google_video_thumbnail_loc_invalid_url',
    'contentLoc' => 'google_video_content_loc_invalid_url',
    'playerLoc' => 'google_video_player_loc_invalid_url',
] as $field => $invalidCode) {
    $invalidMedia = [
        'thumbnailLoc' => 'https://cdn.example.com/thumb.jpg',
        'title' => 'Title',
        'description' => 'Description',
        'contentLoc' => 'https://cdn.example.com/content.mp4',
        'playerLoc' => 'https://cdn.example.com/player',
    ];
    $invalidMedia[$field] = 'https://cdn.example.com/a b';
    $invalidMediaResult = $videos->validate(stack4UrlDocument([stack4Url(options: ['videos' => [new SitemapVideoValidationInputDTO(...$invalidMedia)]])]));
    stack4AssertHasCode('invalid Video URL matrix field: ' . $field, $invalidMediaResult, $invalidCode);
}
foreach (['thumbnailLoc', 'contentLoc', 'playerLoc'] as $field) {
    $validMedia = [
        'thumbnailLoc' => 'https://cdn.example.com/thumb.jpg#fragment',
        'title' => 'Title',
        'description' => 'Description',
        'contentLoc' => 'https://cdn.example.com/content.mp4#fragment',
        'playerLoc' => 'https://cdn.example.com/player#fragment',
    ];
    $validMediaResult = $videos->validate(stack4UrlDocument([stack4Url(options: ['videos' => [new SitemapVideoValidationInputDTO(...$validMedia)]])]));
    stack4AssertNotHasCode('fragments remain accepted for Video URL field: ' . $field, $validMediaResult, match ($field) {
        'thumbnailLoc' => 'google_video_thumbnail_loc_invalid_url',
        'contentLoc' => 'google_video_content_loc_invalid_url',
        default => 'google_video_player_loc_invalid_url',
    });
}
foreach ([str_repeat('a', 2048), str_repeat('a', 2049), str_repeat('é', 1024), str_repeat('é', 1025)] as $descriptionValue) {
    $descriptionBoundary = $videos->validate(stack4UrlDocument([stack4Url(options: ['videos' => [new SitemapVideoValidationInputDTO(
        thumbnailLoc: 'https://cdn.example.com/thumb.jpg',
        title: 'Title',
        description: $descriptionValue,
        contentLoc: 'https://cdn.example.com/video.mp4',
    )]])]));
    $expectedLengthDiagnostic = strlen($descriptionValue) > 2048;
    stack4AssertSame('Video description byte boundary: ' . strlen($descriptionValue), $expectedLengthDiagnostic, in_array('google_video_description_length_exceeds_measure_boundary', stack4Codes($descriptionBoundary), true));
}
stack4AssertNotHasCode('empty optional Video publication date has no diagnostic', $videos->validate(stack4UrlDocument([stack4Url(options: ['videos' => [new SitemapVideoValidationInputDTO(
    thumbnailLoc: 'https://cdn.example.com/thumb.jpg',
    title: 'Title',
    description: 'Description',
    contentLoc: 'https://cdn.example.com/video.mp4',
    publicationDate: ' ',
)]] )])), 'google_video_publication_date_invalid');
$dataVideo = $videos->validate(stack4UrlDocument([stack4Url(options: ['videos' => [new SitemapVideoValidationInputDTO(
    thumbnailLoc: 'data:image/png;base64,AA',
    title: 'Title',
    description: 'Description',
    contentLoc: 'DATA:video/mp4;base64,AA',
)]])]));
stack4AssertHasCode('data content location emits unsupported data URL', $dataVideo, 'google_video_data_url_unsupported');
stack4AssertNotHasCode('data content location does not cascade to invalid URL', $dataVideo, 'google_video_content_loc_invalid_url');
stack4AssertHasCode('data thumbnail uses invalid thumbnail URL contract', $dataVideo, 'google_video_thumbnail_loc_invalid_url');
$dataPlayerEntry = new SitemapVideoValidationInputDTO(
    thumbnailLoc: 'https://cdn.example.com/thumb.jpg',
    title: 'Title',
    description: 'Description',
    contentLoc: 'https://cdn.example.com/video.mp4',
    playerLoc: 'DATA:video/mp4;base64,AA',
);
$dataPlayer = $videos->validate(stack4UrlDocument([stack4Url(options: ['videos' => [$dataPlayerEntry]])]));
stack4AssertHasCode('data player location emits unsupported data URL', $dataPlayer, 'google_video_data_url_unsupported');
stack4AssertNotHasCode('data player location does not cascade to invalid URL', $dataPlayer, 'google_video_player_loc_invalid_url');
$dataBothEntry = new SitemapVideoValidationInputDTO(
    thumbnailLoc: 'https://cdn.example.com/thumb.jpg',
    title: 'Title',
    description: 'Description',
    contentLoc: 'data:video/mp4;base64,AA',
    playerLoc: 'data:video/mp4;base64,AA',
);
$dataBoth = $videos->validate(stack4UrlDocument([stack4Url(options: ['videos' => [$dataBothEntry]])]));
stack4AssertSame('both data media locations receive field-specific unsupported diagnostics', 2, count(array_filter(stack4Diagnostics($dataBoth), static fn (array $diagnostic): bool => $diagnostic['code'] === 'google_video_data_url_unsupported')));
stack4AssertNotHasCode('data media locations do not create invalid URL diagnostics', $dataBoth, 'google_video_content_loc_invalid_url');
stack4AssertNotHasCode('data media locations do not create player URL diagnostics', $dataBoth, 'google_video_player_loc_invalid_url');
$preferenceVideo = $videos->validate(stack4UrlDocument([stack4Url(options: ['videos' => [new SitemapVideoValidationInputDTO(
    thumbnailLoc: 'https://cdn.example.com/thumb.jpg',
    title: 'Title',
    description: 'Description',
    playerLoc: 'https://cdn.example.com/player',
)]] )]));
stack4AssertHasCode('content location preference is informational', $preferenceVideo, 'google_video_content_loc_preference');
$equalMediaVideo = $videos->validate(
    stack4UrlDocument([
        stack4Url('https://example.com/page', ['videos' => [new SitemapVideoValidationInputDTO(
    thumbnailLoc: 'https://cdn.example.com/thumb.jpg',
    title: 'Title',
    description: 'Description',
    contentLoc: 'https://example.com/page',
    playerLoc: 'https://example.com/page',
        )]]),
    ]),
);
stack4AssertSame('content and player equality produce separate diagnostics', 2, count(array_filter(stack4Diagnostics($equalMediaVideo), static fn (array $diagnostic): bool => $diagnostic['code'] === 'google_video_media_loc_equals_parent_loc')));
$invalidEqualMedia = $videos->validate(stack4UrlDocument([stack4Url('https://example.com/page', ['videos' => [new SitemapVideoValidationInputDTO(
    thumbnailLoc: 'https://cdn.example.com/thumb.jpg',
    title: 'Title',
    description: 'Description',
    contentLoc: 'not-a-url',
)]] )]));
stack4AssertNotHasCode('invalid media does not compare equal to parent location', $invalidEqualMedia, 'google_video_media_loc_equals_parent_loc');
$videoEvidence = $videos->validate(
    stack4UrlDocument([stack4Url(options: ['videos' => [$baseVideo]])]),
    stack4Evidence([
        'google_video.relevance' => [0 => [0 => 'irrelevant']],
        'google_video.title_host_page_match' => [0 => [0 => 'differs']],
        'google_video.description_host_page_match' => [0 => [0 => 'matches']],
    ]),
);
stack4AssertDiagnostic('video relevance warning evidence', $videoEvidence, 'google_video_relevance_context', 'warning', null, 'irrelevant', 'sitemap_video', 0, 0);
stack4AssertDiagnostic('video title warning evidence', $videoEvidence, 'google_video_title_host_page_match', 'warning', 'title', 'differs', 'sitemap_video', 0, 0);
stack4AssertDiagnostic('video description info evidence', $videoEvidence, 'google_video_description_host_page_match', 'info', 'description', 'matches', 'sitemap_video', 0, 0);
$targetMatrix = $videos->validate(stack4UrlDocument([
    stack4Url(options: ['videos' => [$baseVideo, $missingTitleVideo]]),
    stack4Url(options: ['videos' => [$missingDescriptionVideo]]),
]));
stack4AssertDiagnostic('Video URL 0/video 1 target', $targetMatrix, 'google_video_title_missing', 'warning', 'title', null, 'sitemap_video', 0, 1);
stack4AssertDiagnostic('Video URL 1/video 0 target', $targetMatrix, 'google_video_description_missing', 'warning', 'description', null, 'sitemap_video', 1, 0);
stack4AssertTrue('Video diagnostics never claim remote facts', !array_filter(
    stack4Diagnostics($targetMatrix),
    static fn (array $diagnostic): bool => str_contains($diagnostic['code'], 'remote') || str_contains($diagnostic['message'], 'remote'),
));

$validNews = new SitemapNewsValidationInputDTO('Example Daily', 'zh-cn', '2026-07-01T10:00:00.123+02:00', 'A story');
foreach (['2026-07-01', '2026-07-01T10:00Z', '2026-07-01T10:00:00+00:00', '2026-07-01T10:00:00.1-05:00'] as $publicationDate) {
    $newsResult = $newsValidator->validate(stack4UrlDocument([stack4Url(options: ['news' => [new SitemapNewsValidationInputDTO('Example Daily', 'en', $publicationDate, 'A story')]])]));
    stack4AssertNotHasCode('valid News publication date: ' . $publicationDate, $newsResult, 'google_news_publication_date_invalid');
}
foreach (['en', 'eng', 'zh-cn', 'zh-tw'] as $publicationLanguage) {
    stack4AssertNotHasCode(
        'accepted News language: ' . $publicationLanguage,
        $newsValidator->validate(stack4UrlDocument([stack4Url(options: ['news' => [new SitemapNewsValidationInputDTO('Example Daily', $publicationLanguage, '2026-07-01', 'A story')]])])),
        'google_news_language_invalid',
    );
}
foreach (['english', 'e', 'zh-CN'] as $publicationLanguage) {
    stack4AssertHasCode(
        'invalid News language: ' . $publicationLanguage,
        $newsValidator->validate(stack4UrlDocument([stack4Url(options: ['news' => [new SitemapNewsValidationInputDTO('Example Daily', $publicationLanguage, '2026-07-01', 'A story')]])])),
        'google_news_language_invalid',
    );
}
$missingNews = $newsValidator->validate(stack4UrlDocument([stack4Url(options: ['news' => [new SitemapNewsValidationInputDTO()]] )]));
foreach (['google_news_publication_name_missing', 'google_news_language_missing', 'google_news_publication_date_missing', 'google_news_title_missing'] as $code) {
    stack4AssertHasCode('missing News field: ' . $code, $missingNews, $code);
}
foreach (['google_news_publication_name_parenthetical', 'google_news_language_invalid', 'google_news_publication_date_invalid', 'google_news_title_content_evidence'] as $code) {
    stack4AssertNotHasCode('missing News field does not create unrelated diagnostic: ' . $code, $missingNews, $code);
}
$newsEvidenceForMissing = stack4Evidence([
    'google_news.original_publication' => [0 => [0 => 'not_original']],
    'google_news.publication_name_match' => [0 => [0 => 'mismatched']],
    'google_news.freshness' => [0 => [0 => 'outside_window']],
    'google_news.title_content_conformance' => [0 => [0 => 'nonconforming']],
]);
$missingNameNews = $newsValidator->validate(
    stack4UrlDocument([stack4Url(options: ['news' => [new SitemapNewsValidationInputDTO(null, 'en', '2026-07-01', 'A story')]])]),
    $newsEvidenceForMissing,
);
stack4AssertHasCode('missing News name is diagnosed', $missingNameNews, 'google_news_publication_name_missing');
stack4AssertNotHasCode('missing News name suppresses name-match evidence', $missingNameNews, 'google_news_name_exact_match_evidence');
stack4AssertHasCode('News freshness remains independent of missing name', $missingNameNews, 'google_news_freshness_evidence');
$missingDateNews = $newsValidator->validate(
    stack4UrlDocument([stack4Url(options: ['news' => [new SitemapNewsValidationInputDTO('Example Daily', 'en', null, 'A story')]])]),
    $newsEvidenceForMissing,
);
stack4AssertHasCode('missing News date is diagnosed', $missingDateNews, 'google_news_publication_date_missing');
stack4AssertNotHasCode('missing News date suppresses original-publication evidence', $missingDateNews, 'google_news_original_publication_evidence');
stack4AssertHasCode('News freshness remains independent of missing date', $missingDateNews, 'google_news_freshness_evidence');
$missingTitleNews = $newsValidator->validate(
    stack4UrlDocument([stack4Url(options: ['news' => [new SitemapNewsValidationInputDTO('Example Daily', 'en', '2026-07-01', null)]])]),
    $newsEvidenceForMissing,
);
stack4AssertHasCode('missing News title is diagnosed', $missingTitleNews, 'google_news_title_missing');
stack4AssertNotHasCode('missing News title suppresses title-content evidence', $missingTitleNews, 'google_news_title_content_evidence');
$invalidNews = $newsValidator->validate(stack4UrlDocument([stack4Url(options: ['news' => [new SitemapNewsValidationInputDTO('Example (Local)', 'english', '2026-07-01T10:00', 'A story')]])]));
stack4AssertHasCode('parenthetical publication name is diagnosed', $invalidNews, 'google_news_publication_name_parenthetical');
stack4AssertHasCode('invalid News language is diagnosed', $invalidNews, 'google_news_language_invalid');
stack4AssertHasCode('invalid News date is diagnosed', $invalidNews, 'google_news_publication_date_invalid');
stack4AssertNotHasCode('invalid News language does not cascade to missing', $invalidNews, 'google_news_language_missing');
stack4AssertNotHasCode('invalid News date does not cascade to missing', $invalidNews, 'google_news_publication_date_missing');
$multipleNews = $newsValidator->validate(stack4UrlDocument([stack4Url(options: ['news' => [$validNews, $validNews]])]));
stack4AssertHasCode('multiple News entries per URL are diagnosed', $multipleNews, 'google_news_multiple_entries_per_url');
stack4AssertHasCode('1,001 total News entries exceed the document limit', $newsValidator->validate(stack4UrlDocument([stack4Url(options: ['news' => array_fill(0, 1001, $validNews)])])), 'google_news_document_count_exceeds_limit');
$newsEvidence = $newsValidator->validate(
    stack4UrlDocument([stack4Url(options: ['news' => [$validNews]])]),
    stack4Evidence([
        'google_news.original_publication' => [0 => [0 => 'not_original']],
        'google_news.publication_name_match' => [0 => [0 => 'mismatched']],
        'google_news.freshness' => [0 => [0 => 'outside_window']],
        'google_news.title_content_conformance' => [0 => [0 => 'nonconforming']],
    ]),
);
stack4AssertDiagnostic('News original-publication warning evidence', $newsEvidence, 'google_news_original_publication_evidence', 'warning', 'publication_date', 'not_original', 'sitemap_news', 0, 0);
stack4AssertDiagnostic('News publication-name warning evidence', $newsEvidence, 'google_news_name_exact_match_evidence', 'warning', 'name', 'mismatched', 'sitemap_news', 0, 0);
stack4AssertDiagnostic('News freshness warning evidence', $newsEvidence, 'google_news_freshness_evidence', 'warning', null, 'outside_window', 'sitemap_news', 0, 0);
stack4AssertDiagnostic('News title conformance warning evidence', $newsEvidence, 'google_news_title_content_evidence', 'warning', 'title', 'nonconforming', 'sitemap_news', 0, 0);
$newsTargetMatrix = $newsValidator->validate(
    stack4UrlDocument([stack4Url(options: ['news' => [$validNews, new SitemapNewsValidationInputDTO('Example Daily', 'en', null, 'A story')]])]),
    stack4Evidence([
        'google_news.original_publication' => [0 => [0 => 'original']],
        'google_news.publication_name_match' => [0 => [0 => 'matched', 1 => 'mismatched']],
        'google_news.freshness' => [0 => [0 => 'within_window', 1 => 'outside_window']],
    ]),
);
stack4AssertDiagnostic('News URL 0/news 0 name evidence target', $newsTargetMatrix, 'google_news_name_exact_match_evidence', 'info', 'name', 'matched', 'sitemap_news', 0, 0);
stack4AssertDiagnostic('News URL 0/news 1 missing date target', $newsTargetMatrix, 'google_news_publication_date_missing', 'warning', 'publication_date', null, 'sitemap_news', 0, 1);
stack4AssertDiagnostic('News URL 0/news 1 freshness target', $newsTargetMatrix, 'google_news_freshness_evidence', 'warning', null, 'outside_window', 'sitemap_news', 0, 1);
$optionalNews = new SitemapNewsValidationInputDTO(
    'Example Daily',
    'en',
    '2026-07-01',
    'A story',
    'subscription',
    'PressRelease',
    'seo,news',
    'ABC,XYZ',
);
$optionalNewsResult = $newsValidator->validate(stack4UrlDocument([stack4Url(options: ['news' => [$optionalNews]])]));
stack4AssertSame('optional News fields have no runtime diagnostics of their own', [
    'google_news_title_content_evidence',
    'google_news_original_publication_evidence',
    'google_news_name_exact_match_evidence',
    'google_news_freshness_evidence',
], stack4Codes($optionalNewsResult));
$newsSource = (string) file_get_contents(__DIR__ . '/../src/Web/Validation/Profile/GoogleNewsSitemapValidator.php');
stack4AssertTrue('Google News validator performs no clock arithmetic', !str_contains($newsSource, 'time(') && !str_contains($newsSource, 'now(') && !str_contains($newsSource, 'DateTime'));

$facadeFiles = [
    __DIR__ . '/../src/Web/Sitemap/SitemapXmlStringRenderer.php',
    __DIR__ . '/../src/Web/Sitemap/SitemapIndexXmlStringRenderer.php',
];
foreach ($facadeFiles as $facadeFile) {
    stack4AssertTrue('public Sitemap facade does not own XMLWriter: ' . basename($facadeFile), !str_contains((string) file_get_contents($facadeFile), 'XMLWriter'));
}
$generatorSource = (string) file_get_contents(__DIR__ . '/../src/Shared/Service/SitemapGeneratorService.php');
$canonicalSource = (string) file_get_contents(__DIR__ . '/../src/Shared/Service/Internal/SitemapCanonicalXmlWriter.php');
stack4AssertTrue('SitemapGeneratorService has no Shared to Web dependency', !str_contains($generatorSource, 'Maatify\\Seo\\Web\\'));
stack4AssertTrue('Shared canonical writer has no Shared to Web dependency', !str_contains($canonicalSource, 'Maatify\\Seo\\Web\\'));
stack4AssertTrue('the canonical writer exists only under Shared internal', is_file(__DIR__ . '/../src/Shared/Service/Internal/SitemapCanonicalXmlWriter.php') && !is_file(__DIR__ . '/../src/Web/Sitemap/Internal/SitemapCanonicalXmlWriter.php'));
$profileSources = glob(__DIR__ . '/../src/Web/Validation/Profile/{SitemapProtocolValidator.php,GoogleSitemapValidator.php,GoogleImageSitemapValidator.php,GoogleVideoSitemapValidator.php,GoogleNewsSitemapValidator.php}', GLOB_BRACE);
foreach ($profileSources as $profileSource) {
    $source = (string) file_get_contents($profileSource);
    stack4AssertTrue('Stack 4 validator has no network/parser/clock inference: ' . basename($profileSource), !str_contains($source, 'parse_url') && !str_contains($source, 'FILTER_VALIDATE_URL') && !str_contains($source, 'file_get_contents') && !str_contains($source, 'now(') && !str_contains($source, 'time('));
}

echo "Stack 4 Sitemap standards and Google extension tests passed.\n";
