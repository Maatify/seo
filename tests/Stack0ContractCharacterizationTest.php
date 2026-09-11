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

use Maatify\Seo\Shared\Command\GenerateMetaTagsCommand;
use Maatify\Seo\Shared\Command\SeoOverride\CreateSeoOverrideCommand;
use Maatify\Seo\Shared\Command\SeoOverride\UpdateSeoOverrideCommand;
use Maatify\Seo\Shared\Contract\HostUrlGeneratorInterface;
use Maatify\Seo\Shared\Contract\SeoOverrideRepositoryInterface;
use Maatify\Seo\Shared\DTO\MetaTagsDTO;
use Maatify\Seo\Shared\DTO\SeoOverride\SeoOverrideDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapAlternateUrlDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapImageDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapIndexEntryDTO as SharedSitemapIndexEntryDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapNewsDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapUrlDTO;
use Maatify\Seo\Shared\DTO\Sitemap\SitemapVideoDTO;
use Maatify\Seo\Shared\Service\MetaGeneratorService;
use Maatify\Seo\Shared\Service\SeoOverrideQueryService;
use Maatify\Seo\Shared\Service\SitemapGeneratorService;
use Maatify\Seo\Exception\SeoInvalidArgumentException;
use Maatify\Seo\Web\Robots\DTO\RobotsRuleDTO;
use Maatify\Seo\Web\Robots\DTO\RobotsTxtDTO;
use Maatify\Seo\Web\Sitemap\DTO\SitemapIndexEntryDTO as WebSitemapIndexEntryDTO;
use Maatify\Seo\Web\Sitemap\SitemapIndexXmlStringRenderer;
use Maatify\Seo\Web\Sitemap\SitemapXmlStringRenderer;
use Maatify\Seo\Web\Validation\DTO\SeoValidationIssueDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationResultDTO;
use Maatify\Seo\Web\Validation\SeoMetaValidator;
use Maatify\Seo\Web\Validation\SeoValidationScoreCalculator;

function stack0AssertTrue(string $label, bool $actual): void
{
    if (!$actual) {
        throw new RuntimeException("Assertion failed: {$label}");
    }
}

function stack0AssertSame(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        throw new RuntimeException("Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true));
    }
}

function stack0AssertThrows(string $label, callable $callback, ?string $expectedMessage = null, ?string $expectedClass = null): void
{
    try {
        $callback();
    } catch (Throwable $exception) {
        if ($expectedClass !== null) {
            stack0AssertSame($label . ' exception class', $expectedClass, get_class($exception));
        }

        if ($expectedMessage !== null) {
            stack0AssertSame($label . ' exception message', $expectedMessage, $exception->getMessage());
        }

        return;
    }

    throw new RuntimeException("Assertion failed: {$label}\nExpected an exception.");
}

final class Stack0FakeSeoOverrideRepository implements SeoOverrideRepositoryInterface
{
    public function __construct(private ?SeoOverrideDTO $activeOverride)
    {
    }

    public function create(CreateSeoOverrideCommand $command): int
    {
        return 1;
    }

    public function update(UpdateSeoOverrideCommand $command): bool
    {
        return true;
    }

    public function findById(int $id): ?SeoOverrideDTO
    {
        return null;
    }

    public function findActiveForEntity(string $entityType, string $entityId, int $languageId): ?SeoOverrideDTO
    {
        return $this->activeOverride;
    }

    /** @return list<SeoOverrideDTO> */
    public function findByEntity(string $entityType, string $entityId, ?int $languageId = null, bool $includeDeleted = false): array
    {
        return $this->activeOverride === null ? [] : [$this->activeOverride];
    }

    public function softDelete(int $id): bool
    {
        return true;
    }

    public function hardDelete(int $id): bool
    {
        return true;
    }
}

final class Stack0FakeHostUrlGenerator implements HostUrlGeneratorInterface
{
    /** @var list<array{entityType: string, entityId: string, languageId: int, slug: ?string}> */
    public array $calls = [];

