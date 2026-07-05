<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Web/Robots/MetaRobotsBuilder.php';
require_once __DIR__ . '/../src/Exception/SeoErrorCode.php';
require_once __DIR__ . '/../src/Exception/SeoExceptionInterface.php';
require_once __DIR__ . '/../src/Exception/SeoInvalidArgumentException.php';

use Maatify\Seo\Web\Robots\MetaRobotsBuilder;
use Maatify\Seo\Exception\SeoInvalidArgumentException;

/**
 * Asserts that two values are exactly identical.
 */
function assertSameValue(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        $expectedStr = is_scalar($expected) ? (string) $expected : print_r($expected, true);
        $actualStr = is_scalar($actual) ? (string) $actual : print_r($actual, true);
        throw new \RuntimeException("Assertion failed: $message. Expected: $expectedStr, Actual: $actualStr");
    }
}

/**
 * Asserts that an exception is thrown.
 */
function assertThrowsException(callable $callback, string $expectedExceptionClass, string $message = ''): void
{
    try {
        $callback();
    } catch (\Throwable $e) {
        if (!($e instanceof $expectedExceptionClass)) {
            $actualClass = get_class($e);
            throw new \RuntimeException("Assertion failed: Expected exception $expectedExceptionClass, but got $actualClass. $message");
        }
        return;
    }
    throw new \RuntimeException("Assertion failed: Expected exception $expectedExceptionClass to be thrown. $message");
}

echo "Running Batch 1A MetaRobotsBuilder tests...\n";

// Test: All public methods and insertion order
$builder = new MetaRobotsBuilder();
$builder->index()
        ->follow()
        ->noArchive()
        ->noSnippet()
        ->noImageIndex()
        ->noTranslate()
        ->maxSnippet(50)
        ->maxImagePreview('large')
        ->maxVideoPreview(10)
        ->unavailableAfter('2023-12-31')
        ->add('custom-directive');

assertSameValue(
    'index, follow, noarchive, nosnippet, noimageindex, notranslate, max-snippet:50, max-image-preview:large, max-video-preview:10, unavailable_after:2023-12-31, custom-directive',
    $builder->build(),
    'All public methods and insertion order should be correct'
);

// Test: No duplicates
$builder->clear();
$builder->index()
        ->index()
        ->add('custom')
        ->add('custom');

assertSameValue(
    'index, custom',
    $builder->build(),
    'Directives should not be duplicated'
);

// Test: index/noindex exclusivity
$builder->clear();
$builder->index();
assertSameValue('index', $builder->build());
$builder->noIndex();
assertSameValue('noindex', $builder->build(), 'noIndex should replace index');
$builder->index();
assertSameValue('index', $builder->build(), 'index should replace noindex');
$builder->add('noindex');
assertSameValue('noindex', $builder->build(), 'add(noindex) should replace index');

// Test: follow/nofollow exclusivity
$builder->clear();
$builder->follow();
assertSameValue('follow', $builder->build());
$builder->noFollow();
assertSameValue('nofollow', $builder->build(), 'noFollow should replace follow');
$builder->follow();
assertSameValue('follow', $builder->build(), 'follow should replace nofollow');
$builder->add('nofollow');
assertSameValue('nofollow', $builder->build(), 'add(nofollow) should replace follow');

// Test: max-* replacement
$builder->clear();
$builder->maxSnippet(10)->maxSnippet(20);
assertSameValue('max-snippet:20', $builder->build(), 'max-snippet should replace previous value');
$builder->maxImagePreview('standard')->maxImagePreview('none');
assertSameValue('max-snippet:20, max-image-preview:none', $builder->build(), 'max-image-preview should replace previous value');
$builder->maxVideoPreview(5)->maxVideoPreview(15);
assertSameValue('max-snippet:20, max-image-preview:none, max-video-preview:15', $builder->build(), 'max-video-preview should replace previous value');
$builder->add('max-snippet:30');
assertSameValue('max-image-preview:none, max-video-preview:15, max-snippet:30', $builder->build(), 'add(max-snippet:*) should replace previous value and move to end');

// Test: unavailable_after replacement
$builder->clear();
$builder->unavailableAfter('date1')->unavailableAfter('date2');
assertSameValue('unavailable_after:date2', $builder->build(), 'unavailable_after should replace previous value');
$builder->add('unavailable_after:date3');
assertSameValue('unavailable_after:date3', $builder->build(), 'add(unavailable_after:*) should replace previous value');

// Test: negative max values throw SeoInvalidArgumentException
assertThrowsException(
    fn() => (new MetaRobotsBuilder())->maxSnippet(-1),
    SeoInvalidArgumentException::class,
    'Negative max-snippet should throw'
);
assertThrowsException(
    fn() => (new MetaRobotsBuilder())->maxVideoPreview(-1),
    SeoInvalidArgumentException::class,
    'Negative max-video-preview should throw'
);

// Test: invalid max-image-preview throws SeoInvalidArgumentException
assertThrowsException(
    fn() => (new MetaRobotsBuilder())->maxImagePreview('invalid'),
    SeoInvalidArgumentException::class,
    'Invalid max-image-preview should throw'
);

// Test: Output methods (build, __toString, toArray, toHtml escaping)
$builder->clear();
$builder->index()->add('bad"char>');

assertSameValue('index, bad"char>', $builder->build(), 'build() works');
assertSameValue('index, bad"char>', (string) $builder, '__toString() works');
assertSameValue(['index', 'bad"char>'], $builder->toArray(), 'toArray() works');
assertSameValue(
    '<meta name="robots" content="index, bad&quot;char&gt;">',
    $builder->toHtml(),
    'toHtml() escapes correctly'
);

// Test: has(), remove(), clear()
$builder->clear();
$builder->index()->follow();
assertSameValue(true, $builder->has('index'), 'has() works for existing');
assertSameValue(false, $builder->has('noindex'), 'has() works for missing');

$builder->remove('index');
assertSameValue('follow', $builder->build(), 'remove() works');

$builder->clear();
assertSameValue('', $builder->build(), 'clear() works');

echo "All MetaRobotsBuilder tests passed!\n";
