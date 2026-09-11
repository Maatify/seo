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

use Maatify\Seo\Shared\DTO\Sitemap\SitemapAlternateUrlDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapUrlDTO;
use Maatify\Seo\Shared\Service\Internal\HreflangTagNormalizer;
use Maatify\Seo\Web\Hreflang\HreflangLinkDTO;
use Maatify\Seo\Web\Indexing\CanonicalUrlBuilder;
use Maatify\Seo\Web\Sitemap\SitemapXmlStringRenderer;
use Maatify\Seo\Web\Validation\DTO\SeoCompanionValidationResultDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationContextDTO;
use Maatify\Seo\Web\Validation\Input\Hreflang\HreflangValidationClusterDTO;
use Maatify\Seo\Web\Validation\Input\Hreflang\HreflangValidationLinkDTO;
use Maatify\Seo\Web\Validation\Input\Hreflang\HreflangValidationPageDTO;
use Maatify\Seo\Web\Validation\Profile\GoogleCanonicalValidator;
use Maatify\Seo\Web\Validation\Profile\GoogleHreflangClusterValidator;

$stack6Failures = 0;

function stack6AssertSame(string $label, mixed $expected, mixed $actual): void
{
    global $stack6Failures;
    if ($expected !== $actual) {
        ++$stack6Failures;
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
    }
}

function stack6AssertTrue(string $label, bool $actual): void
{
    stack6AssertSame($label, true, $actual);
}

function stack6AssertFalse(string $label, bool $actual): void
{
    stack6AssertSame($label, false, $actual);
}

/** @return list<string> */
function stack6Codes(SeoCompanionValidationResultDTO $result): array
{
    return array_map(
        static fn ($diagnostic): string => $diagnostic->code,
        $result->diagnostics,
    );
}

function stack6CountCode(SeoCompanionValidationResultDTO $result, string $code): int
{
    return count(array_keys(stack6Codes($result), $code, true));
}

/** @param array{scope: string, entry_index: int|null, item_index: int|null, line: int|null} $target */
function stack6AssertDiagnostic(
    SeoCompanionValidationResultDTO $result,
    string $code,
    string $field,
    array $target,
): void {
    $matches = array_values(array_filter(
        $result->toArray()['diagnostics'],
        static fn (array $diagnostic): bool => $diagnostic['code'] === $code,
    ));

    stack6AssertSame("{$code} exact occurrence count", 1, count($matches));
    if (count($matches) !== 1) {
        return;
    }

    $diagnostic = $matches[0];
    stack6AssertSame("{$code} severity", 'warning', $diagnostic['severity']);
    stack6AssertSame("{$code} field", $field, $diagnostic['field']);
    stack6AssertSame("{$code} origin", 'provider', $diagnostic['origin']);
    stack6AssertSame("{$code} profile", 'google', $diagnostic['profile']);
    stack6AssertSame("{$code} evidence state", null, $diagnostic['evidence_state']);
    stack6AssertSame("{$code} related legacy code", null, $diagnostic['related_legacy_code']);
    stack6AssertSame("{$code} target", $target, $diagnostic['target']);
}

/** @param list<HreflangValidationLinkDTO> $links */
function stack6Page(string $pageUrl, array $links): HreflangValidationPageDTO
{
    return new HreflangValidationPageDTO($pageUrl, $links);
}

function stack6Link(?string $hreflang, ?string $url): HreflangValidationLinkDTO
{
    return new HreflangValidationLinkDTO($hreflang, $url);
}

/** @param list<HreflangValidationPageDTO> $pages */
function stack6Cluster(array $pages): HreflangValidationClusterDTO
{
    return new HreflangValidationClusterDTO($pages);
}

function stack6AssertNoCodes(string $label, SeoCompanionValidationResultDTO $result): void
{
    stack6AssertSame($label, [], stack6Codes($result));
    stack6AssertSame($label . ' standalone legacy', null, $result->legacy);
}