    public function __construct(private string $generatedUrl)
    {
    }

    public function generateEntityUrl(string $entityType, string $entityId, int $languageId, ?string $slug): string
    {
        $this->calls[] = [
            'entityType' => $entityType,
            'entityId' => $entityId,
            'languageId' => $languageId,
            'slug' => $slug,
        ];

        return $this->generatedUrl;
    }

    public function generateHomeUrl(int $languageId): string
    {
        return 'https://example.com/';
    }
}

$xmlHeader = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$extendedUrl = new SitemapUrlDTO(
    loc: 'https://example.com/extended',
    lastmod: '2026-07-01T10:00:00+00:00',
    changefreq: 'daily',
    priority: 0.7,
    alternates: [new SitemapAlternateUrlDTO('en', 'https://example.com/extended')],
    images: [new SitemapImageDTO('https://cdn.example.com/extended.jpg', 'Extended image')],
    videos: [new SitemapVideoDTO('https://cdn.example.com/extended-thumb.jpg', 'Extended video', 'Extended video description', 'https://cdn.example.com/extended.mp4')],
    news: [
        new SitemapNewsDTO('Example Daily', 'en', '2026-07-01', 'First story'),
        new SitemapNewsDTO('Example Tribune', 'en', '2026-07-02', 'Second story'),
    ],
);

$renderer = new SitemapXmlStringRenderer();
$rendererExtendedXml = $renderer->renderUrlSet([$extendedUrl]);
$expectedRendererExtendedXml = $xmlHeader
    . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"><url><loc>https://example.com/extended</loc><lastmod>2026-07-01T10:00:00+00:00</lastmod><changefreq>daily</changefreq><priority>0.7</priority><xhtml:link rel="alternate" hreflang="en" href="https://example.com/extended"/><image:image><image:loc>https://cdn.example.com/extended.jpg</image:loc><image:title>Extended image</image:title></image:image><video:video><video:thumbnail_loc>https://cdn.example.com/extended-thumb.jpg</video:thumbnail_loc><video:title>Extended video</video:title><video:description>Extended video description</video:description><video:content_loc>https://cdn.example.com/extended.mp4</video:content_loc></video:video><news:news><news:publication><news:name>Example Daily</news:name><news:language>en</news:language></news:publication><news:publication_date>2026-07-01</news:publication_date><news:title>First story</news:title></news:news><news:news><news:publication><news:name>Example Tribune</news:name><news:language>en</news:language></news:publication><news:publication_date>2026-07-02</news:publication_date><news:title>Second story</news:title></news:news></url></urlset>' . "\n";
stack0AssertSame('renderer exact extended sitemap XML', $expectedRendererExtendedXml, $rendererExtendedXml);

$generatorResult = (new SitemapGeneratorService())->generateUrlSitemap([$extendedUrl]);
$expectedGeneratorXml = $xmlHeader
    . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml"><url><loc>https://example.com/extended</loc><lastmod>2026-07-01T10:00:00+00:00</lastmod><changefreq>daily</changefreq><priority>0.7</priority><xhtml:link rel="alternate" hreflang="en" href="https://example.com/extended"/></url></urlset>' . "\n";
stack0AssertSame('generator exact extended sitemap XML currently drops image video news', $expectedGeneratorXml, $generatorResult->xml);
stack0AssertSame('sitemap generation result serialized shape', ['xml' => $expectedGeneratorXml, 'entry_count' => 1, 'type' => 'urlset'], $generatorResult->jsonSerialize());

