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

use Maatify\Seo\Shared\DTO\MetaTagsDTO;
use Maatify\Seo\Web\Social\OpenGraphBuilder;
use Maatify\Seo\Web\Social\SocialImage;
use Maatify\Seo\Web\Validation\DTO\SeoCompanionValidationResultDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationContextDTO;
use Maatify\Seo\Web\Validation\Profile\OpenGraphProtocolValidator;
use Maatify\Seo\Web\Validation\SeoMetaValidator;
use Maatify\Seo\Web\Validation\SeoValidationReportBuilder;
use Maatify\Seo\Web\Validation\SeoValidationScoreCalculator;
$failures = 0;

function stack5AssertSame(string $label, mixed $expected, mixed $actual): void
{
    global $failures;
    if ($expected !== $actual) {
        ++$failures;
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
    }
}

function stack5AssertTrue(string $label, bool $actual): void
{
    stack5AssertSame($label, true, $actual);
}

function stack5AssertFalse(string $label, bool $actual): void
{
    stack5AssertSame($label, false, $actual);
}

/** @return list<string> */
function stack5Codes(SeoCompanionValidationResultDTO $result): array
{
    $codes = [];
    foreach ($result->diagnostics as $diagnostic) {
        $codes[] = $diagnostic->code;
    }

    return $codes;
}

function stack5HasCode(SeoCompanionValidationResultDTO $result, string $code): bool
{
    return in_array($code, stack5Codes($result), true);
}

function stack5CountCode(SeoCompanionValidationResultDTO $result, string $code): int
{
    return count(array_keys(stack5Codes($result), $code, true));
}

function stack5AssertDiagnosticContract(SeoCompanionValidationResultDTO $result, string $code, string $field): void
{
    $matching = [];
    foreach ($result->toArray()['diagnostics'] as $diagnostic) {
        if ($diagnostic['code'] === $code) {
            $matching[] = $diagnostic;
        }
    }

    stack5AssertSame("{$code} exact occurrence count", 1, count($matching));
    if (count($matching) !== 1) {
        return;
    }

    $diagnostic = $matching[0];
    stack5AssertSame("{$code} severity", 'warning', $diagnostic['severity']);
    stack5AssertSame("{$code} field", $field, $diagnostic['field']);
    stack5AssertSame("{$code} origin", 'protocol', $diagnostic['origin']);
    stack5AssertSame("{$code} profile", 'ogp', $diagnostic['profile']);
    stack5AssertSame("{$code} evidence state", null, $diagnostic['evidence_state']);
    stack5AssertSame("{$code} related legacy code", null, $diagnostic['related_legacy_code']);
    stack5AssertSame("{$code} target", ['scope' => 'meta', 'entry_index' => null, 'item_index' => null, 'line' => null], $diagnostic['target']);
    stack5AssertTrue("{$code} message is non-empty", is_string($diagnostic['message']) && $diagnostic['message'] !== '');
}

/** @param array<string, mixed> $meta */
function stack5ValidMeta(array $meta = []): array
{
    return array_merge([
        'title' => 'A valid page title',
        'description' => 'This is a sufficiently long description for the Stack 5 validation architecture tests.',
    ], $meta);
}

/** @return list<string> */
function stack5ReflectionTypeNames(?ReflectionType $type): array
{
    if ($type instanceof \ReflectionUnionType) {
        $names = [];
        foreach ($type->getTypes() as $namedType) {
            if ($namedType instanceof \ReflectionNamedType) {
                $names[] = $namedType->getName();
            }
        }

        sort($names);

        return $names;
    }

    return $type instanceof \ReflectionNamedType ? [$type->getName()] : [];
}

/**
 * @param list<array{name: string, types: list<string>, allowsNull: bool, hasDefault: bool, default: mixed}> $expectedParameters
 */
