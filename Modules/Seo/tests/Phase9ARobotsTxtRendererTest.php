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
use Maatify\Seo\Web\Robots\RobotsTxtRenderer;

function assertSameValue(string $label, string $expected, string $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n{$expected}\nActual:\n{$actual}\n");
        exit(1);
    }
}

function assertException(string $label, \Closure $callback, string $expectedExceptionClass, string $expectedMessage): void
{
    try {
        $callback();
        fwrite(STDERR, "Assertion failed: {$label}\nExpected exception {$expectedExceptionClass} was not thrown.\n");
        exit(1);
    } catch (\Throwable $e) {
        if (!($e instanceof $expectedExceptionClass)) {
            fwrite(STDERR, "Assertion failed: {$label}\nExpected exception {$expectedExceptionClass}, but got " . get_class($e) . ".\n");
            exit(1);
        }
        if ($e->getMessage() !== $expectedMessage) {
            fwrite(STDERR, "Assertion failed: {$label}\nExpected message:\n{$expectedMessage}\nActual:\n{$e->getMessage()}\n");
            exit(1);
        }
    }
}

$renderer = new RobotsTxtRenderer();

// testRenderSingleRule
$rule = new RobotsRuleDTO(
    userAgent: '*',
    allow: ['/'],
    disallow: ['/admin']
);
$dto = new RobotsTxtDTO(rules: [$rule]);

$expected = <<<TXT
User-agent: *
Allow: /
Disallow: /admin

TXT;

assertSameValue('testRenderSingleRule', $expected, $renderer->render($dto));

// testRenderMultipleRules
$rule1 = new RobotsRuleDTO(
    userAgent: '*',
    allow: ['/'],
    disallow: ['/admin']
);
$rule2 = new RobotsRuleDTO(
    userAgent: 'Googlebot',
    allow: ['/images'],
    disallow: ['/private']
);
$dto = new RobotsTxtDTO(rules: [$rule1, $rule2]);

$expected = <<<TXT
User-agent: *
Allow: /
Disallow: /admin

User-agent: Googlebot
Allow: /images
Disallow: /private

TXT;

assertSameValue('testRenderMultipleRules', $expected, $renderer->render($dto));

// testRenderWithSitemapAndComments
$rule = new RobotsRuleDTO(
    userAgent: '*',
    allow: ['/'],
    disallow: ['/admin'],
    comments: ['Rule comment', '', '   ']
);
$dto = new RobotsTxtDTO(
    rules: [$rule],
    sitemaps: ['https://example.com/sitemap.xml'],
    comments: ['Global comment']
);

$expected = <<<TXT
# Global comment

# Rule comment
User-agent: *
Allow: /
Disallow: /admin

Sitemap: https://example.com/sitemap.xml

TXT;

assertSameValue('testRenderWithSitemapAndComments', $expected, $renderer->render($dto));

// testRenderWithCrawlDelay
$rule = new RobotsRuleDTO(
    userAgent: '*',
    crawlDelay: 10
);
$dto = new RobotsTxtDTO(rules: [$rule]);

$expected = <<<TXT
User-agent: *
Crawl-delay: 10

TXT;

assertSameValue('testRenderWithCrawlDelay', $expected, $renderer->render($dto));

// testEmptyUserAgentThrowsException
assertException(
    'testEmptyUserAgentThrowsException',
    static fn() => new RobotsRuleDTO(userAgent: '   '),
    SeoInvalidArgumentException::class,
    'Field [userAgent] must not be empty.'
);

// testEmptyAllowPathThrowsException
assertException(
    'testEmptyAllowPathThrowsException',
    static fn() => new RobotsRuleDTO(userAgent: '*', allow: ['/valid', '  ']),
    SeoInvalidArgumentException::class,
    'Field [allow path] must not be empty.'
);

// testEmptyDisallowPathThrowsException
assertException(
    'testEmptyDisallowPathThrowsException',
    static fn() => new RobotsRuleDTO(userAgent: '*', disallow: ['   ']),
    SeoInvalidArgumentException::class,
    'Field [disallow path] must not be empty.'
);

// testInvalidCrawlDelayThrowsException
assertException(
    'testInvalidCrawlDelayThrowsException',
    static fn() => new RobotsRuleDTO(userAgent: '*', crawlDelay: -5),
    SeoInvalidArgumentException::class,
    'Field [crawlDelay] is invalid: must be greater than or equal to 0.'
);

// testInvalidSitemapThrowsException
assertException(
    'testInvalidSitemapThrowsException',
    static fn() => new RobotsTxtDTO(sitemaps: ['not-a-url']),
    SeoInvalidArgumentException::class,
    'URL [not-a-url] is invalid.'
);

// testOnlySitemap
$dto = new RobotsTxtDTO(
    sitemaps: ['https://example.com/sitemap.xml']
);

$expected = <<<TXT
Sitemap: https://example.com/sitemap.xml

TXT;

assertSameValue('testOnlySitemap', $expected, $renderer->render($dto));

// testOnlyComments
$dto = new RobotsTxtDTO(
    comments: ['Just a comment']
);

$expected = <<<TXT
# Just a comment

TXT;

assertSameValue('testOnlyComments', $expected, $renderer->render($dto));

echo "Phase 9A RobotsTxtRenderer tests passed.\n";