function stack6AssertProfileSignature(string $label, string $class, string $inputType, string $inputName): void
{
    $method = new ReflectionMethod($class, 'validate');
    $parameters = $method->getParameters();

    stack6AssertSame($label . ' method name', 'validate', $method->getName());
    stack6AssertSame($label . ' parameter count', 2, count($parameters));
    stack6AssertSame($label . ' input parameter name', $inputName, $parameters[0]->getName());
    stack6AssertSame($label . ' input parameter type', $inputType, $parameters[0]->getType()?->getName());
    stack6AssertFalse($label . ' input nullable', $parameters[0]->allowsNull());
    stack6AssertSame($label . ' context parameter name', 'context', $parameters[1]->getName());
    stack6AssertSame($label . ' context parameter type', SeoValidationContextDTO::class, $parameters[1]->getType()?->getName());
    stack6AssertTrue($label . ' context nullable', $parameters[1]->allowsNull());
    stack6AssertTrue($label . ' context default available', $parameters[1]->isDefaultValueAvailable());
    stack6AssertSame($label . ' context default', null, $parameters[1]->getDefaultValue());
    stack6AssertSame($label . ' return type', SeoCompanionValidationResultDTO::class, $method->getReturnType()?->getName());
}

function stack6AssertLinkCodeTarget(SeoCompanionValidationResultDTO $result, string $code, int $pageIndex, int $linkIndex): void
{
    stack6AssertDiagnostic(
        $result,
        $code,
        $code === 'hreflang_tag_invalid_syntax' ? 'hreflang' : 'href',
        ['scope' => 'hreflang_link', 'entry_index' => $pageIndex, 'item_index' => $linkIndex, 'line' => null],
    );
}

$canonicalValidator = new GoogleCanonicalValidator();
$hreflangValidator = new GoogleHreflangClusterValidator();

// 1. The two new public profile signatures are exact and standalone.
stack6AssertProfileSignature(
    'GoogleCanonicalValidator signature',
    GoogleCanonicalValidator::class,
    'string',
    'canonical',
);
stack6AssertProfileSignature(
    'GoogleHreflangClusterValidator signature',
    GoogleHreflangClusterValidator::class,
    HreflangValidationClusterDTO::class,
    'cluster',
);

// 2. Canonical profile adds only the fixed relative best-practice warning.
$absoluteCanonical = $canonicalValidator->validate('https://example.com/page');
stack6AssertNoCodes('absolute canonical', $absoluteCanonical);

$relativeCanonical = $canonicalValidator->validate('/page');
stack6AssertDiagnostic(
    $relativeCanonical,
    'canonical_relative_provider_best_practice',
    'href',
    ['scope' => 'canonical', 'entry_index' => null, 'item_index' => null, 'line' => null],
);

$relativePathCanonical = $canonicalValidator->validate('products/item');
stack6AssertSame('relative path canonical has one diagnostic', ['canonical_relative_provider_best_practice'], stack6Codes($relativePathCanonical));
stack6AssertSame('empty canonical has no invented diagnostic', [], stack6Codes($canonicalValidator->validate('')));
stack6AssertSame(
    'canonical ignores unknown context keys',
    $relativeCanonical->toArray(),
    $canonicalValidator->validate('/page', new SeoValidationContextDTO(['unknown.stack6' => 'ignored']))->toArray(),
);

$relativeBuilder = (new CanonicalUrlBuilder())->setPath('/relative/path')->setQueryParams(['q' => 'a b']);
stack6AssertSame('relative builder output remains unchanged', '/relative/path?q=a%20b', $relativeBuilder->build());
stack6AssertSame('relative builder HTML remains unchanged', '<link rel="canonical" href="/relative/path?q=a%20b">', $relativeBuilder->toHtml());
$absoluteBuilder = (new CanonicalUrlBuilder('https://example.com'))->setPath('/page')->setQueryParams(['q' => 'a b']);
stack6AssertSame('absolute builder output remains unchanged', 'https://example.com/page?q=a%20b', $absoluteBuilder->build());