function stack5AssertMethodSignature(string $label, string $class, string $methodName, array $expectedParameters, string $returnType): void
{
    $method = new \ReflectionMethod($class, $methodName);
    stack5AssertSame($label . ' return type', $returnType, $method->getReturnType()?->getName());

    $parameters = $method->getParameters();
    stack5AssertSame($label . ' parameter count', count($expectedParameters), count($parameters));
    foreach ($expectedParameters as $index => $expected) {
        $parameter = $parameters[$index] ?? null;
        if (!$parameter instanceof \ReflectionParameter) {
            continue;
        }

        stack5AssertSame($label . " parameter {$index} name", $expected['name'], $parameter->getName());
        $expectedTypes = $expected['types'];
        sort($expectedTypes);
        stack5AssertSame($label . " parameter {$index} types", $expectedTypes, stack5ReflectionTypeNames($parameter->getType()));
        stack5AssertSame($label . " parameter {$index} allows null", $expected['allowsNull'], $parameter->allowsNull());
        stack5AssertSame($label . " parameter {$index} has default", $expected['hasDefault'], $parameter->isDefaultValueAvailable());
        if ($expected['hasDefault']) {
            stack5AssertSame($label . " parameter {$index} default", $expected['default'], $parameter->getDefaultValue());
        }
    }
}

function stack5AssertProfileSignature(string $label, string $class, string $inputType, string $inputName = 'input'): void
{
    stack5AssertMethodSignature(
        $label,
        $class,
        'validate',
        [
            ['name' => $inputName, 'types' => [$inputType], 'allowsNull' => false, 'hasDefault' => false, 'default' => null],
            ['name' => 'context', 'types' => [SeoValidationContextDTO::class], 'allowsNull' => true, 'hasDefault' => true, 'default' => null],
        ],
        SeoCompanionValidationResultDTO::class,
    );
}

/** @return array<string, mixed>|null */
function stack5LegacyIssue(array $legacy, string $code): ?array
{
    foreach ($legacy['issues'] as $issue) {
        if ($issue['code'] === $code) {
            return $issue;
        }
    }

    return null;
}

/** @param array<string, mixed> $meta */
function stack5AssertClassification(string $code, array $meta, string $field, string $origin, string $profile): void
{
    $legacyResult = SeoMetaValidator::validate($meta);
    $companion = SeoMetaValidator::validateWithCompanion($meta);
    $legacy = $legacyResult->toArray();
    $legacyIssue = stack5LegacyIssue($legacy, $code);
    stack5AssertTrue("{$code} underlying legacy issue exists", $legacyIssue !== null);
    stack5AssertSame("{$code} classification count", 1, stack5CountCode($companion, $code));

    $matching = [];
    foreach ($companion->toArray()['diagnostics'] as $diagnostic) {
        if ($diagnostic['code'] === $code) {
            $matching[] = $diagnostic;
        }
    }

    if (count($matching) !== 1 || $legacyIssue === null) {
        return;
    }

    $diagnostic = $matching[0];
    stack5AssertSame("{$code} severity matches legacy", $legacyIssue['severity'], $diagnostic['severity']);
    stack5AssertSame("{$code} message correlates with legacy", $legacyIssue['message'], $diagnostic['message']);
    stack5AssertSame("{$code} field", $field, $diagnostic['field']);
    stack5AssertSame("{$code} origin", $origin, $diagnostic['origin']);
    stack5AssertSame("{$code} profile", $profile, $diagnostic['profile']);
    stack5AssertSame("{$code} evidence state", null, $diagnostic['evidence_state']);
    stack5AssertSame("{$code} related legacy code", $code, $diagnostic['related_legacy_code']);
    stack5AssertSame("{$code} target", ['scope' => 'meta', 'entry_index' => null, 'item_index' => null, 'line' => null], $diagnostic['target']);
    stack5AssertSame("{$code} legacy result is unchanged", $legacy, $companion->legacy?->toArray());
    stack5AssertSame("{$code} score is unchanged", SeoValidationScoreCalculator::score($legacyResult)->toArray(), SeoValidationScoreCalculator::score($companion->legacy ?? $legacyResult)->toArray());
}