$sharedIndexEntry = new SharedSitemapIndexEntryDTO('https://example.com/shared.xml', '2026-07-01');
$webIndexEntry = new WebSitemapIndexEntryDTO('https://example.com/web.xml', '2026-07-01');
stack0AssertSame('shared index DTO serialized shape', ['loc' => 'https://example.com/shared.xml', 'lastmod' => '2026-07-01'], $sharedIndexEntry->jsonSerialize());
stack0AssertSame('web index DTO serialized shape', ['loc' => 'https://example.com/web.xml', 'lastmod' => '2026-07-01'], $webIndexEntry->jsonSerialize());
stack0AssertSame(
    'web index renderer exact XML remains stable',
    $xmlHeader . '<sitemap><loc>https://example.com/web.xml</loc><lastmod>2026-07-01</lastmod></sitemap>' . "\n",
    (new SitemapIndexXmlStringRenderer())->renderEntry($webIndexEntry),
);
stack0AssertThrows('shared index DTO retains empty-field invalid URL exception', static fn() => new SharedSitemapIndexEntryDTO('not-a-url'), 'Field [loc] must not be empty.', SeoInvalidArgumentException::class);
stack0AssertThrows('web index DTO retains invalid-URL exception', static fn() => new WebSitemapIndexEntryDTO('not-a-url'), 'URL [not-a-url] is invalid.', SeoInvalidArgumentException::class);

$generator = new SitemapGeneratorService();
stack0AssertThrows(
    'typed-only URL sitemap generator rejects raw-array URL entries',
    static fn() => $generator->generateUrlSitemap([['loc' => 'https://example.com/raw-url-entry']]),
    'Field [urls] must not be empty.',
    SeoInvalidArgumentException::class,
);
stack0AssertThrows(
    'typed-only sitemap index generator rejects raw-array index entries',
    static fn() => $generator->generateSitemapIndex([['loc' => 'https://example.com/raw-index-entry.xml']]),
    'Field [entries] must not be empty.',
    SeoInvalidArgumentException::class,
);

$fractionalLastmod = '2026-07-01T10:00:00.123+00:00';
stack0AssertTrue('fractional-second lastmod is currently rejected by shared helper', !SitemapUrlDTO::isValidLastmod($fractionalLastmod));
stack0AssertThrows('URL DTO rejects fractional-second lastmod', static fn() => new SitemapUrlDTO('https://example.com/fractional', $fractionalLastmod));
stack0AssertThrows('shared index DTO rejects fractional-second lastmod', static fn() => new SharedSitemapIndexEntryDTO('https://example.com/fractional.xml', $fractionalLastmod));
stack0AssertThrows('web index DTO rejects fractional-second lastmod', static fn() => new WebSitemapIndexEntryDTO('https://example.com/fractional.xml', $fractionalLastmod));
stack0AssertThrows('raw index renderer rejects fractional-second lastmod', static fn() => (new SitemapIndexXmlStringRenderer())->renderEntry(['loc' => 'https://example.com/fractional.xml', 'lastmod' => $fractionalLastmod]));

$leadingWildcardRule = new RobotsRuleDTO('*', allow: ['*'], disallow: ['*/private']);
stack0AssertSame('robots rule preserves leading-wildcard allow path', ['*'], $leadingWildcardRule->allow);
stack0AssertSame('robots rule preserves leading-wildcard disallow path', ['*/private'], $leadingWildcardRule->disallow);
stack0AssertThrows('robots rule still rejects empty allow pattern', static fn() => new RobotsRuleDTO('*', allow: ['']));
stack0AssertThrows('robots rule still rejects empty disallow pattern', static fn() => new RobotsRuleDTO('*', disallow: [' ']));
new RobotsTxtDTO(sitemaps: ['https://example.com/sitemap.xml']);
stack0AssertThrows('robots Sitemap still uses ASCII-only FILTER_VALIDATE_URL for Unicode host', static fn() => new RobotsTxtDTO(sitemaps: ['https://مثال.com/sitemap.xml']));
stack0AssertThrows('robots Sitemap still uses ASCII-only FILTER_VALIDATE_URL for Unicode path', static fn() => new RobotsTxtDTO(sitemaps: ['https://example.com/العربية/sitemap.xml']));

