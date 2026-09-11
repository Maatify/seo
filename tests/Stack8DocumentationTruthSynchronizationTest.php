<?php

declare(strict_types=1);

function stack8Fail(string $message): never
{
    fwrite(STDERR, "Assertion failed: {$message}\n");
    exit(1);
}

function stack8AssertTrue(string $label, bool $actual): void
{
    if (!$actual) {
        stack8Fail($label);
    }
}

function stack8AssertFalse(string $label, bool $actual): void
{
    stack8AssertTrue($label, !$actual);
}

function stack8AssertContains(string $label, string $haystack, string $needle): void
{
    stack8AssertTrue($label, str_contains($haystack, $needle));
}

function stack8AssertNotContains(string $label, string $haystack, string $needle): void
{
    stack8AssertFalse($label, str_contains($haystack, $needle));
}

function stack8Read(string $relativePath): string
{
    $path = dirname(__DIR__) . '/' . $relativePath;
    if (!is_file($path)) {
        stack8Fail("missing documentation fixture: {$relativePath}");
    }

    $contents = file_get_contents($path);
    if ($contents === false) {
        stack8Fail("unable to read documentation fixture: {$relativePath}");
    }

    return $contents;
}

// This is intentionally a documentation-only test: it must not load production code.
$loadedSourceFiles = array_values(array_filter(
    get_included_files(),
    static fn (string $path): bool => str_contains($path, DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR),
));
stack8AssertTrue('Stack 8 test has no production-source dependency', $loadedSourceFiles === []);

$docsIndex = stack8Read('docs/README.md');
foreach ([
    '## Documentation authority',
    '### Current normative documentation',
    '### Historical implementation evidence',
    '### Architecture and audit evidence',
    '### Future and planning material',
    'SEO/library/',
    'SEO_LIBRARY_REFERENCE.md',
    'guides/',
    'phases/',
    'verification/',
    'batches/',
    'audits/',
    'roadmap/',
    'proposals/',
] as $needle) {
    stack8AssertContains("docs index contains {$needle}", $docsIndex, $needle);
}

$phase22 = stack8Read('docs/phases/PHASE_22_SEARCH_CONSOLE_EXTERNAL_VERIFICATION.md');
stack8AssertContains('Phase 22 Final Review passed', $phase22, 'Final Review: `PASS`');
stack8AssertContains('Phase 22 is complete', $phase22, 'Phase 22: `Complete`');
stack8AssertContains('Phase 22 integration is recorded', $phase22, 'Integration PR: `#214` merged into `main`');
stack8AssertNotContains('Phase 22 has no pending Final Review', $phase22, 'Final Review: pending');
stack8AssertNotContains('Phase 22 has no incomplete lifecycle wording', $phase22, 'not marked complete');

$changelog = stack8Read('CHANGELOG.md');
stack8AssertContains('Unreleased changelog exists', $changelog, '## [1.0.0] - Unreleased');
stack8AssertNotContains('changelog does not claim XML streaming', $changelog, 'to stream valid XML');
foreach ([
    'ProductGroup',
    'AggregateOffer',
    'scoped structural and property-range semantic validation',
    'CLI',
    'Search Console',
    'Merchant Center',
    'Stack 0',
    'Stack 1',
    'Stack 2',
    'Stack 3',
    'Stack 4',
    'Stack 5',
    'Stack 6',
    'Stack 7',
    'Stack 8',
] as $needle) {
    stack8AssertContains("Unreleased history contains {$needle}", $changelog, $needle);
}

$readme = stack8Read('README.md');
stack8AssertContains('README records base and strict sitemap scope', $readme, 'base/strict DTO fields');
stack8AssertContains('README separates sitemap provider profiles', $readme, 'provider/profile validation boundaries');
stack8AssertNotContains('README does not imply provider strict compliance', $readme, 'strict URL/date/frequency/priority validation.');
stack8AssertContains('README records Twitter/X audit boundary', $readme, 'Twitter/X provider conformance was not source-verified');

$structuredDocPaths = [
    'docs/SEO/library/STRUCTURED_DATA_ARCHITECTURE.md',
    'docs/SEO_LIBRARY_REFERENCE.md',
    'docs/guides/USAGE_GUIDE.md',
];
$structuredDocContents = [];
foreach ($structuredDocPaths as $path) {
    $contents = stack8Read($path);
    $structuredDocContents[$path] = $contents;
    stack8AssertContains("{$path} uses the Stack 7 validation wording", $contents, 'scoped structural and property-range semantic validation');
}
$currentStructuredDocs = implode('\n', $structuredDocContents);
foreach ([
    'scoped structural and property-range semantic validation',
    'Product',
    'Offer',
    'AggregateOffer',
    'ProductGroup',
] as $needle) {
    stack8AssertContains("current structured docs contain {$needle}", $currentStructuredDocs, $needle);
}
stack8AssertContains('current docs record the Hreflang ISO boundary', $currentStructuredDocs, 'ISO 639');
stack8AssertContains('current docs defer standards-data membership', $currentStructuredDocs, 'separately versioned standards-data contract');
stack8AssertContains('current docs record the provider capability-matrix boundary', $currentStructuredDocs, 'capability matrix');
stack8AssertContains('current docs record the date-stamped provider boundary', $currentStructuredDocs, 'date-stamped contract');
stack8AssertContains('current docs separate Google required and recommended properties', $currentStructuredDocs, 'Google required/recommended');
stack8AssertContains('current docs separate Merchant eligibility', $currentStructuredDocs, 'Merchant eligibility');
stack8AssertContains('current docs preserve the Twitter/X compatibility boundary', $readme, 'Twitter/X provider conformance was not source-verified');

$reference = $structuredDocContents['docs/SEO_LIBRARY_REFERENCE.md'];
stack8AssertContains('MetaGeneratorService remains an unresolved Stack 0 contract', $reference, 'unknown / needs decision');
stack8AssertContains('MetaGeneratorService points to Stack 0 evidence', $reference, 'STACK_0_CONTRACT_CHARACTERIZATION_INVENTORY.md');

$sitemapExample = stack8Read('examples/sitemap-output.php');
stack8AssertContains('sitemap example uses a valid publication date', $sitemapExample, "publicationDate: '2026-07-01'");
stack8AssertNotContains('sitemap example has no stale invalid publication date', $sitemapExample, "publicationDate: '2026-07-32'");

fwrite(STDOUT, "Stack 8 documentation truth synchronization tests passed.\n");