// 1. Fixed public signatures, including the unchanged Stack 2/4 profile surface.
stack5AssertMethodSignature(
    'SeoMetaValidator::validate',
    SeoMetaValidator::class,
    'validate',
    [
        ['name' => 'meta', 'types' => ['array', 'object'], 'allowsNull' => false, 'hasDefault' => false, 'default' => null],
        ['name' => 'options', 'types' => ['array'], 'allowsNull' => false, 'hasDefault' => true, 'default' => []],
    ],
    'Maatify\\Seo\\Web\\Validation\\DTO\\SeoValidationResultDTO',
);
stack5AssertMethodSignature(
    'SeoMetaValidator::validateWithCompanion',
    SeoMetaValidator::class,
    'validateWithCompanion',
    [
        ['name' => 'meta', 'types' => ['array', 'object'], 'allowsNull' => false, 'hasDefault' => false, 'default' => null],
        ['name' => 'options', 'types' => ['array'], 'allowsNull' => false, 'hasDefault' => true, 'default' => []],
        ['name' => 'context', 'types' => [SeoValidationContextDTO::class], 'allowsNull' => true, 'hasDefault' => true, 'default' => null],
    ],
    SeoCompanionValidationResultDTO::class,
);
stack5AssertMethodSignature(
    'OpenGraphProtocolValidator::validate',
    OpenGraphProtocolValidator::class,
    'validate',
    [
        ['name' => 'meta', 'types' => ['array', 'object'], 'allowsNull' => false, 'hasDefault' => false, 'default' => null],
        ['name' => 'options', 'types' => ['array'], 'allowsNull' => false, 'hasDefault' => true, 'default' => []],
        ['name' => 'context', 'types' => [SeoValidationContextDTO::class], 'allowsNull' => true, 'hasDefault' => true, 'default' => null],
    ],
    SeoCompanionValidationResultDTO::class,
);

stack5AssertProfileSignature('RFC 9309 profile signature', 'Maatify\\Seo\\Web\\Validation\\Profile\\Rfc9309RobotsValidator', 'Maatify\\Seo\\Web\\Validation\\Input\\RobotsTxtValidationInputDTO');
stack5AssertProfileSignature('Google robots.txt profile signature', 'Maatify\\Seo\\Web\\Validation\\Profile\\GoogleRobotsTxtValidator', 'Maatify\\Seo\\Web\\Validation\\Input\\RobotsTxtValidationInputDTO');
stack5AssertProfileSignature('Google robots meta profile signature', 'Maatify\\Seo\\Web\\Validation\\Profile\\GoogleRobotsMetaValidator', 'Maatify\\Seo\\Web\\Validation\\Input\\RobotsMetaValidationInputDTO');
stack5AssertProfileSignature('Google Sitemap profile signature', 'Maatify\\Seo\\Web\\Validation\\Profile\\GoogleSitemapValidator', 'Maatify\\Seo\\Web\\Validation\\Input\\Sitemap\\SitemapValidationDocumentDTO', 'document');
stack5AssertProfileSignature('Google Image profile signature', 'Maatify\\Seo\\Web\\Validation\\Profile\\GoogleImageSitemapValidator', 'Maatify\\Seo\\Web\\Validation\\Input\\Sitemap\\SitemapValidationDocumentDTO', 'document');
stack5AssertProfileSignature('Google Video profile signature', 'Maatify\\Seo\\Web\\Validation\\Profile\\GoogleVideoSitemapValidator', 'Maatify\\Seo\\Web\\Validation\\Input\\Sitemap\\SitemapValidationDocumentDTO', 'document');
stack5AssertProfileSignature('Google News profile signature', 'Maatify\\Seo\\Web\\Validation\\Profile\\GoogleNewsSitemapValidator', 'Maatify\\Seo\\Web\\Validation\\Input\\Sitemap\\SitemapValidationDocumentDTO', 'document');

// 2. Legacy result identity for array/object input and custom options.
$arrayMeta = stack5ValidMeta(['openGraph' => ['title' => 'OG title']]);
$arrayLegacy = SeoMetaValidator::validate($arrayMeta);
$arrayCompanion = SeoMetaValidator::validateWithCompanion($arrayMeta);
stack5AssertSame('array legacy result is byte-for-byte equal by JSON', json_encode($arrayLegacy, JSON_THROW_ON_ERROR), json_encode($arrayCompanion->legacy, JSON_THROW_ON_ERROR));
stack5AssertSame('array legacy result has identical serialized shape', $arrayLegacy->toArray(), $arrayCompanion->legacy?->toArray());

