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

use Maatify\Seo\Exception\SeoNotFoundException;
use Maatify\Seo\Shared\Command\SeoOverride\CreateSeoOverrideCommand;
use Maatify\Seo\Shared\Command\SeoOverride\UpdateSeoOverrideCommand;
use Maatify\Seo\Shared\Command\GenerateMetaTagsCommand;
use Maatify\Seo\Shared\Contract\HostUrlGeneratorInterface;
use Maatify\Seo\Shared\Contract\SeoOverrideRepositoryInterface;
use Maatify\Seo\Shared\DTO\MetaTagsDTO;
use Maatify\Seo\Shared\DTO\SeoOverride\SeoOverrideDTO;
use Maatify\Seo\Shared\Service\MetaGeneratorService;
use Maatify\Seo\Shared\Service\SeoOverrideQueryService;

$phase24CompletedCases = 0;

function phase24Fail(string $label, string $details = ''): never
{
    fwrite(STDERR, "Contract mismatch: {$label}\n{$details}\n");
    exit(1);
}

function phase24AssertSame(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        phase24Fail(
            $label,
            "Expected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true),
        );
    }
}

function phase24CaptureException(string $label, callable $callback): \Throwable
{
    try {
        $callback();
    } catch (\Throwable $exception) {
        return $exception;
    }

    phase24Fail($label, 'Expected an exception, but none was thrown.');
}

function phase24RunCase(string $label, callable $callback): void
{
    global $phase24CompletedCases;

    try {
        $callback();
    } catch (\Throwable $exception) {
        phase24Fail($label, get_class($exception) . ': ' . $exception->getMessage());
    }

    ++$phase24CompletedCases;
}

function phase24Command(
    string $defaultTitle = 'Default title',
    ?string $defaultDescription = 'Default description',
    string $robots = 'index,follow',
    ?string $canonicalUrl = null,
): GenerateMetaTagsCommand {
    return new GenerateMetaTagsCommand(
        entityType: 'article',
        entityId: '42',
        languageId: 1,
        defaultTitle: $defaultTitle,
        defaultDescription: $defaultDescription,
        slug: 'article-42',
        canonicalUrl: $canonicalUrl,
        robots: $robots,
    );
}

function phase24Override(?string $metaTitle, ?string $metaDescription): SeoOverrideDTO
{
    return new SeoOverrideDTO(
        id: 1,
        entityType: 'article',
        entityId: '42',
        languageId: 1,
        metaTitle: $metaTitle,
        metaDescription: $metaDescription,
        createdAt: '2026-01-01T00:00:00+00:00',
        updatedAt: '2026-01-01T00:00:00+00:00',
        deletedAt: null,
    );
}

function phase24Service(
    Phase24FakeSeoOverrideRepository $repository,
    ?HostUrlGeneratorInterface $urlGenerator = null,
): MetaGeneratorService {
    return new MetaGeneratorService(new SeoOverrideQueryService($repository), $urlGenerator);
}

final class Phase24FakeSeoOverrideRepository implements SeoOverrideRepositoryInterface
{
    public function __construct(
        private ?SeoOverrideDTO $activeOverride = null,
        private ?\Throwable $activeLookupException = null,
    ) {
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
        if ($this->activeLookupException !== null) {
            throw $this->activeLookupException;
        }

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

final class Phase24FakeHostUrlGenerator implements HostUrlGeneratorInterface
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
        return 'https://example.test/';
    }
}

phase24RunCase('01 default title is trimmed', static function (): void {
    $meta = phase24Service(new Phase24FakeSeoOverrideRepository())->generate(
        phase24Command(defaultTitle: " \tDefault title\n "),
    );

    phase24AssertSame('default title trim', 'Default title', $meta->title);
});

phase24RunCase('02 non-blank default description is trimmed', static function (): void {
    $meta = phase24Service(new Phase24FakeSeoOverrideRepository())->generate(
        phase24Command(defaultDescription: " \tDefault description\n "),
    );

    phase24AssertSame('default description trim', 'Default description', $meta->description);
});

phase24RunCase('03 blank default description becomes null', static function (): void {
    $meta = phase24Service(new Phase24FakeSeoOverrideRepository())->generate(
        phase24Command(defaultDescription: " \t\n "),
    );

    phase24AssertSame('blank default description', null, $meta->description);
});

phase24RunCase('04 robots receives trim only', static function (): void {
    $meta = phase24Service(new Phase24FakeSeoOverrideRepository())->generate(
        phase24Command(robots: '  noindex , follow  '),
    );

    phase24AssertSame('robots trim without additional normalization', 'noindex , follow', $meta->robots);
});

