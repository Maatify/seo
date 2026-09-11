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
use Maatify\Seo\Web\Robots\DTO\RobotsRuleDTO;
use Maatify\Seo\Web\Robots\DTO\RobotsTxtDTO;
use Maatify\Seo\Web\Robots\MetaRobotsBuilder;
use Maatify\Seo\Web\Robots\RobotsTxtRenderer;
use Maatify\Seo\Web\Validation\DTO\SeoCompanionValidationResultDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationContextDTO;
use Maatify\Seo\Web\Validation\Input\RobotsMetaValidationInputDTO;
use Maatify\Seo\Web\Validation\Input\RobotsTxtValidationInputDTO;
use Maatify\Seo\Web\Validation\Profile\GoogleRobotsMetaValidator;
use Maatify\Seo\Web\Validation\Profile\GoogleRobotsTxtValidator;
use Maatify\Seo\Web\Validation\Profile\Rfc9309RobotsValidator;

function stack2AssertSame(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        throw new RuntimeException("Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true));
    }
}

function stack2AssertTrue(string $label, bool $actual): void
{
    if (!$actual) {
        throw new RuntimeException("Assertion failed: {$label}");
    }
}

function stack2AssertThrows(string $label, callable $callback): void
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

/** @param class-string $class */
function stack2AssertValidatorSignature(string $label, string $class, string $inputClass): void
{
    $method = new ReflectionMethod($class, 'validate');
    stack2AssertTrue($label . ' is public', $method->isPublic());
    stack2AssertSame($label . ' parameter count', 2, count($method->getParameters()));

    $parameters = $method->getParameters();
    stack2AssertSame($label . ' input parameter name', 'input', $parameters[0]->getName());
    stack2AssertSame($label . ' input parameter type', $inputClass, $parameters[0]->getType()?->getName());
    stack2AssertSame($label . ' context parameter name', 'context', $parameters[1]->getName());
    stack2AssertSame($label . ' context parameter type', 'Maatify\\Seo\\Web\\Validation\\DTO\\SeoValidationContextDTO', $parameters[1]->getType()?->getName());
    stack2AssertTrue($label . ' context default is available', $parameters[1]->isDefaultValueAvailable());
    stack2AssertSame($label . ' context default', null, $parameters[1]->getDefaultValue());
    stack2AssertSame($label . ' return type', 'Maatify\\Seo\\Web\\Validation\\DTO\\SeoCompanionValidationResultDTO', $method->getReturnType()?->getName());
}

/** @param list<array{code: string, severity: string, field: string, origin: string, profile: string, evidence_state: string|null, target: array{scope: string, entry_index: int|null, item_index: int|null, line: int|null}}> $expected */
function stack2AssertDiagnostics(string $label, SeoCompanionValidationResultDTO $result, array $expected): void
{
    $allowedCodes = [
        'robots_rfc9309_leading_wildcard_compatibility',
        'robots_rfc9309_product_token_invalid',
        'robots_rfc9309_path_pattern_invalid',
        'robots_rfc9309_control_character_invalid',
        'robots_google_document_size_exceeds_parse_limit',
        'robots_google_document_invalid_utf8',
        'robots_google_present_path_leading_slash',
        'robots_google_sitemap_url_not_fully_qualified',
        'robots_meta_indexifembedded_without_noindex',
        'robots_meta_unavailable_after_missing',
        'robots_meta_unavailable_after_recognizability',
    ];

    $actual = [];
    foreach ($result->diagnostics as $diagnostic) {
        $diagnosticArray = $diagnostic->toArray();
        stack2AssertTrue($label . ' uses only an authorized code', in_array($diagnosticArray['code'], $allowedCodes, true));
        stack2AssertTrue($label . ' has a non-empty message', $diagnosticArray['message'] !== '');
        stack2AssertSame($label . ' new diagnostics have no legacy correlation', null, $diagnosticArray['related_legacy_code']);
        $actual[] = [
            'code' => $diagnosticArray['code'],
            'severity' => $diagnosticArray['severity'],
            'field' => $diagnosticArray['field'],
            'origin' => $diagnosticArray['origin'],
            'profile' => $diagnosticArray['profile'],
            'evidence_state' => $diagnosticArray['evidence_state'],
            'target' => $diagnosticArray['target'],
        ];
    }

    stack2AssertSame($label . ' exact diagnostic contracts', $expected, $actual);
    stack2AssertSame($label . ' has no legacy result', null, $result->legacy);
}