$objectMeta = new MetaTagsDTO(
    title: 'Object style metadata',
    description: 'This object metadata description is long enough for the Stack 5 validation architecture tests.',
    canonicalUrl: 'https://example.com/object',
    openGraphTitle: 'Object OG title',
    openGraphDescription: 'Object OG description',
    openGraphUrl: 'https://example.com/object-og',
    openGraphType: 'article',
    openGraphImage: 'https://example.com/object-image.jpg',
);
$objectLegacy = SeoMetaValidator::validate($objectMeta);
$objectCompanion = SeoMetaValidator::validateWithCompanion($objectMeta);
stack5AssertSame('MetaTagsDTO legacy result is byte-for-byte equal by JSON', json_encode($objectLegacy, JSON_THROW_ON_ERROR), json_encode($objectCompanion->legacy, JSON_THROW_ON_ERROR));
stack5AssertSame('MetaTagsDTO legacy result has identical serialized shape', $objectLegacy->toArray(), $objectCompanion->legacy?->toArray());

$customMeta = stack5ValidMeta(['title' => 'Tiny', 'description' => 'Short']);
$customOptions = [
    'titleMinLength' => 5,
    'titleMaxLength' => 10,
    'descriptionMinLength' => 5,
    'descriptionMaxLength' => 20,
];
$customLegacy = SeoMetaValidator::validate($customMeta, $customOptions);
$customCompanion = SeoMetaValidator::validateWithCompanion($customMeta, $customOptions);
stack5AssertSame('custom options preserve legacy result', $customLegacy->toArray(), $customCompanion->legacy?->toArray());
stack5AssertSame('custom options preserve legacy JSON', json_encode($customLegacy, JSON_THROW_ON_ERROR), json_encode($customCompanion->legacy, JSON_THROW_ON_ERROR));

// 3. Every fixed legacy classification row appears once, only when its legacy issue exists.
stack5AssertClassification('title_too_short', stack5ValidMeta(['title' => 'Short']), 'title', 'heuristic', 'seo-default');
stack5AssertClassification('title_too_long', stack5ValidMeta(['title' => str_repeat('T', 61)]), 'title', 'heuristic', 'seo-default');
stack5AssertClassification('description_too_short', stack5ValidMeta(['description' => 'Short']), 'description', 'heuristic', 'seo-default');
stack5AssertClassification('description_too_long', stack5ValidMeta(['description' => str_repeat('D', 161)]), 'description', 'heuristic', 'seo-default');
stack5AssertClassification('missing_og_title', stack5ValidMeta([
    'openGraph' => [
        'description' => 'OG description',
        'image' => 'https://example.com/image.jpg',
        'type' => 'website',
        'url' => 'https://example.com/page',
    ],
]), 'og:title', 'protocol', 'ogp');
stack5AssertClassification('missing_og_description', stack5ValidMeta([
    'openGraph' => [
        'title' => 'OG title',
        'image' => 'https://example.com/image.jpg',
        'type' => 'website',
        'url' => 'https://example.com/page',
    ],
]), 'og:description', 'heuristic', 'seo-default');
stack5AssertClassification('missing_og_image', stack5ValidMeta([
    'openGraph' => [
        'title' => 'OG title',
        'description' => 'OG description',
        'type' => 'website',
        'url' => 'https://example.com/page',
    ],
]), 'og:image', 'protocol', 'ogp');

$completeOpenGraph = stack5ValidMeta([
    'openGraph' => [
        'title' => 'OG title',
        'description' => 'OG description',
        'image' => 'https://example.com/image.jpg',
        'type' => 'website',
        'url' => 'https://example.com/page',
    ],
]);
$completeResult = SeoMetaValidator::validateWithCompanion($completeOpenGraph);
stack5AssertSame('no absent legacy issue produces a classification', [], stack5Codes($completeResult));