$ogResult = SeoMetaValidator::validate([
    'title' => 'A valid title for Stack Zero',
    'description' => str_repeat('D', 60),
    'openGraph' => [],
]);
stack0AssertSame('current OGP legacy warning codes', ['missing_og_title', 'missing_og_description', 'missing_og_image'], array_map(static fn (SeoValidationIssueDTO $issue): string => $issue->code, $ogResult->issues));
stack0AssertSame('current OGP warnings all retain warning severity', ['warning', 'warning', 'warning'], array_map(static fn (SeoValidationIssueDTO $issue): string => $issue->severity, $ogResult->issues));
$ogScore = SeoValidationScoreCalculator::score($ogResult);
stack0AssertSame('three current OGP warnings retain default five-point deductions', 85, $ogScore->score);
stack0AssertSame('each current OGP warning participates in legacy score', [5, 5, 5], array_column($ogScore->deductions, 'points'));

$byteBoundaryOptions = [
    'titleMinLength' => 0,
    'titleMaxLength' => 4,
    'descriptionMinLength' => 0,
    'descriptionMaxLength' => 4,
];
$asciiBoundary = SeoMetaValidator::validate(['title' => 'abcd', 'description' => 'abcd'], $byteBoundaryOptions);
stack0AssertSame('ASCII four-byte title and description remain at max boundary', [], $asciiBoundary->issues);
$unicodeBeyondByteBoundary = SeoMetaValidator::validate(['title' => 'عرب', 'description' => 'عرب'], $byteBoundaryOptions);
stack0AssertSame('Unicode title and description use strlen byte length', ['title_too_long', 'description_too_long'], array_map(static fn (SeoValidationIssueDTO $issue): string => $issue->code, $unicodeBeyondByteBoundary->issues));
stack0AssertSame('title and description length issues retain warning severity', ['warning', 'warning'], array_map(static fn (SeoValidationIssueDTO $issue): string => $issue->severity, $unicodeBeyondByteBoundary->issues));
stack0AssertSame('Unicode byte-length warnings retain default score deduction', 90, SeoValidationScoreCalculator::score($unicodeBeyondByteBoundary)->score);

$issue = new SeoValidationIssueDTO('stack0_warning', SeoValidationIssueDTO::SEVERITY_WARNING, 'Stack 0 warning.', 'title');
stack0AssertSame('validation issue serialized shape', ['code' => 'stack0_warning', 'severity' => 'warning', 'message' => 'Stack 0 warning.', 'field' => 'title'], $issue->toArray());
$resultShape = (new SeoValidationResultDTO([
    new SeoValidationIssueDTO('stack0_error', SeoValidationIssueDTO::SEVERITY_ERROR, 'Stack 0 error.', 'canonical'),
    $issue,
    new SeoValidationIssueDTO('stack0_info', SeoValidationIssueDTO::SEVERITY_INFO, 'Stack 0 info.'),
]))->toArray();
stack0AssertSame('validation result serialized shape', [
    'is_valid' => false,
    'has_warnings' => true,
    'errors' => [['code' => 'stack0_error', 'severity' => 'error', 'message' => 'Stack 0 error.', 'field' => 'canonical']],
    'warnings' => [['code' => 'stack0_warning', 'severity' => 'warning', 'message' => 'Stack 0 warning.', 'field' => 'title']],
    'info' => [['code' => 'stack0_info', 'severity' => 'info', 'message' => 'Stack 0 info.', 'field' => null]],
    'issues' => [
        ['code' => 'stack0_error', 'severity' => 'error', 'message' => 'Stack 0 error.', 'field' => 'canonical'],
        ['code' => 'stack0_warning', 'severity' => 'warning', 'message' => 'Stack 0 warning.', 'field' => 'title'],
        ['code' => 'stack0_info', 'severity' => 'info', 'message' => 'Stack 0 info.', 'field' => null],
    ],
], $resultShape);