stack2AssertValidatorSignature('RFC validator signature', Rfc9309RobotsValidator::class, RobotsTxtValidationInputDTO::class);
stack2AssertValidatorSignature('Google robots.txt validator signature', GoogleRobotsTxtValidator::class, RobotsTxtValidationInputDTO::class);
stack2AssertValidatorSignature('Google robots meta validator signature', GoogleRobotsMetaValidator::class, RobotsMetaValidationInputDTO::class);

$rfcValidator = new Rfc9309RobotsValidator();
$rfcResult = $rfcValidator->validate(new RobotsTxtValidationInputDTO(
    "# raw comment\n"
    . "User-agent: *\n"
    . "Allow:\n"
    . "Disallow:\n"
    . "Allow: /literal%23hash#comment\n"
    . "Disallow: */private\n"
    . "Disallow: no-slash\n"
    . "User-agent: Googlebot-2\n"
    . "Disallow: /valid\n"
));
stack2AssertDiagnostics('RFC path/product diagnostics', $rfcResult, [
    [
        'code' => 'robots_rfc9309_leading_wildcard_compatibility',
        'severity' => 'warning',
        'field' => 'path',
        'origin' => 'protocol',
        'profile' => 'rfc9309',
        'evidence_state' => null,
        'target' => ['scope' => 'robots_rule', 'entry_index' => null, 'item_index' => null, 'line' => 6],
    ],
    [
        'code' => 'robots_rfc9309_path_pattern_invalid',
        'severity' => 'error',
        'field' => 'path',
        'origin' => 'protocol',
        'profile' => 'rfc9309',
        'evidence_state' => null,
        'target' => ['scope' => 'robots_rule', 'entry_index' => null, 'item_index' => null, 'line' => 7],
    ],
    [
        'code' => 'robots_rfc9309_product_token_invalid',
        'severity' => 'error',
        'field' => 'user_agent',
        'origin' => 'protocol',
        'profile' => 'rfc9309',
        'evidence_state' => null,
        'target' => ['scope' => 'robots_rule', 'entry_index' => null, 'item_index' => null, 'line' => 8],
    ],
]);

$validRfcResult = $rfcValidator->validate(new RobotsTxtValidationInputDTO("User-agent: *\r\nAllow: /\r\nDisallow:\n"));
stack2AssertDiagnostics('RFC valid empty/root/line-ending cases', $validRfcResult, []);

$controlRfcResult = $rfcValidator->validate(new RobotsTxtValidationInputDTO("User-agent: bot\x01\r\nUser-agent: tab\t\r\nDisallow: /private\x02\r\n# comment\x03\nAllow: /ok\n"));
stack2AssertDiagnostics('RFC control-character diagnostics', $controlRfcResult, [
    [
        'code' => 'robots_rfc9309_control_character_invalid',
        'severity' => 'error',
        'field' => 'user_agent',
        'origin' => 'protocol',
        'profile' => 'rfc9309',
        'evidence_state' => null,
        'target' => ['scope' => 'robots_rule', 'entry_index' => null, 'item_index' => null, 'line' => 1],
    ],
    [
        'code' => 'robots_rfc9309_control_character_invalid',
        'severity' => 'error',
        'field' => 'user_agent',
        'origin' => 'protocol',
        'profile' => 'rfc9309',
        'evidence_state' => null,
        'target' => ['scope' => 'robots_rule', 'entry_index' => null, 'item_index' => null, 'line' => 2],
    ],
    [
        'code' => 'robots_rfc9309_control_character_invalid',
        'severity' => 'error',
        'field' => 'path',
        'origin' => 'protocol',
        'profile' => 'rfc9309',
        'evidence_state' => null,
        'target' => ['scope' => 'robots_rule', 'entry_index' => null, 'item_index' => null, 'line' => 3],
    ],
]);