// 4. strlen() byte semantics and the existing warning deduction remain unchanged.
stack5AssertSame('Arabic test value is two UTF-8 bytes', 2, strlen('م'));
$asciiBoundary = SeoMetaValidator::validate(
    ['title' => 'AB', 'description' => 'CD'],
    ['titleMinLength' => 2, 'titleMaxLength' => 2, 'descriptionMinLength' => 2, 'descriptionMaxLength' => 2],
);
stack5AssertSame('ASCII exact byte boundaries produce no length issue', [], array_values(array_filter(array_map(static fn ($issue): string => $issue->code, $asciiBoundary->issues), static fn (string $code): bool => str_contains($code, '_too_'))));

$unicodeBoundary = SeoMetaValidator::validate(
    ['title' => 'م', 'description' => 'م'],
    ['titleMinLength' => 2, 'titleMaxLength' => 2, 'descriptionMinLength' => 2, 'descriptionMaxLength' => 2],
);
stack5AssertSame('Arabic exact byte boundaries produce no length issue', [], array_values(array_filter(array_map(static fn ($issue): string => $issue->code, $unicodeBoundary->issues), static fn (string $code): bool => str_contains($code, '_too_'))));

$unicodeTitleShort = SeoMetaValidator::validate(
    ['title' => 'م', 'description' => 'م'],
    ['titleMinLength' => 3, 'titleMaxLength' => 3, 'descriptionMinLength' => 2, 'descriptionMaxLength' => 2],
);
stack5AssertTrue('Arabic title uses byte measurement at custom boundary', in_array('title_too_short', array_map(static fn ($issue): string => $issue->code, $unicodeTitleShort->issues), true));

$unicodeDescriptionShort = SeoMetaValidator::validate(
    ['title' => 'م', 'description' => 'م'],
    ['titleMinLength' => 2, 'titleMaxLength' => 2, 'descriptionMinLength' => 3, 'descriptionMaxLength' => 3],
);
stack5AssertTrue('Arabic description uses byte measurement at custom boundary', in_array('description_too_short', array_map(static fn ($issue): string => $issue->code, $unicodeDescriptionShort->issues), true));

$oneWarning = SeoMetaValidator::validate(['title' => 'A', 'description' => 'CD'], ['titleMinLength' => 2, 'titleMaxLength' => 10, 'descriptionMinLength' => 2, 'descriptionMaxLength' => 2]);
stack5AssertSame('existing default warning deduction remains five points', 95, SeoValidationScoreCalculator::score($oneWarning)->score);
stack5AssertSame('classification metadata does not add a score deduction', SeoValidationScoreCalculator::score($oneWarning)->toArray(), SeoValidationScoreCalculator::score(SeoMetaValidator::validateWithCompanion(['title' => 'A', 'description' => 'CD'], ['titleMinLength' => 2, 'titleMaxLength' => 10, 'descriptionMinLength' => 2, 'descriptionMaxLength' => 2])->legacy ?? $oneWarning)->toArray());

// 5. Presence-triggered activation: type and URL alone never activate OGP.
$activationSignals = [
    'nested openGraph array' => ['openGraph' => []],
    'nested openGraph object' => ['openGraph' => new stdClass()],
    'nested og array' => ['og' => []],
    'camel title' => ['openGraphTitle' => 'OG title'],
    'snake title' => ['open_graph_title' => 'OG title'],
    'camel description' => ['openGraphDescription' => 'OG description'],
    'snake description' => ['open_graph_description' => 'OG description'],
    'camel image' => ['openGraphImage' => 'https://example.com/image.jpg'],
    'snake image' => ['open_graph_image' => 'https://example.com/image.jpg'],
];
foreach ($activationSignals as $label => $signal) {
    $result = SeoMetaValidator::validateWithCompanion(stack5ValidMeta($signal));
    stack5AssertTrue("{$label} activates missing type", stack5HasCode($result, 'missing_og_type'));
    stack5AssertTrue("{$label} activates missing URL", stack5HasCode($result, 'missing_og_url'));
}

$noSignal = SeoMetaValidator::validateWithCompanion(stack5ValidMeta());
stack5AssertSame('no Open Graph signal emits no companion diagnostics', [], stack5Codes($noSignal));