// 3. Web and Sitemap paths share one conventional normalization source.
$normalizationCases = [
    'en' => 'en',
    'EN' => 'en',
    'en-us' => 'en-US',
    'EN-us' => 'en-US',
    'zh-Hant' => 'zh-Hant',
    'zh-hant' => 'zh-Hant',
    'ZH-HANS-us' => 'zh-Hans-US',
    'es-419' => 'es-419',
    'x-default' => 'x-default',
    'X-DEFAULT' => 'x-default',
];
$sitemapRenderer = new SitemapXmlStringRenderer();
foreach ($normalizationCases as $raw => $expected) {
    stack6AssertSame($raw . ' shared normalizer output', $expected, HreflangTagNormalizer::normalize($raw));

    $webLink = new HreflangLinkDTO($raw, 'https://example.com/page');
    $sitemapLink = new SitemapAlternateUrlDTO($raw, 'https://example.com/page');
    stack6AssertSame($raw . ' Web normalized output', $expected, $webLink->hreflang);
    stack6AssertSame($raw . ' Sitemap normalized output', $expected, $sitemapLink->jsonSerialize()['hreflang']);

    $xml = $sitemapRenderer->renderUrlEntry(new SitemapUrlDTO(
        'https://example.com/page',
        alternates: [$sitemapLink],
    ));
    stack6AssertTrue($raw . ' Sitemap XML normalized output', str_contains($xml, 'hreflang="' . $expected . '"'));
}

// 4. Candidate hreflang syntax is lexical only; casing is never diagnostic.
$validTagCases = [
    'en', 'EN', 'en-US', 'EN-us', 'zh-Hant', 'zh-Hans-US', 'es-419', 'x-default', 'X-DEFAULT',
    'zz-ZZ', 'qaa-Latn-001',
];
foreach ($validTagCases as $tag) {
    $pageUrl = 'https://example.com/tag/' . strtolower(str_replace(['-', '_'], '-', $tag));
    $result = $hreflangValidator->validate(stack6Cluster([
        stack6Page($pageUrl, [stack6Link($tag, $pageUrl)]),
    ]));
    stack6AssertSame($tag . ' has no syntax diagnostic', 0, stack6CountCode($result, 'hreflang_tag_invalid_syntax'));
    stack6AssertFalse($tag . ' has no noncanonical-case diagnostic', in_array('hreflang_noncanonical_case', stack6Codes($result), true));
}

$invalidTagCases = ['', ' ', 'e', 'engl', 'en_', 'en_US', 'en-', '-en', 'en-USA', 'zh-Hant-US-extra', null];
foreach ($invalidTagCases as $tag) {
    $pageUrl = 'https://example.com/invalid-tag';
    $result = $hreflangValidator->validate(stack6Cluster([
        stack6Page($pageUrl, [
            stack6Link('en', $pageUrl),
            stack6Link($tag, 'https://example.com/other'),
        ]),
    ]));
    stack6AssertSame('invalid tag has one syntax diagnostic: ' . var_export($tag, true), 1, stack6CountCode($result, 'hreflang_tag_invalid_syntax'));
    stack6AssertSame('invalid tag has no URL diagnostic: ' . var_export($tag, true), 0, stack6CountCode($result, 'hreflang_url_not_fully_qualified'));
}

// 5. Hreflang URL validation delegates to the common absolute-authority profile.
$acceptedUrlCases = [
    'https://example.com/en/',
    'https://example.com/العربية/',
    'https://مثال.com/en/',
    'https://example.com/en/#section',
    'https://user@example.com/en/',
    'https://user:pass@example.com/en/',
    'https://[::1]/en/',
    'https://[2001:db8::1]:8443/en/',
    'custom+v1://example.com/path',
    'https://example.com:0/en/',
    'https://example.com:80/en/',
    'https://example.com:443/en/',
    'https://example.com:0080/en/',
    'https://example.com:65535/en/',
    'https://example.com/%20/%AF',
    'https://example.com/search?q=a%20b&x=1',
];
foreach ($acceptedUrlCases as $url) {
    $pageUrl = 'https://example.com/url-matrix';
    $result = $hreflangValidator->validate(stack6Cluster([
        stack6Page($pageUrl, [stack6Link('en', $pageUrl), stack6Link('fr', $url)]),
    ]));
    stack6AssertSame('accepted URL has no URL diagnostic: ' . $url, 0, stack6CountCode($result, 'hreflang_url_not_fully_qualified'));
}