$googleValidator = new GoogleRobotsTxtValidator();
$googleBoundary = $googleValidator->validate(new RobotsTxtValidationInputDTO(str_repeat('a', 512000)));
stack2AssertDiagnostics('Google 512000-byte boundary', $googleBoundary, []);
$googleOverBoundary = $googleValidator->validate(new RobotsTxtValidationInputDTO(str_repeat('a', 512001)));
stack2AssertDiagnostics('Google over-boundary diagnostic', $googleOverBoundary, [
    [
        'code' => 'robots_google_document_size_exceeds_parse_limit',
        'severity' => 'warning',
        'field' => 'document',
        'origin' => 'provider',
        'profile' => 'google',
        'evidence_state' => null,
        'target' => ['scope' => 'robots_document', 'entry_index' => null, 'item_index' => null, 'line' => null],
    ],
]);
$googleInvalidUtf8 = $googleValidator->validate(new RobotsTxtValidationInputDTO("User-agent: *\n\xC3\x28"));
stack2AssertDiagnostics('Google invalid UTF-8 diagnostic', $googleInvalidUtf8, [
    [
        'code' => 'robots_google_document_invalid_utf8',
        'severity' => 'warning',
        'field' => 'document',
        'origin' => 'provider',
        'profile' => 'google',
        'evidence_state' => null,
        'target' => ['scope' => 'robots_document', 'entry_index' => null, 'item_index' => null, 'line' => null],
    ],
]);
$googleOverBoundaryInvalidUtf8 = $googleValidator->validate(new RobotsTxtValidationInputDTO(str_repeat('a', 512000) . "\xC3\x28"));
stack2AssertDiagnostics('Google size and UTF-8 diagnostics coexist', $googleOverBoundaryInvalidUtf8, [
    [
        'code' => 'robots_google_document_size_exceeds_parse_limit',
        'severity' => 'warning',
        'field' => 'document',
        'origin' => 'provider',
        'profile' => 'google',
        'evidence_state' => null,
        'target' => ['scope' => 'robots_document', 'entry_index' => null, 'item_index' => null, 'line' => null],
    ],
    [
        'code' => 'robots_google_document_invalid_utf8',
        'severity' => 'warning',
        'field' => 'document',
        'origin' => 'provider',
        'profile' => 'google',
        'evidence_state' => null,
        'target' => ['scope' => 'robots_document', 'entry_index' => null, 'item_index' => null, 'line' => null],
    ],
]);