foreach ([
    'openGraphType' => ['openGraphType' => 'website'],
    'openGraphUrl' => ['openGraphUrl' => 'https://example.com/page'],
    'og:type' => ['og:type' => 'website'],
    'og:url' => ['og:url' => 'https://example.com/page'],
] as $label => $signal) {
    $result = SeoMetaValidator::validateWithCompanion(stack5ValidMeta($signal));
    stack5AssertFalse("{$label} alone does not activate OGP", stack5HasCode($result, 'missing_og_type') || stack5HasCode($result, 'missing_og_url') || stack5HasCode($result, 'missing_og_title') || stack5HasCode($result, 'missing_og_image') || stack5HasCode($result, 'missing_og_description'));
}

// 6. Required basics and anti-duplication behavior.
$missingTitle = SeoMetaValidator::validateWithCompanion(stack5ValidMeta([
    'openGraph' => ['description' => 'OG description', 'image' => 'https://example.com/image.jpg', 'type' => 'website', 'url' => 'https://example.com/page'],
]));
stack5AssertSame('missing title keeps one legacy classification', 1, stack5CountCode($missingTitle, 'missing_og_title'));
stack5AssertFalse('missing title has no duplicate new diagnostic', stack5HasCode($missingTitle, 'ogp_missing_title'));
stack5AssertFalse('present type suppresses missing type', stack5HasCode($missingTitle, 'missing_og_type'));
stack5AssertFalse('present URL suppresses missing URL', stack5HasCode($missingTitle, 'missing_og_url'));

$missingImage = SeoMetaValidator::validateWithCompanion(stack5ValidMeta([
    'openGraph' => ['title' => 'OG title', 'description' => 'OG description', 'type' => 'website', 'url' => 'https://example.com/page'],
]));
stack5AssertSame('missing image keeps one legacy classification', 1, stack5CountCode($missingImage, 'missing_og_image'));
stack5AssertFalse('missing image has no duplicate new diagnostic', stack5HasCode($missingImage, 'ogp_missing_image'));

$missingDescription = SeoMetaValidator::validateWithCompanion(stack5ValidMeta([
    'openGraph' => ['title' => 'OG title', 'image' => 'https://example.com/image.jpg', 'type' => 'website', 'url' => 'https://example.com/page'],
]));
stack5AssertSame('missing description is one heuristic classification', 1, stack5CountCode($missingDescription, 'missing_og_description'));
stack5AssertFalse('missing description has no OGP protocol diagnostic', stack5HasCode($missingDescription, 'ogp_missing_description'));

$missingType = SeoMetaValidator::validateWithCompanion(stack5ValidMeta([
    'openGraph' => ['title' => 'OG title', 'description' => 'OG description', 'image' => 'https://example.com/image.jpg', 'url' => 'https://example.com/page'],
]));
stack5AssertDiagnosticContract($missingType, 'missing_og_type', 'og:type');
stack5AssertFalse('missing type does not enter legacy result', in_array('missing_og_type', $missingType->legacy?->toArray()['issues'] ? array_column($missingType->legacy->toArray()['issues'], 'code') : [], true));

$missingUrl = SeoMetaValidator::validateWithCompanion(stack5ValidMeta([
    'openGraph' => ['title' => 'OG title', 'description' => 'OG description', 'image' => 'https://example.com/image.jpg', 'type' => 'website'],
]));
stack5AssertDiagnosticContract($missingUrl, 'missing_og_url', 'og:url');
stack5AssertFalse('missing URL does not enter legacy result', in_array('missing_og_url', $missingUrl->legacy?->toArray()['issues'] ? array_column($missingUrl->legacy->toArray()['issues'], 'code') : [], true));

$completeResult = SeoMetaValidator::validateWithCompanion($completeOpenGraph);
stack5AssertFalse('complete title has no title classification', stack5HasCode($completeResult, 'missing_og_title'));
stack5AssertFalse('complete image has no image classification', stack5HasCode($completeResult, 'missing_og_image'));
stack5AssertFalse('complete OGP has no missing type', stack5HasCode($completeResult, 'missing_og_type'));
stack5AssertFalse('complete OGP has no missing URL', stack5HasCode($completeResult, 'missing_og_url'));