phase24RunCase('05 missing active override converts to not-found and falls back to defaults', static function (): void {
    $repository = new Phase24FakeSeoOverrideRepository(activeOverride: null);
    $queryService = new SeoOverrideQueryService($repository);
    $notFound = phase24CaptureException(
        'real query service missing-override conversion',
        static fn () => $queryService->getActiveForEntity('article', '42', 1),
    );
    phase24AssertSame('missing override exception class', SeoNotFoundException::class, get_class($notFound));

    $meta = (new MetaGeneratorService($queryService))->generate(phase24Command());
    phase24AssertSame('missing override default title', 'Default title', $meta->title);
    phase24AssertSame('missing override default description', 'Default description', $meta->description);
});

phase24RunCase('06 title-only override replaces title and retains default description', static function (): void {
    $meta = phase24Service(new Phase24FakeSeoOverrideRepository(phase24Override(' Override title ', null)))
        ->generate(phase24Command());

    phase24AssertSame('title-only override title', 'Override title', $meta->title);
    phase24AssertSame('title-only override description', 'Default description', $meta->description);
});

phase24RunCase('07 description-only override replaces description and retains default title', static function (): void {
    $meta = phase24Service(new Phase24FakeSeoOverrideRepository(phase24Override(null, ' Override description ')))
        ->generate(phase24Command());

    phase24AssertSame('description-only override title', 'Default title', $meta->title);
    phase24AssertSame('description-only override description', 'Override description', $meta->description);
});

phase24RunCase('08 blank override fields do not erase defaults', static function (): void {
    $meta = phase24Service(new Phase24FakeSeoOverrideRepository(phase24Override(" \t\n ", '   ')))
        ->generate(phase24Command());

    phase24AssertSame('blank title override retains default', 'Default title', $meta->title);
    phase24AssertSame('blank description override retains default', 'Default description', $meta->description);
});

phase24RunCase('09 explicit SeoNotFoundException during lookup means no override', static function (): void {
    $repository = new Phase24FakeSeoOverrideRepository(
        activeLookupException: SeoNotFoundException::withCode('article:42:1'),
    );
    $meta = phase24Service($repository)->generate(phase24Command());

    phase24AssertSame('explicit not-found default title', 'Default title', $meta->title);
    phase24AssertSame('explicit not-found default description', 'Default description', $meta->description);
});

phase24RunCase('10 non-not-found lookup exception propagates unchanged', static function (): void {
    $expectedException = new \RuntimeException('override storage unavailable');
    $service = phase24Service(new Phase24FakeSeoOverrideRepository(activeLookupException: $expectedException));
    $actualException = phase24CaptureException(
        'non-not-found lookup exception propagation',
        static fn () => $service->generate(phase24Command()),
    );

    phase24AssertSame('propagated lookup exception identity', $expectedException, $actualException);
});

phase24RunCase('11 non-blank explicit canonical is trimmed', static function (): void {
    $meta = phase24Service(new Phase24FakeSeoOverrideRepository())->generate(
        phase24Command(canonicalUrl: " \thttps://example.test/explicit\n "),
    );

    phase24AssertSame('explicit canonical trim', 'https://example.test/explicit', $meta->canonicalUrl);
});

phase24RunCase('12 explicit canonical takes precedence over host URL', static function (): void {
    $host = new Phase24FakeHostUrlGenerator('https://host.test/generated');
    $meta = phase24Service(new Phase24FakeSeoOverrideRepository(), $host)
        ->generate(phase24Command(canonicalUrl: 'https://explicit.test/canonical'));

    phase24AssertSame('explicit canonical precedence', 'https://explicit.test/canonical', $meta->canonicalUrl);
});

phase24RunCase('13 host URL generator is not called for explicit canonical', static function (): void {
    $host = new Phase24FakeHostUrlGenerator('https://host.test/generated');
    phase24Service(new Phase24FakeSeoOverrideRepository(), $host)
        ->generate(phase24Command(canonicalUrl: 'https://explicit.test/canonical'));

    phase24AssertSame('explicit canonical host call count', 0, count($host->calls));
});

phase24RunCase('14 null explicit canonical uses host URL fallback', static function (): void {
    $host = new Phase24FakeHostUrlGenerator('https://host.test/generated');
    $meta = phase24Service(new Phase24FakeSeoOverrideRepository(), $host)
        ->generate(phase24Command(canonicalUrl: null));

    phase24AssertSame('null canonical host fallback value', 'https://host.test/generated', $meta->canonicalUrl);
});