$googleRulesAndSitemaps = $googleValidator->validate(new RobotsTxtValidationInputDTO(
    "User-agent: *\n"
    . "Allow: /ok\n"
    . "Disallow: no-slash\n"
    . "Sitemap: https://example.com/sitemap.xml\n"
    . "Sitemap: https://مثال.اختبار/مسار?q=بحث\n"
    . "Sitemap: https://other.example/sitemap.xml?x=1\n"
    . "Sitemap: relative.xml\n"
    . "Sitemap: https://example.com/path#fragment\n"
    . "Sitemap: data:text/plain,robots\n"
    . "Sitemap:   \n"
    . "Sitemap: https://example.com/a b\n"
));
stack2AssertDiagnostics('Google path and Sitemap contracts', $googleRulesAndSitemaps, [
    [
        'code' => 'robots_google_present_path_leading_slash',
        'severity' => 'warning',
        'field' => 'path',
        'origin' => 'provider',
        'profile' => 'google',
        'evidence_state' => null,
        'target' => ['scope' => 'robots_rule', 'entry_index' => null, 'item_index' => null, 'line' => 3],
    ],
    [
        'code' => 'robots_google_sitemap_url_not_fully_qualified',
        'severity' => 'warning',
        'field' => 'sitemap',
        'origin' => 'provider',
        'profile' => 'google',
        'evidence_state' => null,
        'target' => ['scope' => 'robots_rule', 'entry_index' => null, 'item_index' => null, 'line' => 7],
    ],
    [
        'code' => 'robots_google_sitemap_url_not_fully_qualified',
        'severity' => 'warning',
        'field' => 'sitemap',
        'origin' => 'provider',
        'profile' => 'google',
        'evidence_state' => null,
        'target' => ['scope' => 'robots_rule', 'entry_index' => null, 'item_index' => null, 'line' => 8],
    ],
    [
        'code' => 'robots_google_sitemap_url_not_fully_qualified',
        'severity' => 'warning',
        'field' => 'sitemap',
        'origin' => 'provider',
        'profile' => 'google',
        'evidence_state' => null,
        'target' => ['scope' => 'robots_rule', 'entry_index' => null, 'item_index' => null, 'line' => 9],
    ],
    [
        'code' => 'robots_google_sitemap_url_not_fully_qualified',
        'severity' => 'warning',
        'field' => 'sitemap',
        'origin' => 'provider',
        'profile' => 'google',
        'evidence_state' => null,
        'target' => ['scope' => 'robots_rule', 'entry_index' => null, 'item_index' => null, 'line' => 10],
    ],
    [
        'code' => 'robots_google_sitemap_url_not_fully_qualified',
        'severity' => 'warning',
        'field' => 'sitemap',
        'origin' => 'provider',
        'profile' => 'google',
        'evidence_state' => null,
        'target' => ['scope' => 'robots_rule', 'entry_index' => null, 'item_index' => null, 'line' => 11],
    ],
]);