// 7. New diagnostics are companion-only and do not change legacy validity, reports, counts, grade, health, or score.
$newOnlyMeta = stack5ValidMeta([
    'openGraph' => ['title' => 'OG title', 'description' => 'OG description', 'image' => 'https://example.com/image.jpg'],
]);
$newOnlyLegacy = SeoMetaValidator::validate($newOnlyMeta);
$newOnlyCompanion = SeoMetaValidator::validateWithCompanion($newOnlyMeta);
stack5AssertTrue('new OGP diagnostics keep legacy valid', $newOnlyCompanion->legacy?->isValid ?? false);
stack5AssertFalse('new OGP diagnostics keep legacy warning flag false', $newOnlyCompanion->legacy?->hasWarnings ?? true);
stack5AssertSame('new OGP diagnostics are absent from legacy issue codes', [], array_values(array_filter(array_column($newOnlyCompanion->legacy?->toArray()['issues'] ?? [], 'code'), static fn (mixed $code): bool => in_array($code, ['missing_og_type', 'missing_og_url'], true))));
stack5AssertSame('new OGP diagnostics do not affect score', 100, SeoValidationScoreCalculator::score($newOnlyLegacy)->score);
$newOnlyReport = SeoValidationReportBuilder::build($newOnlyMeta);
stack5AssertTrue('new OGP diagnostics keep report valid', $newOnlyReport->isValid);
stack5AssertTrue('new OGP diagnostics keep report healthy', $newOnlyReport->isHealthy);
stack5AssertSame('new OGP diagnostics keep report score', 100, $newOnlyReport->score);
stack5AssertSame('new OGP diagnostics keep report grade', 'A', $newOnlyReport->grade);
stack5AssertSame('new OGP diagnostics keep report counts', [0, 0, 0], [$newOnlyReport->errorCount, $newOnlyReport->warningCount, $newOnlyReport->infoCount]);
stack5AssertSame('new OGP diagnostics keep report issues empty', [], $newOnlyReport->issues);

$nonMetaInput = stack5ValidMeta([
    'robots' => 'index,noindex',
    'canonical' => 'not-a-url',
    'jsonLd' => [],
    'openGraph' => ['title' => 'OG title', 'description' => 'OG description', 'image' => 'https://example.com/image.jpg'],
]);
$nonMetaCompanion = SeoMetaValidator::validateWithCompanion($nonMetaInput);
stack5AssertSame('validateWithCompanion only emits fixed OGP companion codes', ['missing_og_type', 'missing_og_url'], stack5Codes($nonMetaCompanion));
stack5AssertFalse('validateWithCompanion does not orchestrate robots profiles', stack5HasCode($nonMetaCompanion, 'robots_google_present_path_leading_slash'));
stack5AssertFalse('validateWithCompanion does not orchestrate sitemap profiles', stack5HasCode($nonMetaCompanion, 'sitemap_loc_invalid_uri_iri'));
stack5AssertFalse('validateWithCompanion does not orchestrate canonical profiles', stack5HasCode($nonMetaCompanion, 'canonical_relative_provider_best_practice'));
stack5AssertFalse('validateWithCompanion does not orchestrate hreflang profiles', stack5HasCode($nonMetaCompanion, 'hreflang_url_not_fully_qualified'));

// 8. OpenGraphProtocolValidator delegates to the same rule source and always embeds legacy.
$context = new SeoValidationContextDTO(['unknown.evidence.key' => 'ignored']);
$viaMeta = SeoMetaValidator::validateWithCompanion($newOnlyMeta, [], $context);
$viaProfile = (new OpenGraphProtocolValidator())->validate($newOnlyMeta, [], $context);
stack5AssertSame('OGP delegation has identical array output', $viaMeta->toArray(), $viaProfile->toArray());
stack5AssertSame('OGP delegation has identical JSON output', json_encode($viaMeta, JSON_THROW_ON_ERROR), json_encode($viaProfile, JSON_THROW_ON_ERROR));
stack5AssertSame('unknown evidence keys do not change OGP semantics', $viaMeta->toArray(), SeoMetaValidator::validateWithCompanion($newOnlyMeta)->toArray());