$rejectedUrlCases = [
    null,
    '',
    ' ',
    '/en/',
    'example.com/en/',
    'https:///en/',
    'https://',
    'https://example.com/a b',
    'https://example.com/%ZZ',
    'https://example.com/%',
    'https://@example.com/path',
    'https://user@/path',
    'https://a@b@example.com/path',
    'https://example.com:/path',
    'https://example.com:-1/path',
    'https://example.com:+80/path',
    'https://example.com:abc/path',
    'https://example.com:65536/path',
    'https://[::1/path',
    'https://[]/path',
    'https://[::1]text/path',
    'https://example.com/a\\b',
];
foreach ($rejectedUrlCases as $url) {
    $pageUrl = 'https://example.com/url-matrix';
    $result = $hreflangValidator->validate(stack6Cluster([
        stack6Page($pageUrl, [stack6Link('en', $pageUrl), stack6Link('fr', $url)]),
    ]));
    stack6AssertSame('rejected URL has one URL diagnostic: ' . var_export($url, true), 1, stack6CountCode($result, 'hreflang_url_not_fully_qualified'));
    stack6AssertSame('rejected URL has no tag diagnostic: ' . var_export($url, true), 0, stack6CountCode($result, 'hreflang_tag_invalid_syntax'));
}

// 6. Link-level targets cover page 0/link 0, page 0/link 1, and page 1/link 0.
$page0 = 'https://example.com/en/';
$page1 = 'https://example.com/fr/';
$targetPage0Link0 = $hreflangValidator->validate(stack6Cluster([
    stack6Page($page0, [stack6Link('en_US', $page1), stack6Link('en', $page0)]),
]));
stack6AssertLinkCodeTarget($targetPage0Link0, 'hreflang_tag_invalid_syntax', 0, 0);

$targetPage0Link1 = $hreflangValidator->validate(stack6Cluster([
    stack6Page($page0, [stack6Link('en', $page0), stack6Link('fr', '/fr/')]),
]));
stack6AssertLinkCodeTarget($targetPage0Link1, 'hreflang_url_not_fully_qualified', 0, 1);

$targetPage1Link0 = $hreflangValidator->validate(stack6Cluster([
    stack6Page($page0, [stack6Link('en', $page0)]),
    stack6Page($page1, [stack6Link('fr_US', $page0), stack6Link('fr', $page1)]),
]));
stack6AssertLinkCodeTarget($targetPage1Link0, 'hreflang_tag_invalid_syntax', 1, 0);

// 7. Healthy supplied clusters, self-reference, reciprocity, and set equality.
$defaultUrl = 'https://example.com/';
$healthyCluster = stack6Cluster([
    stack6Page($page0, [
        stack6Link('en-US', $page0),
        stack6Link('fr', $page1),
        stack6Link('x-default', $defaultUrl),
    ]),
    stack6Page($page1, [
        stack6Link('EN-us', $page0),
        stack6Link('FR', $page1),
        stack6Link('X-DEFAULT', $defaultUrl),
    ]),
]);
$healthyResult = $hreflangValidator->validate($healthyCluster);
stack6AssertNoCodes('healthy reciprocal cluster', $healthyResult);
stack6AssertSame(
    'cluster ignores unknown context keys',
    $healthyResult->toArray(),
    $hreflangValidator->validate($healthyCluster, new SeoValidationContextDTO(['hreflang.cluster' => 'not used']))->toArray(),
);

$selfMissingResult = $hreflangValidator->validate(stack6Cluster([
    stack6Page($page0, [stack6Link('fr', $page1)]),
]));
stack6AssertDiagnostic(
    $selfMissingResult,
    'hreflang_self_reference_missing',
    'href',
    ['scope' => 'hreflang_page', 'entry_index' => 0, 'item_index' => null, 'line' => null],
);

