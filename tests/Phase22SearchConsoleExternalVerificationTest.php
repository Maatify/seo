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

use Maatify\Seo\Web\Indexing\SearchConsole\DTO\SearchConsoleInspectionRequestDTO;
use Maatify\Seo\Web\Indexing\SearchConsole\DTO\SearchConsoleTransportResponseDTO;
use Maatify\Seo\Web\Indexing\SearchConsole\Exception\SearchConsoleInvalidRequestException;
use Maatify\Seo\Web\Indexing\SearchConsole\Exception\SearchConsoleMalformedResponseException;
use Maatify\Seo\Web\Indexing\SearchConsole\Exception\SearchConsoleTransportException;
use Maatify\Seo\Web\Indexing\SearchConsole\Mapper\SearchConsoleResponseMapper;
use Maatify\Seo\Web\Indexing\SearchConsole\SearchConsoleInspectionService;
use Maatify\Seo\Web\Indexing\SearchConsole\SearchConsoleTransportInterface;

function assertSameValue22(string $label, mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\nExpected:\n" . var_export($expected, true) . "\nActual:\n" . var_export($actual, true) . "\n");
        exit(1);
    }
}

function assertTrueValue22(string $label, bool $actual): void
{
    if (!$actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertFalseValue22(string $label, bool $actual): void
{
    if ($actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

/** @return array<string, mixed> */
function phase22BaseResponse(): array
{
    return [
        'inspectionResult' => [
            'inspectionResultLink' => 'https://search.google.com/search-console/inspect?resource_id=example',
            'indexStatusResult' => [
                'verdict' => 'PASS',
                'coverageState' => 'Submitted and indexed',
                'robotsTxtState' => 'ALLOWED',
                'indexingState' => 'INDEXING_ALLOWED',
                'lastCrawlTime' => '2026-09-08T12:00:00Z',
                'pageFetchState' => 'SUCCESSFUL',
                'googleCanonical' => 'https://example.com/articles/search-console',
                'userCanonical' => 'https://example.com/articles/search-console',
                'crawledAs' => 'MOBILE',
            ],
            'richResultsResult' => [
                'verdict' => 'PASS',
                'detectedItems' => [
                    [
                        'richResultType' => 'Article',
                        'items' => [
                            [
                                'name' => 'Search Console guide',
                                'issues' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];
}

/** @param array<string, mixed> $body */
function phase22Service(array $body, int $httpStatus = 200): Phase22FakeTransport
{
    return new Phase22FakeTransport(new SearchConsoleTransportResponseDTO($httpStatus, $body));
}

final class Phase22FakeTransport implements SearchConsoleTransportInterface
{
    public int $calls = 0;
    public ?SearchConsoleInspectionRequestDTO $receivedRequest = null;

    /**
     * @param ?SearchConsoleTransportResponseDTO $response
     */
    public function __construct(
        private ?SearchConsoleTransportResponseDTO $response,
        private ?\Throwable $failure = null,
    ) {
    }

    public function inspect(SearchConsoleInspectionRequestDTO $request): SearchConsoleTransportResponseDTO
    {
        $this->calls++;
        $this->receivedRequest = $request;

        if ($this->failure !== null) {
            throw $this->failure;
        }
        if ($this->response === null) {
            throw new \LogicException('Test transport response is missing.');
        }

        return $this->response;
    }
}

$request = new SearchConsoleInspectionRequestDTO(
    inspectionUrl: 'https://example.com/articles/search-console',
    siteUrl: 'sc-domain:example.com',
    languageCode: 'en-US',
);

$transport = phase22Service(phase22BaseResponse());
$service = new SearchConsoleInspectionService($transport, new SearchConsoleResponseMapper());
$result = $service->inspect($request);
assertSameValue22('provider identity', 'Google Search Console', $result->providerIdentity);
assertSameValue22('indexed verdict', 'PASS', $result->indexStatusResult->verdict);
assertSameValue22('rich results verdict', 'PASS', $result->richResultsResult?->verdict);
assertSameValue22('transport called once', 1, $transport->calls);
assertSameValue22('request forwarded', $request, $transport->receivedRequest);

/** @var array<string, mixed> $errorBody */
$errorBody = phase22BaseResponse();
$errorBody['inspectionResult']['richResultsResult']['verdict'] = 'ERROR';
$errorBody['inspectionResult']['richResultsResult']['detectedItems'][0]['items'][0]['issues'] = [
    ['issueMessage' => 'Missing author', 'severity' => 'ERROR'],
];
$errorResult = (new SearchConsoleInspectionService(phase22Service($errorBody), new SearchConsoleResponseMapper()))->inspect($request);
assertSameValue22('rich-result error verdict', 'ERROR', $errorResult->richResultsResult?->verdict);
assertSameValue22('rich-result error severity', 'ERROR', $errorResult->richResultsResult?->detectedItems[0]->items[0]->issues[0]->severity);

/** @var array<string, mixed> $warningBody */
$warningBody = phase22BaseResponse();
$warningBody['inspectionResult']['richResultsResult']['verdict'] = 'WARNING';
$warningBody['inspectionResult']['richResultsResult']['detectedItems'][0]['items'][0]['issues'] = [
    ['issueMessage' => 'Image could be larger', 'severity' => 'WARNING'],
];
$warningResult = (new SearchConsoleInspectionService(phase22Service($warningBody), new SearchConsoleResponseMapper()))->inspect($request);
assertSameValue22('rich-result warning verdict', 'WARNING', $warningResult->richResultsResult?->verdict);
assertSameValue22('rich-result warning severity', 'WARNING', $warningResult->richResultsResult?->detectedItems[0]->items[0]->issues[0]->severity);

/** @var array<string, mixed> $multipleBody */
$multipleBody = phase22BaseResponse();
$multipleBody['inspectionResult']['richResultsResult']['detectedItems'][] = [
    'richResultType' => 'Breadcrumbs',
    'items' => [
        ['name' => 'Home path', 'issues' => []],
        ['name' => null, 'issues' => []],
    ],
];
$multipleResult = (new SearchConsoleInspectionService(phase22Service($multipleBody), new SearchConsoleResponseMapper()))->inspect($request);
assertSameValue22('multiple rich-result types', 2, count($multipleResult->richResultsResult?->detectedItems ?? []));
assertSameValue22('multiple items', 2, count($multipleResult->richResultsResult?->detectedItems[1]->items ?? []));

/** @var array<string, mixed> $withoutRichResults */
$withoutRichResults = phase22BaseResponse();
unset($withoutRichResults['inspectionResult']['richResultsResult']);
$withoutRichResult = (new SearchConsoleInspectionService(phase22Service($withoutRichResults), new SearchConsoleResponseMapper()))->inspect($request);
assertSameValue22('absent rich-results section remains null', null, $withoutRichResult->richResultsResult);

assertSameValue22('coverage state', 'Submitted and indexed', $result->indexStatusResult->coverageState);
assertSameValue22('robots state', 'ALLOWED', $result->indexStatusResult->robotsTxtState);
assertSameValue22('indexing state', 'INDEXING_ALLOWED', $result->indexStatusResult->indexingState);
assertSameValue22('last crawl time', '2026-09-08T12:00:00Z', $result->indexStatusResult->lastCrawlTime);
assertSameValue22('page fetch state', 'SUCCESSFUL', $result->indexStatusResult->pageFetchState);
assertSameValue22('google canonical', 'https://example.com/articles/search-console', $result->indexStatusResult->googleCanonical);
assertSameValue22('user canonical', 'https://example.com/articles/search-console', $result->indexStatusResult->userCanonical);
assertSameValue22('crawled as', 'MOBILE', $result->indexStatusResult->crawledAs);

/** @var array<string, mixed> $optionalBody */
$optionalBody = phase22BaseResponse();
unset(
    $optionalBody['inspectionResult']['inspectionResultLink'],
    $optionalBody['inspectionResult']['indexStatusResult']['coverageState'],
    $optionalBody['inspectionResult']['indexStatusResult']['lastCrawlTime'],
    $optionalBody['inspectionResult']['indexStatusResult']['googleCanonical'],
);
$optionalResult = (new SearchConsoleInspectionService(phase22Service($optionalBody), new SearchConsoleResponseMapper()))->inspect($request);
assertSameValue22('optional inspection link', null, $optionalResult->inspectionResultLink);
assertSameValue22('optional coverage state', null, $optionalResult->indexStatusResult->coverageState);
assertSameValue22('optional crawl time', null, $optionalResult->indexStatusResult->lastCrawlTime);
assertSameValue22('optional google canonical', null, $optionalResult->indexStatusResult->googleCanonical);

/** @var array<string, mixed> $unknownBody */
$unknownBody = phase22BaseResponse();
$unknownBody['inspectionResult']['indexStatusResult']['verdict'] = 'FUTURE_VERDICT';
$unknownBody['inspectionResult']['indexStatusResult']['robotsTxtState'] = 'FUTURE_ROBOTS_STATE';
$unknownBody['inspectionResult']['richResultsResult']['verdict'] = 'FUTURE_RICH_VERDICT';
$unknownBody['inspectionResult']['richResultsResult']['detectedItems'][0]['items'][0]['issues'] = [
    ['issueMessage' => 'Provider-defined issue', 'severity' => 'FUTURE_SEVERITY'],
];
$unknownResult = (new SearchConsoleInspectionService(phase22Service($unknownBody), new SearchConsoleResponseMapper()))->inspect($request);
assertSameValue22('unknown index verdict preserved', 'FUTURE_VERDICT', $unknownResult->indexStatusResult->verdict);
assertSameValue22('unknown state preserved', 'FUTURE_ROBOTS_STATE', $unknownResult->indexStatusResult->robotsTxtState);
assertSameValue22('unknown rich verdict preserved', 'FUTURE_RICH_VERDICT', $unknownResult->richResultsResult?->verdict);
assertSameValue22('unknown severity preserved', 'FUTURE_SEVERITY', $unknownResult->richResultsResult?->detectedItems[0]->items[0]->issues[0]->severity);

$malformedCaught = false;
try {
    (new SearchConsoleInspectionService(
        phase22Service(['inspectionResult' => ['indexStatusResult' => 'not-an-object']]),
        new SearchConsoleResponseMapper(),
    ))->inspect($request);
} catch (SearchConsoleMalformedResponseException $exception) {
    $malformedCaught = str_contains($exception->getMessage(), 'indexStatusResult');
}
assertTrueValue22('malformed provider response throws dedicated exception', $malformedCaught);

$nonTwoHundredCaught = false;
try {
    (new SearchConsoleInspectionService(
        phase22Service(['ignored' => true], 403),
        new SearchConsoleResponseMapper(),
    ))->inspect($request);
} catch (SearchConsoleTransportException $exception) {
    $nonTwoHundredCaught = $exception->httpStatus === 403;
}
assertTrueValue22('non-2xx response throws provider exception', $nonTwoHundredCaught);

$invalidInspectionTransport = phase22Service(phase22BaseResponse());
try {
    (new SearchConsoleInspectionService($invalidInspectionTransport, new SearchConsoleResponseMapper()))->inspect(
        new SearchConsoleInspectionRequestDTO('not-a-url', 'https://example.com/'),
    );
    assertTrueValue22('invalid inspection URL throws', false);
} catch (SearchConsoleInvalidRequestException) {
    assertSameValue22('invalid inspection URL does not call transport', 0, $invalidInspectionTransport->calls);
}

foreach (['example.com', 'sc-domain:'] as $invalidSiteUrl) {
    try {
        (new SearchConsoleInspectionService(phase22Service(phase22BaseResponse()), new SearchConsoleResponseMapper()))->inspect(
            new SearchConsoleInspectionRequestDTO('https://example.com/', $invalidSiteUrl),
        );
        assertTrueValue22('invalid site property throws', false);
    } catch (SearchConsoleInvalidRequestException) {
        // Expected for invalid URL-prefix and domain property formats.
    }
}

$transportFailure = new SearchConsoleTransportException('transport unavailable');
$failureTransport = new Phase22FakeTransport(null, $transportFailure);
$failureService = new SearchConsoleInspectionService($failureTransport, new SearchConsoleResponseMapper());
$sameFailure = false;
try {
    $failureService->inspect($request);
} catch (SearchConsoleTransportException $exception) {
    $sameFailure = $exception === $transportFailure;
}
assertTrueValue22('transport failure is propagated without hidden retry or wrapping', $sameFailure);
assertSameValue22('transport failure called once', 1, $failureTransport->calls);

$serviceConstructor = (new ReflectionClass(SearchConsoleInspectionService::class))->getConstructor();
$constructorTypes = [];
if ($serviceConstructor !== null) {
    foreach ($serviceConstructor->getParameters() as $parameter) {
        $type = $parameter->getType();
        $constructorTypes[] = $type instanceof ReflectionNamedType ? $type->getName() : null;
    }
}
assertSameValue22(
    'inspection service depends only on external transport and mapper',
    [SearchConsoleTransportInterface::class, SearchConsoleResponseMapper::class],
    $constructorTypes,
);
assertFalseValue22(
    'external result is not the core validation DTO',
    $result instanceof \Maatify\Seo\Web\Validation\DTO\SeoValidationResultDTO,
);

echo "Phase 22 Search Console external verification tests passed.\n";