$googleSitemapMatrix = $googleValidator->validate(new RobotsTxtValidationInputDTO(
    "Sitemap: https://example.com/sitemap.xml\n"
    . "Sitemap: https://example.com/خرائط/sitemap.xml\n"
    . "Sitemap: https://other.example.com/sitemap.xml\n"
    . "Sitemap: https://example.com:8443/sitemap.xml\n"
    . "Sitemap: https://example.com/sitemap.xml?v=2\n"
    . "Sitemap: /sitemap.xml\n"
    . "Sitemap: example.com/sitemap.xml\n"
    . "Sitemap: https:///sitemap.xml\n"
    . "Sitemap: https://\n"
    . "Sitemap: https://example.com/%ZZ\n"
    . "Sitemap: https://example.com/a b.xml\n"
    . "Sitemap: https://example.com/sitemap.xml#fragment\n"
    . "Sitemap: https://example.com:abc/sitemap.xml\n"
    . "Sitemap: https://example.com:65536/sitemap.xml\n"
    . "Sitemap:\n"
));
stack2AssertDiagnostics('Google Sitemap absolute-authority matrix', $googleSitemapMatrix, [
    [
        'code' => 'robots_google_sitemap_url_not_fully_qualified',
        'severity' => 'warning',
        'field' => 'sitemap',
        'origin' => 'provider',
        'profile' => 'google',
        'evidence_state' => null,
        'target' => ['scope' => 'robots_rule', 'entry_index' => null, 'item_index' => null, 'line' => 6],
    ],
    [
        'code' => 'robots_google_sitemap_url_not_fully_qualified',
        'severity' => 'warning',
        'field' => 'sitemap',
        'origin' => 'provider',
        'profile' => 'google',
        'evidence_state' => null,
        'target' => ['scope' => 'robots_rule', 'entry_index' => null, 'item_index' => null, 'line' => 7],
    ],
    [
        'code' => 'robots_google_sitemap_url_not_fully_qualified',
        'severity' => 'warning',
        'field' => 'sitemap',
        'origin' => 'provider',
        'profile' => 'google',
        'evidence_state' => null,
        'target' => ['scope' => 'robots_rule', 'entry_index' => null, 'item_index' => null, 'line' => 8],
    ],
    [
        'code' => 'robots_google_sitemap_url_not_fully_qualified',
        'severity' => 'warning',
        'field' => 'sitemap',
        'origin' => 'provider',
        'profile' => 'google',
        'evidence_state' => null,
        'target' => ['scope' => 'robots_rule', 'entry_index' => null, 'item_index' => null, 'line' => 9],
    ],
    [
        'code' => 'robots_google_sitemap_url_not_fully_qualified',
        'severity' => 'warning',
        'field' => 'sitemap',
        'origin' => 'provider',
        'profile' => 'google',
        'evidence_state' => null,
        'target' => ['scope' => 'robots_rule', 'entry_index' => null, 'item_index' => null, 'line' => 10],
    ],
    [
        'code' => 'robots_google_sitemap_url_not_fully_qualified',
        'severity' => 'warning',
        'field' => 'sitemap',
        'origin' => 'provider',
        'profile' => 'google',
        'evidence_state' => null,
        'target' => ['scope' => 'robots_rule', 'entry_index' => null, 'item_index' => null, 'line' => 11],
    ],
    [
        'code' => 'robots_google_sitemap_url_not_fully_qualified',
        'severity' => 'warning',
        'field' => 'sitemap',
        'origin' => 'provider',
        'profile' => 'google',
        'evidence_state' => null,
        'target' => ['scope' => 'robots_rule', 'entry_index' => null, 'item_index' => null, 'line' => 12],
    ],
    [
        'code' => 'robots_google_sitemap_url_not_fully_qualified',
        'severity' => 'warning',
        'field' => 'sitemap',
        'origin' => 'provider',
        'profile' => 'google',
        'evidence_state' => null,
        'target' => ['scope' => 'robots_rule', 'entry_index' => null, 'item_index' => null, 'line' => 13],
    ],
    [
        'code' => 'robots_google_sitemap_url_not_fully_qualified',
        'severity' => 'warning',
        'field' => 'sitemap',
        'origin' => 'provider',
        'profile' => 'google',
        'evidence_state' => null,
        'target' => ['scope' => 'robots_rule', 'entry_index' => null, 'item_index' => null, 'line' => 14],
    ],
    [
        'code' => 'robots_google_sitemap_url_not_fully_qualified',
        'severity' => 'warning',
        'field' => 'sitemap',
        'origin' => 'provider',
        'profile' => 'google',
        'evidence_state' => null,
        'target' => ['scope' => 'robots_rule', 'entry_index' => null, 'item_index' => null, 'line' => 15],
    ],
]);

$metaValidator = new GoogleRobotsMetaValidator();
$indexIfEmbedded = $metaValidator->validate(new RobotsMetaValidationInputDTO(['indexifembedded']));
stack2AssertDiagnostics('indexifembedded provider diagnostic', $indexIfEmbedded, [
    [
        'code' => 'robots_meta_indexifembedded_without_noindex',
        'severity' => 'warning',
        'field' => 'meta',
        'origin' => 'provider',
        'profile' => 'google',
        'evidence_state' => null,
        'target' => ['scope' => 'robots_meta', 'entry_index' => null, 'item_index' => null, 'line' => null],
    ],
]);
stack2AssertDiagnostics('indexifembedded with noindex', $metaValidator->validate(new RobotsMetaValidationInputDTO(['noindex', 'indexifembedded'])), []);
stack2AssertDiagnostics('unavailable_after missing diagnostic', $metaValidator->validate(new RobotsMetaValidationInputDTO(['unavailable_after:   '])), [
    [
        'code' => 'robots_meta_unavailable_after_missing',
        'severity' => 'warning',
        'field' => 'meta',
        'origin' => 'provider',
        'profile' => 'google',
        'evidence_state' => null,
        'target' => ['scope' => 'robots_meta', 'entry_index' => null, 'item_index' => null, 'line' => null],
    ],
]);