$standaloneNoSignal = (new OpenGraphProtocolValidator())->validate(stack5ValidMeta());
stack5AssertTrue('standalone OGP profile always embeds legacy', $standaloneNoSignal->legacy !== null);
stack5AssertSame('standalone OGP profile without signal has no diagnostics', [], stack5Codes($standaloneNoSignal));

// 9. OpenGraph builder/output and existing Twitter/MetaTagsDTO contracts remain unchanged.
$builder = (new OpenGraphBuilder())
    ->setTitle('Title')
    ->setDescription('Description')
    ->setType('article')
    ->setUrl('https://example.com/page')
    ->setSiteName('Example')
    ->setLocale('en_US')
    ->setDeterminer('the')
    ->setAudio('https://example.com/audio.mp3')
    ->setVideo('https://example.com/video.mp4')
    ->setImage((new SocialImage('https://example.com/first.jpg'))->setAlt('First alt'))
    ->addImage((new SocialImage('https://example.com/second.jpg'))->setSecureUrl('https://secure.example.com/second.jpg')->setType('image/jpeg')->setWidth(800)->setHeight(600));
$builderTags = $builder->toArray();
stack5AssertSame('OpenGraphBuilder scalar ordering is preserved', ['og:title', 'og:description', 'og:type', 'og:url', 'og:site_name', 'og:locale', 'og:determiner', 'og:audio', 'og:video'], array_column(array_slice($builderTags, 0, 9), 'name'));
stack5AssertSame('OpenGraphBuilder first image remains preferred', 'https://example.com/first.jpg', $builderTags[9]['content']);
stack5AssertSame('OpenGraphBuilder first image structured property stays attached', 'og:image:alt', $builderTags[10]['name']);
stack5AssertSame('OpenGraphBuilder second image follows its root', ['og:image', 'og:image:secure_url', 'og:image:type', 'og:image:width', 'og:image:height'], array_column(array_slice($builderTags, 11), 'name'));

$metaTags = new MetaTagsDTO(
    'A <Title> & "Quote"',
    'Desc <b>bold</b> & "quoted"',
    'https://example.com/page?a=1&b=<x>',
    'noindex,nofollow',
    'OG <Title>',
    'OG desc & details',
    'https://example.com/og?x=1&y=2',
    'Twitter <Title>',
    'Twitter desc & details',
    'article',
    'https://example.com/image.jpg?name=<hero>&size=large',
    'summary_large_image',
    'https://example.com/twitter.jpg?name=<hero>&size=large',
);
stack5AssertSame('MetaTagsDTO JSON shape remains unchanged', '{"title":"A <Title> & \\"Quote\\"","description":"Desc <b>bold<\\/b> & \\"quoted\\"","canonical_url":"https:\\/\\/example.com\\/page?a=1&b=<x>","robots":"noindex,nofollow","open_graph_title":"OG <Title>","open_graph_description":"OG desc & details","open_graph_url":"https:\\/\\/example.com\\/og?x=1&y=2","twitter_title":"Twitter <Title>","twitter_description":"Twitter desc & details","open_graph_type":"article","open_graph_image":"https:\\/\\/example.com\\/image.jpg?name=<hero>&size=large","twitter_card":"summary_large_image","twitter_image":"https:\\/\\/example.com\\/twitter.jpg?name=<hero>&size=large"}', json_encode($metaTags, JSON_THROW_ON_ERROR));

$twitterLegacy = SeoMetaValidator::validate(stack5ValidMeta(['twitter' => ['card' => 'summary']]));
stack5AssertTrue('existing Twitter missing title behavior remains', in_array('missing_twitter_title', array_map(static fn ($issue): string => $issue->code, $twitterLegacy->issues), true));
stack5AssertTrue('existing Twitter missing description behavior remains', in_array('missing_twitter_description', array_map(static fn ($issue): string => $issue->code, $twitterLegacy->issues), true));

if ($failures > 0) {
    fwrite(STDERR, "Stack 5 validation architecture/Open Graph tests failed with {$failures} failures.\n");
    exit(1);
}

fwrite(STDOUT, "Stack 5 validation architecture/Open Graph tests passed.\n");
