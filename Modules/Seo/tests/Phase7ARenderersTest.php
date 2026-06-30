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
use Maatify\Seo\Shared\DTO\Schema\JsonLdSchemaDTO;
use Maatify\Seo\Web\Render\JsonLdScriptRenderer;
use Maatify\Seo\Web\Render\MetaTagsHtmlRenderer;
use Maatify\Seo\Web\Render\OpenGraphHtmlRenderer;
use Maatify\Seo\Web\Render\SeoHeadHtmlRenderer;
use Maatify\Seo\Web\Render\TwitterCardHtmlRenderer;

function assertSameValue(string $label, string $expected, string $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n{$expected}\nActual:\n{$actual}\n");
        exit(1);
    }
}

$metaTags = new MetaTagsDTO(
    title: 'A <Title> & "Quote"',
    description: 'Desc <b>bold</b> & "quoted"',
    canonicalUrl: 'https://example.com/page?a=1&b=<x>',
    robots: 'noindex,nofollow',
    openGraphTitle: 'OG <Title>',
    openGraphDescription: 'OG desc & details',
    openGraphUrl: 'https://example.com/og?x=1&y=2',
    twitterTitle: 'Twitter <Title>',
    twitterDescription: 'Twitter desc & details',
    openGraphType: 'article',
    openGraphImage: 'https://example.com/image.jpg?name=<hero>&size=large',
    twitterCard: 'summary_large_image',
    twitterImage: 'https://example.com/twitter.jpg?name=<hero>&size=large',
);

assertSameValue(
    'title, description, canonical, robots render with escaping',
    '<title>A &lt;Title&gt; &amp; &quot;Quote&quot;</title>' . "\n"
    . '<meta name="description" content="Desc &lt;b&gt;bold&lt;/b&gt; &amp; &quot;quoted&quot;">' . "\n"
    . '<link rel="canonical" href="https://example.com/page?a=1&amp;b=&lt;x&gt;">' . "\n"
    . '<meta name="robots" content="noindex,nofollow">',
    (new MetaTagsHtmlRenderer())->render($metaTags),
);

assertSameValue(
    'MetaTagsDTO JSON includes Phase 7A OpenGraph and Twitter optional fields',
    '{"title":"A <Title> & \"Quote\"","description":"Desc <b>bold<\/b> & \"quoted\"","canonical_url":"https:\/\/example.com\/page?a=1&b=<x>","robots":"noindex,nofollow","open_graph_title":"OG <Title>","open_graph_description":"OG desc & details","open_graph_url":"https:\/\/example.com\/og?x=1&y=2","twitter_title":"Twitter <Title>","twitter_description":"Twitter desc & details","open_graph_type":"article","open_graph_image":"https:\/\/example.com\/image.jpg?name=<hero>&size=large","twitter_card":"summary_large_image","twitter_image":"https:\/\/example.com\/twitter.jpg?name=<hero>&size=large"}',
    json_encode($metaTags, JSON_THROW_ON_ERROR),
);

assertSameValue(
    'OpenGraph renders deterministic supported fields',
    '<meta property="og:title" content="OG &lt;Title&gt;">' . "\n"
    . '<meta property="og:description" content="OG desc &amp; details">' . "\n"
    . '<meta property="og:type" content="article">' . "\n"
    . '<meta property="og:url" content="https://example.com/og?x=1&amp;y=2">' . "\n"
    . '<meta property="og:image" content="https://example.com/image.jpg?name=&lt;hero&gt;&amp;size=large">',
    (new OpenGraphHtmlRenderer())->render($metaTags),
);

assertSameValue(
    'Twitter renders deterministic supported fields',
    '<meta name="twitter:card" content="summary_large_image">' . "\n"
    . '<meta name="twitter:title" content="Twitter &lt;Title&gt;">' . "\n"
    . '<meta name="twitter:description" content="Twitter desc &amp; details">' . "\n"
    . '<meta name="twitter:image" content="https://example.com/twitter.jpg?name=&lt;hero&gt;&amp;size=large">',
    (new TwitterCardHtmlRenderer())->render($metaTags),
);

assertSameValue(
    'JSON-LD renders safe script payload',
    '<script type="application/ld+json">{"@context":"https://schema.org","@type":"WebPage","name":"\u003CUnsafe\u003E \u0026 \u0022Quoted\u0022"}</script>',
    (new JsonLdScriptRenderer())->render(new JsonLdSchemaDTO([
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'name' => '<Unsafe> & "Quoted"',
    ])),
);

assertSameValue(
    'JSON-LD renders multiple payloads',
    '<script type="application/ld+json">{"@type":"WebPage"}</script>' . "\n"
    . '<script type="application/ld+json">{"@type":"Organization"}</script>',
    (new JsonLdScriptRenderer())->render([
        new JsonLdSchemaDTO(['@type' => 'WebPage']),
        ['@type' => 'Organization'],
    ]),
);

assertSameValue(
    'full composed head renders sections in deterministic order',
    '<title>A &lt;Title&gt; &amp; &quot;Quote&quot;</title>' . "\n"
    . '<meta name="description" content="Desc &lt;b&gt;bold&lt;/b&gt; &amp; &quot;quoted&quot;">' . "\n"
    . '<link rel="canonical" href="https://example.com/page?a=1&amp;b=&lt;x&gt;">' . "\n"
    . '<meta name="robots" content="noindex,nofollow">' . "\n"
    . '<meta property="og:title" content="OG &lt;Title&gt;">' . "\n"
    . '<meta property="og:description" content="OG desc &amp; details">' . "\n"
    . '<meta property="og:type" content="article">' . "\n"
    . '<meta property="og:url" content="https://example.com/og?x=1&amp;y=2">' . "\n"
    . '<meta property="og:image" content="https://example.com/image.jpg?name=&lt;hero&gt;&amp;size=large">' . "\n"
    . '<meta name="twitter:card" content="summary_large_image">' . "\n"
    . '<meta name="twitter:title" content="Twitter &lt;Title&gt;">' . "\n"
    . '<meta name="twitter:description" content="Twitter desc &amp; details">' . "\n"
    . '<meta name="twitter:image" content="https://example.com/twitter.jpg?name=&lt;hero&gt;&amp;size=large">' . "\n"
    . '<script type="application/ld+json">{"@type":"WebPage"}</script>',
    (new SeoHeadHtmlRenderer())->render($metaTags, [new JsonLdSchemaDTO(['@type' => 'WebPage'])]),
);

$minimalMetaTags = new MetaTagsDTO(
    title: 'Only title',
    description: null,
    canonicalUrl: null,
    robots: 'index,follow',
    openGraphTitle: '',
    openGraphDescription: null,
    openGraphUrl: null,
    twitterTitle: null,
    twitterDescription: '',
    openGraphType: null,
    openGraphImage: '',
    twitterCard: null,
    twitterImage: '',
);

assertSameValue(
    'null and empty optional fields are omitted',
    '<title>Only title</title>' . "\n" . '<meta name="robots" content="index,follow">',
    (new SeoHeadHtmlRenderer())->render($minimalMetaTags),
);

echo "Phase 7A renderer tests passed.\n";