foreach (['recognized' => 'info', 'unrecognized' => 'warning', 'unknown' => 'info'] as $state => $severity) {
    $result = $metaValidator->validate(
        new RobotsMetaValidationInputDTO(['unavailable_after:2026-01-01']),
        new SeoValidationContextDTO(['robots_meta.unavailable_after_recognizability' => $state]),
    );
    stack2AssertDiagnostics("unavailable_after {$state} evidence", $result, [
        [
            'code' => 'robots_meta_unavailable_after_recognizability',
            'severity' => $severity,
            'field' => 'meta',
            'origin' => 'provider',
            'profile' => 'google',
            'evidence_state' => $state,
            'target' => ['scope' => 'robots_meta', 'entry_index' => null, 'item_index' => null, 'line' => null],
        ],
    ]);
}

$builder = (new MetaRobotsBuilder())
    ->maxSnippet(-1)
    ->maxVideoPreview(-1)
    ->indexifembedded()
    ->noArchive()
    ->unavailableAfter('as-provided');
stack2AssertSame('builder keeps valid Google -1 helpers and indexifembedded', 'max-snippet:-1, max-video-preview:-1, indexifembedded, noarchive, unavailable_after:as-provided', $builder->build());
stack2AssertThrows('max-snippet below -1 remains invalid', static fn() => (new MetaRobotsBuilder())->maxSnippet(-2));
stack2AssertThrows('max-video-preview below -1 remains invalid', static fn() => (new MetaRobotsBuilder())->maxVideoPreview(-2));

stack2AssertThrows('strict user-agent control injection', static fn() => new RobotsRuleDTO("bot\nInjected"));
stack2AssertThrows('strict Allow control injection', static fn() => new RobotsRuleDTO('*', allow: ["/safe\r\nInjected"]));
stack2AssertThrows('strict Disallow control injection', static fn() => new RobotsRuleDTO('*', disallow: ["/safe\x01"]));
stack2AssertThrows('strict rule comment control injection', static fn() => new RobotsRuleDTO('*', comments: ["comment\x7F"]));
stack2AssertThrows('strict top-level comment control injection', static fn() => new RobotsTxtDTO(comments: ["comment\nInjected"]));
stack2AssertThrows('strict Sitemap control injection', static fn() => new RobotsTxtDTO(sitemaps: ["https://example.com/sitemap.xml\nInjected"]));
stack2AssertTrue('strict leading wildcard compatibility remains accepted', (new RobotsRuleDTO('*', disallow: ['*/private']))->disallow === ['*/private']);
stack2AssertThrows('strict empty Allow behavior remains rejected', static fn() => new RobotsRuleDTO('*', allow: ['']));
stack2AssertThrows('strict empty Disallow behavior remains rejected', static fn() => new RobotsRuleDTO('*', disallow: [' ']));
stack2AssertThrows('strict Unicode Sitemap behavior remains rejected', static fn() => new RobotsTxtDTO(sitemaps: ['https://مثال.com/sitemap.xml']));

$strictRenderer = new RobotsTxtRenderer();
$strictRobots = new RobotsTxtDTO(
    rules: [new RobotsRuleDTO('*', allow: ['/'], disallow: ['/private'], crawlDelay: 10, comments: ['Rule comment'])],
    sitemaps: ['https://example.com/sitemap.xml'],
    comments: ['Top comment'],
);
stack2AssertSame(
    'strict renderer output/order remains unchanged',
    "# Top comment\n\n# Rule comment\nUser-agent: *\nCrawl-delay: 10\nAllow: /\nDisallow: /private\n\nSitemap: https://example.com/sitemap.xml\n",
    $strictRenderer->render($strictRobots),
);

fwrite(STDOUT, "Stack 2 Robots tests passed.\n");
