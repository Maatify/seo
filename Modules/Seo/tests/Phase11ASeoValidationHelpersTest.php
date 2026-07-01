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
use Maatify\Seo\Shared\DTO\MetaTagsDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationResultDTO;
use Maatify\Seo\Web\Validation\SeoMetaValidator;

function assertTrueValue11A(string $label, bool $actual): void
{
    if (!$actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertFalseValue11A(string $label, bool $actual): void
{
    if ($actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertSameValue11A(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertContainsCode11A(string $label, SeoValidationResultDTO $result, string $code): void
{
    foreach ($result->issues as $issue) {
        if ($issue->code === $code) {
            return;
        }
    }

    fwrite(STDERR, "Assertion failed: {$label}\nMissing issue code [{$code}].\n");
    exit(1);
}

function assertThrowsInvalidConfig11A(string $label, callable $callback): void
{
    try {
        $callback();
    } catch (SeoInvalidArgumentException) {
        return;
    }

    fwrite(STDERR, "Assertion failed: {$label}\nExpected SeoInvalidArgumentException.\n");
    exit(1);
}

$valid = SeoMetaValidator::validate([
    'title' => 'A useful product page title',
    'description' => 'This useful product page description is long enough for ordinary search result snippets.',
    'canonical' => 'https://example.com/products/useful',
    'robots' => 'index,follow',
    'openGraph' => [
        'title' => 'A useful product page title',
        'description' => 'This useful product page description is long enough for social previews.',
        'image' => 'https://example.com/og.jpg',
    ],
    'twitter' => [
        'card' => 'summary_large_image',
        'title' => 'A useful product page title',
        'description' => 'This useful product page description is long enough for Twitter previews.',
    ],
    'jsonLd' => [
        ['@context' => 'https://schema.org', '@type' => 'Product', 'name' => 'Useful Product'],
    ],
]);
assertTrueValue11A('valid metadata has no errors', $valid->isValid);
assertFalseValue11A('valid metadata has no warnings', $valid->hasWarnings);
assertSameValue11A('valid metadata issue count', 0, count($valid->issues));

$missingTitle = SeoMetaValidator::validate(['description' => 'This page has a valid description but no title at all.']);
assertFalseValue11A('missing title makes result invalid', $missingTitle->isValid);
assertContainsCode11A('missing title error exists', $missingTitle, 'missing_title');
assertSameValue11A('missing title creates one error', 1, count($missingTitle->errors));

$shortTitle = SeoMetaValidator::validate(['title' => 'Short', 'description' => 'This page has a valid description that exceeds the configured minimum length.']);
assertTrueValue11A('short title warning has no errors', $shortTitle->isValid);
assertTrueValue11A('short title warning sets hasWarnings', $shortTitle->hasWarnings);
assertContainsCode11A('short title warning exists', $shortTitle, 'title_too_short');

$longTitle = SeoMetaValidator::validate(['title' => str_repeat('A', 61), 'description' => 'This page has a valid description that exceeds the configured minimum length.']);
assertContainsCode11A('long title warning exists', $longTitle, 'title_too_long');

$missingDescription = SeoMetaValidator::validate(['title' => 'A valid title']);
assertContainsCode11A('missing description warning exists', $missingDescription, 'missing_description');

$shortDescription = SeoMetaValidator::validate(['title' => 'A valid title', 'description' => 'Too short']);
assertContainsCode11A('short description warning exists', $shortDescription, 'description_too_short');

$longDescription = SeoMetaValidator::validate(['title' => 'A valid title', 'description' => str_repeat('D', 161)]);
assertContainsCode11A('long description warning exists', $longDescription, 'description_too_long');

$invalidCanonical = SeoMetaValidator::validate(['title' => 'A valid title', 'description' => 'This page has a valid description that exceeds the configured minimum length.', 'canonical' => 'not a url']);
assertFalseValue11A('invalid canonical makes result invalid', $invalidCanonical->isValid);
assertContainsCode11A('invalid canonical error exists', $invalidCanonical, 'invalid_canonical');

$requiredCanonical = SeoMetaValidator::validate(['title' => 'A valid title', 'description' => 'This page has a valid description that exceeds the configured minimum length.'], ['requireCanonical' => true]);
assertTrueValue11A('missing required canonical is warning only', $requiredCanonical->isValid);
assertContainsCode11A('required canonical warning exists', $requiredCanonical, 'missing_canonical');

$robotsStringConflict = SeoMetaValidator::validate(['title' => 'A valid title', 'description' => 'This page has a valid description that exceeds the configured minimum length.', 'robots' => 'index,noindex follow']);
assertContainsCode11A('robots index conflict exists', $robotsStringConflict, 'robots_index_conflict');
$robotsArrayConflict = SeoMetaValidator::validate(['title' => 'A valid title', 'description' => 'This page has a valid description that exceeds the configured minimum length.', 'robots' => ['follow', 'nofollow']]);
assertContainsCode11A('robots follow conflict exists', $robotsArrayConflict, 'robots_follow_conflict');

$openGraphMissing = SeoMetaValidator::validate(['title' => 'A valid title', 'description' => 'This page has a valid description that exceeds the configured minimum length.', 'openGraph' => ['title' => 'OG title']]);
assertContainsCode11A('OpenGraph missing description warning exists', $openGraphMissing, 'missing_og_description');
assertContainsCode11A('OpenGraph missing image warning exists', $openGraphMissing, 'missing_og_image');

$twitterMissing = SeoMetaValidator::validate(['title' => 'A valid title', 'description' => 'This page has a valid description that exceeds the configured minimum length.', 'twitter' => ['card' => 'summary']]);
assertContainsCode11A('Twitter missing title warning exists', $twitterMissing, 'missing_twitter_title');
assertContainsCode11A('Twitter missing description warning exists', $twitterMissing, 'missing_twitter_description');

$emptyJsonLd = SeoMetaValidator::validate(['title' => 'A valid title', 'description' => 'This page has a valid description that exceeds the configured minimum length.', 'jsonLd' => []]);
assertContainsCode11A('empty JSON-LD warning exists', $emptyJsonLd, 'invalid_json_ld');
$invalidJsonLd = SeoMetaValidator::validate(['title' => 'A valid title', 'description' => 'This page has a valid description that exceeds the configured minimum length.', 'schema' => ['bad']]);
assertContainsCode11A('invalid JSON-LD entry warning exists', $invalidJsonLd, 'invalid_json_ld_schema');
assertTrueValue11A('JSON-LD warnings are not errors', $invalidJsonLd->isValid);

$warningAndError = SeoMetaValidator::validate(['title' => '', 'description' => 'Short']);
assertFalseValue11A('warnings plus errors is invalid', $warningAndError->isValid);
assertTrueValue11A('warnings plus errors has warnings', $warningAndError->hasWarnings);

$arrayOutput = $warningAndError->toArray();
assertSameValue11A('array output includes is_valid', false, $arrayOutput['is_valid']);
assertSameValue11A('jsonSerialize matches toArray', $arrayOutput, $warningAndError->jsonSerialize());
$json = json_encode($warningAndError, JSON_THROW_ON_ERROR);
assertTrueValue11A('JSON serialization includes issues', str_contains($json, 'missing_title'));

$objectResult = SeoMetaValidator::validate(new MetaTagsDTO(
    title: 'Object style metadata',
    description: 'This object style metadata description is long enough for validation checks.',
    canonicalUrl: 'https://example.com/object',
    robots: 'index,follow',
));
assertTrueValue11A('object style metadata is supported', $objectResult->isValid);

assertThrowsInvalidConfig11A('invalid min/max configuration throws module exception', static function (): void {
    SeoMetaValidator::validate(['title' => 'A valid title'], ['titleMinLength' => 70, 'titleMaxLength' => 60]);
});
assertThrowsInvalidConfig11A('invalid option type throws module exception', static function (): void {
    SeoMetaValidator::validate(['title' => 'A valid title'], ['requireCanonical' => 'yes']);
});

assertFalseValue11A('validator does not send headers', headers_sent());

fwrite(STDOUT, "Phase 11A SEO validation helpers tests passed.\n");