$validateReflection = new ReflectionMethod(SeoMetaValidator::class, 'validate');
stack0AssertSame('validator public parameter order', ['meta', 'options'], array_map(static fn (ReflectionParameter $parameter): string => $parameter->getName(), $validateReflection->getParameters()));
$metaType = $validateReflection->getParameters()[0]->getType();
$metaTypeNames = $metaType instanceof ReflectionUnionType ? array_map(static fn (ReflectionNamedType $type): string => $type->getName(), $metaType->getTypes()) : [];
sort($metaTypeNames);
stack0AssertTrue('validator first parameter is array|object union', $metaType instanceof ReflectionUnionType && ['array', 'object'] === $metaTypeNames);
stack0AssertTrue('validator options parameter defaults to empty array', $validateReflection->getParameters()[1]->isDefaultValueAvailable() && $validateReflection->getParameters()[1]->getDefaultValue() === []);
stack0AssertSame('validator return type remains legacy result DTO', SeoValidationResultDTO::class, $validateReflection->getReturnType()?->getName());
$objectMetaResult = SeoMetaValidator::validate(new MetaTagsDTO('Object branch title', str_repeat('D', 60), 'https://example.com/canonical'));
stack0AssertTrue('MetaTagsDTO object branch remains callable', $objectMetaResult instanceof SeoValidationResultDTO);

$hostUrlGenerator = new Stack0FakeHostUrlGenerator('https://example.com/generated/article');
$defaultMeta = (new MetaGeneratorService(new SeoOverrideQueryService(new Stack0FakeSeoOverrideRepository(null)), $hostUrlGenerator))->generate(new GenerateMetaTagsCommand(
    entityType: 'article',
    entityId: '42',
    languageId: 1,
    defaultTitle: ' Default article title ',
    defaultDescription: ' Default article description ',
    slug: 'article-42',
    robots: ' index,follow ',
));
stack0AssertSame('MetaGeneratorService trims and returns host-generated canonical defaults', [
    'title' => 'Default article title',
    'description' => 'Default article description',
    'canonicalUrl' => 'https://example.com/generated/article',
    'robots' => 'index,follow',
    'openGraphTitle' => 'Default article title',
    'openGraphDescription' => 'Default article description',
    'openGraphUrl' => 'https://example.com/generated/article',
    'twitterTitle' => 'Default article title',
    'twitterDescription' => 'Default article description',
], [
    'title' => $defaultMeta->title,
    'description' => $defaultMeta->description,
    'canonicalUrl' => $defaultMeta->canonicalUrl,
    'robots' => $defaultMeta->robots,
    'openGraphTitle' => $defaultMeta->openGraphTitle,
    'openGraphDescription' => $defaultMeta->openGraphDescription,
    'openGraphUrl' => $defaultMeta->openGraphUrl,
    'twitterTitle' => $defaultMeta->twitterTitle,
    'twitterDescription' => $defaultMeta->twitterDescription,
]);
stack0AssertSame('MetaGeneratorService passes entity context to host URL generator', [['entityType' => 'article', 'entityId' => '42', 'languageId' => 1, 'slug' => 'article-42']], $hostUrlGenerator->calls);

$override = new SeoOverrideDTO(1, 'article', '42', 1, ' Override article title ', null, '2026-07-01T10:00:00+00:00', '2026-07-01T10:00:00+00:00', null);
$explicitCanonicalMeta = (new MetaGeneratorService(new SeoOverrideQueryService(new Stack0FakeSeoOverrideRepository($override)), $hostUrlGenerator))->generate(new GenerateMetaTagsCommand(
    entityType: 'article',
    entityId: '42',
    languageId: 1,
    defaultTitle: 'Default article title',
    defaultDescription: 'Default article description',
    canonicalUrl: ' https://example.com/explicit ',
));
stack0AssertSame('MetaGeneratorService applies non-empty override and explicit canonical precedence', 'Override article title', $explicitCanonicalMeta->title);
stack0AssertSame('MetaGeneratorService keeps default description when override description is null', 'Default article description', $explicitCanonicalMeta->description);
stack0AssertSame('MetaGeneratorService trims explicit canonical URL', 'https://example.com/explicit', $explicitCanonicalMeta->canonicalUrl);

echo "Stack 0 contract characterization tests passed.\n";