$reciprocalMissingResult = $hreflangValidator->validate(stack6Cluster([
    stack6Page($page0, [stack6Link('en', $page0), stack6Link('fr', $page1)]),
    stack6Page($page1, [stack6Link('fr', $page1)]),
]));
stack6AssertDiagnostic(
    $reciprocalMissingResult,
    'hreflang_reciprocal_link_missing',
    'href',
    ['scope' => 'hreflang_page', 'entry_index' => 0, 'item_index' => null, 'line' => null],
);
stack6AssertSame('reciprocity missing only on source page', 0, count(array_filter(
    $reciprocalMissingResult->toArray()['diagnostics'],
    static fn (array $diagnostic): bool => $diagnostic['code'] === 'hreflang_reciprocal_link_missing'
        && $diagnostic['target']['entry_index'] === 1,
)));

$inconsistentSetResult = $hreflangValidator->validate(stack6Cluster([
    stack6Page($page0, [stack6Link('en', $page0), stack6Link('fr', $page1), stack6Link('x-default', $defaultUrl)]),
    stack6Page($page1, [stack6Link('en', $page0), stack6Link('fr', $page1)]),
]));
stack6AssertDiagnostic(
    $inconsistentSetResult,
    'hreflang_alternate_set_inconsistent',
    'href',
    ['scope' => 'hreflang_page', 'entry_index' => 1, 'item_index' => null, 'line' => null],
);

// 8. Structurally invalid links emit their own diagnostics but never cascade.
$invalidLinkCases = [
    [stack6Link('en_US', 'https://example.com/other'), ['hreflang_tag_invalid_syntax']],
    [stack6Link('fr', '/relative'), ['hreflang_url_not_fully_qualified']],
    [stack6Link('en_US', '/relative'), ['hreflang_tag_invalid_syntax', 'hreflang_url_not_fully_qualified']],
];
foreach ($invalidLinkCases as $caseIndex => [$invalidLink, $expectedCodes]) {
    $result = $hreflangValidator->validate(stack6Cluster([
        stack6Page($page0, [stack6Link('en', $page0), $invalidLink]),
    ]));
    sort($expectedCodes);
    $actualCodes = stack6Codes($result);
    sort($actualCodes);
    stack6AssertSame('invalid link case ' . $caseIndex . ' exact link diagnostics', $expectedCodes, $actualCodes);
    foreach (['hreflang_self_reference_missing', 'hreflang_reciprocal_link_missing', 'hreflang_alternate_set_inconsistent'] as $clusterCode) {
        stack6AssertSame('invalid link case ' . $caseIndex . ' no cascade ' . $clusterCode, 0, stack6CountCode($result, $clusterCode));
    }
}

// 9. Page identity remains exact structural input and duplicate identity remains an invocation failure.
$exactPageIdentity = '  page identity  ';
$identityResult = $hreflangValidator->validate(stack6Cluster([
    stack6Page($exactPageIdentity, []),
]));
stack6AssertSame('page identity is not an alternate URL diagnostic', 0, stack6CountCode($identityResult, 'hreflang_url_not_fully_qualified'));
stack6AssertSame('page identity remains exact', $exactPageIdentity, stack6Cluster([stack6Page($exactPageIdentity, [])])->pages[0]->pageUrl);

$duplicateIdentityThrew = false;
try {
    stack6Cluster([
        stack6Page($page0, []),
        stack6Page($page0, []),
    ]);
} catch (Throwable) {
    $duplicateIdentityThrew = true;
}
stack6AssertTrue('duplicate exact page identity remains an invocation failure', $duplicateIdentityThrew);

stack6AssertFalse('no ISO membership diagnostic code exists', in_array('hreflang_iso_membership_invalid', stack6Codes($healthyResult), true));
stack6AssertFalse('no unapproved Stack 6 diagnostic code exists', in_array('hreflang_noncanonical_case', stack6Codes($healthyResult), true));

if ($stack6Failures > 0) {
    fwrite(STDERR, "Stack 6 failed with {$stack6Failures} assertion(s).\n");
    exit(1);
}

echo "Stack 6 Canonical/Hreflang tests passed.\n";