phase24RunCase('15 blank explicit canonical uses host URL fallback', static function (): void {
    $host = new Phase24FakeHostUrlGenerator('https://host.test/generated');
    $meta = phase24Service(new Phase24FakeSeoOverrideRepository(), $host)
        ->generate(phase24Command(canonicalUrl: " \t\n "));

    phase24AssertSame('blank canonical host fallback value', 'https://host.test/generated', $meta->canonicalUrl);
});

phase24RunCase('16 no explicit canonical or host generator produces null', static function (): void {
    $meta = phase24Service(new Phase24FakeSeoOverrideRepository())->generate(phase24Command());

    phase24AssertSame('canonical without host generator', null, $meta->canonicalUrl);
});

phase24RunCase('17 host-generated URL is returned unchanged with surrounding spaces', static function (): void {
    $hostUrl = " \thttps://host.test/generated\n ";
    $host = new Phase24FakeHostUrlGenerator($hostUrl);
    $meta = phase24Service(new Phase24FakeSeoOverrideRepository(), $host)->generate(phase24Command());

    phase24AssertSame('host URL remains unchanged', $hostUrl, $meta->canonicalUrl);
});

phase24RunCase('18 final title propagates to base, Open Graph, and Twitter title fields', static function (): void {
    $meta = phase24Service(new Phase24FakeSeoOverrideRepository(phase24Override('Final title', null)))
        ->generate(phase24Command());

    phase24AssertSame('final title', 'Final title', $meta->title);
    phase24AssertSame('Open Graph title copy', 'Final title', $meta->openGraphTitle);
    phase24AssertSame('Twitter title copy', 'Final title', $meta->twitterTitle);
});

phase24RunCase('19 final description propagates to base, Open Graph, and Twitter description fields', static function (): void {
    $meta = phase24Service(new Phase24FakeSeoOverrideRepository(phase24Override(null, 'Final description')))
        ->generate(phase24Command());

    phase24AssertSame('final description', 'Final description', $meta->description);
    phase24AssertSame('Open Graph description copy', 'Final description', $meta->openGraphDescription);
    phase24AssertSame('Twitter description copy', 'Final description', $meta->twitterDescription);
});

phase24RunCase('20 final canonical propagates to base canonical and Open Graph URL', static function (): void {
    $meta = phase24Service(new Phase24FakeSeoOverrideRepository())->generate(
        phase24Command(canonicalUrl: 'https://example.test/final-canonical'),
    );

    phase24AssertSame('final canonical', 'https://example.test/final-canonical', $meta->canonicalUrl);
    phase24AssertSame('Open Graph URL copy', 'https://example.test/final-canonical', $meta->openGraphUrl);
});

phase24RunCase('21 untouched social fields remain null', static function (): void {
    $meta = phase24Service(new Phase24FakeSeoOverrideRepository())->generate(phase24Command());

    phase24AssertSame('openGraphType remains null', null, $meta->openGraphType);
    phase24AssertSame('openGraphImage remains null', null, $meta->openGraphImage);
    phase24AssertSame('twitterCard remains null', null, $meta->twitterCard);
    phase24AssertSame('twitterImage remains null', null, $meta->twitterImage);
});

phase24RunCase('22 MetaTagsDTO constructor order and serialized keys remain public shape', static function (): void {
    $constructor = (new ReflectionClass(MetaTagsDTO::class))->getConstructor();
    if ($constructor === null) {
        phase24Fail('MetaTagsDTO constructor exists');
    }

    phase24AssertSame('MetaTagsDTO constructor parameter order', [
        'title',
        'description',
        'canonicalUrl',
        'robots',
        'openGraphTitle',
        'openGraphDescription',
        'openGraphUrl',
        'twitterTitle',
        'twitterDescription',
        'openGraphType',
        'openGraphImage',
        'twitterCard',
        'twitterImage',
    ], array_map(static fn (ReflectionParameter $parameter): string => $parameter->getName(), $constructor->getParameters()));

    $meta = new MetaTagsDTO('Shape title', null, null);
    phase24AssertSame('MetaTagsDTO serialized keys', [
        'title',
        'description',
        'canonical_url',
        'robots',
        'open_graph_title',
        'open_graph_description',
        'open_graph_url',
        'twitter_title',
        'twitter_description',
        'open_graph_type',
        'open_graph_image',
        'twitter_card',
        'twitter_image',
    ], array_keys($meta->jsonSerialize()));
});

phase24AssertSame('exact contract case count', 22, $phase24CompletedCases);
fwrite(STDOUT, "Phase 24 MetaGeneratorService contract tests passed (22 cases).\n");
