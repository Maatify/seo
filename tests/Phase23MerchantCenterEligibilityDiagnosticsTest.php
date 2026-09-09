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

use Maatify\Seo\Web\MerchantCenter\DTO\MerchantCenterAggregateRequestDTO;
use Maatify\Seo\Web\MerchantCenter\DTO\MerchantCenterAggregateStatusListResultDTO;
use Maatify\Seo\Web\MerchantCenter\DTO\MerchantCenterProductRequestDTO;
use Maatify\Seo\Web\MerchantCenter\DTO\MerchantCenterProductStatusResultDTO;
use Maatify\Seo\Web\MerchantCenter\DTO\MerchantCenterTransportResponseDTO;
use Maatify\Seo\Web\MerchantCenter\Exception\MerchantCenterInvalidRequestException;
use Maatify\Seo\Web\MerchantCenter\Exception\MerchantCenterMalformedResponseException;
use Maatify\Seo\Web\MerchantCenter\Exception\MerchantCenterTransportException;
use Maatify\Seo\Web\MerchantCenter\Mapper\MerchantCenterResponseMapper;
use Maatify\Seo\Web\MerchantCenter\MerchantCenterDiagnosticsService;
use Maatify\Seo\Web\MerchantCenter\MerchantCenterTransportInterface;

function assertSameValue23(mixed $expected, mixed $actual, string $label): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertTrueValue23(bool $condition, string $label): void
{
    if (!$condition) {
        fwrite(STDERR, "Assertion failed: {$label}\n");
        exit(1);
    }
}

function assertInstanceOf23(string $expectedClass, mixed $actual, string $label): void
{
    assertTrueValue23($actual instanceof $expectedClass, $label);
}

/** @return array<string, mixed> */
function phase23ProductResponse(): array
{
    return [
        'name' => 'accounts/123/products/en~US~sku123',
        'productStatus' => [
            'destinationStatuses' => [
                [
                    'reportingContext' => 'SHOPPING_ADS',
                    'approvedCountries' => ['US'],
                    'pendingCountries' => [],
                    'disapprovedCountries' => [],
                ],
                [
                    'reportingContext' => 'LOCAL_INVENTORY',
                    'approvedCountries' => ['CA'],
                    'pendingCountries' => ['GB'],
                    'disapprovedCountries' => ['AU'],
                ],
            ],
            'itemLevelIssues' => [
                [
                    'code' => 'missing_value',
                    'severity' => 'ERROR',
                    'resolution' => 'MERCHANT_ACTION',
                    'attribute' => 'title',
                    'reportingContext' => 'SHOPPING_ADS',
                    'description' => 'A title is missing.',
                    'detail' => 'Add a title to the product.',
                    'documentation' => 'https://support.google.com/merchants/answer/0001',
                    'applicableCountries' => ['US', 'CA'],
                ],
                [
                    'code' => 'pending_review',
                    'severity' => 'WARNING',
                    'resolution' => 'PENDING_PROCESSING',
                    'attribute' => null,
                    'reportingContext' => 'LOCAL_INVENTORY',
                    'description' => null,
                    'detail' => null,
                    'documentation' => null,
                    'applicableCountries' => ['GB'],
                ],
            ],
            'creationDate' => '2026-09-01T10:00:00Z',
            'lastUpdateDate' => '2026-09-08T12:00:00Z',
            'googleExpirationDate' => '2026-10-08T12:00:00Z',
        ],
    ];
}

/** @return array<string, mixed> */
function phase23AggregateResponse(): array
{
    return [
        'aggregateProductStatuses' => [
            [
                'name' => 'accounts/123/aggregateProductStatuses/SHOPPING_ADS~US',
                'reportingContext' => 'SHOPPING_ADS',
                'country' => 'US',
                'stats' => [
                    'activeCount' => '9223372036854775807',
                    'pendingCount' => '12',
                    'disapprovedCount' => '3',
                    'expiringCount' => '1',
                ],
                'itemLevelIssues' => [
                    [
                        'code' => 'invalid_gtin',
                        'severity' => 'DISAPPROVED',
                        'resolution' => 'MERCHANT_ACTION',
                        'attribute' => 'gtin',
                        'description' => 'The GTIN is invalid.',
                        'detail' => 'Use a valid GTIN.',
                        'documentationUri' => 'https://support.google.com/merchants/answer/0002',
                        'productCount' => '18446744073709551615',
                    ],
                ],
            ],
            [
                'name' => 'accounts/123/aggregateProductStatuses/LOCAL_INVENTORY~GB',
                'reportingContext' => 'LOCAL_INVENTORY',
                'country' => 'GB',
                'stats' => null,
            ],
        ],
        'nextPageToken' => 'next-page-token',
    ];
}

/**
 * @param ?MerchantCenterTransportResponseDTO $response
 */
final class Phase23FakeTransport implements MerchantCenterTransportInterface
{
    public int $productCalls = 0;
    public int $aggregateCalls = 0;
    public ?MerchantCenterProductRequestDTO $receivedProductRequest = null;
    public ?MerchantCenterAggregateRequestDTO $receivedAggregateRequest = null;

    public function __construct(
        private ?MerchantCenterTransportResponseDTO $response,
        private ?\Throwable $failure = null,
    ) {
    }

    public function getProduct(MerchantCenterProductRequestDTO $request): MerchantCenterTransportResponseDTO
    {
        $this->productCalls++;
        $this->receivedProductRequest = $request;

        return $this->responseOrThrow();
    }

    public function listAggregateProductStatuses(
        MerchantCenterAggregateRequestDTO $request,
    ): MerchantCenterTransportResponseDTO {
        $this->aggregateCalls++;
        $this->receivedAggregateRequest = $request;

        return $this->responseOrThrow();
    }

    private function responseOrThrow(): MerchantCenterTransportResponseDTO
    {
        if ($this->failure !== null) {
            throw $this->failure;
        }
        if ($this->response === null) {
            throw new \LogicException('Test transport response is missing.');
        }

        return $this->response;
    }
}

/**
 * @param array<string, mixed> $body
 */
function phase23ProductService(
    array $body,
    int $httpStatus = 200,
    ?\Throwable $failure = null,
): array {
    $transport = new Phase23FakeTransport(new MerchantCenterTransportResponseDTO($httpStatus, $body), $failure);
    return [$transport, new MerchantCenterDiagnosticsService($transport, new MerchantCenterResponseMapper())];
}

/**
 * @param array<string, mixed> $body
 */
function phase23AggregateService(
    array $body,
    int $httpStatus = 200,
    ?\Throwable $failure = null,
): array {
    $transport = new Phase23FakeTransport(new MerchantCenterTransportResponseDTO($httpStatus, $body), $failure);
    return [$transport, new MerchantCenterDiagnosticsService($transport, new MerchantCenterResponseMapper())];
}

$productRequest = new MerchantCenterProductRequestDTO('accounts/123/products/en~US~sku123');
[$productTransport, $productService] = phase23ProductService(phase23ProductResponse());
$productResult = $productService->getProductDiagnostics($productRequest);
assertInstanceOf23(MerchantCenterProductStatusResultDTO::class, $productResult, 'product result is typed');
assertSameValue23('accounts/123/products/en~US~sku123', $productResult->productName, 'product name');
assertSameValue23(2, count($productResult->destinationStatuses), 'multiple reporting contexts');
assertSameValue23('SHOPPING_ADS', $productResult->destinationStatuses[0]->reportingContext, 'reporting context');
assertSameValue23(['US'], $productResult->destinationStatuses[0]->approvedCountries, 'fully approved country');
assertSameValue23(['GB'], $productResult->destinationStatuses[1]->pendingCountries, 'pending country');
assertSameValue23(['AU'], $productResult->destinationStatuses[1]->disapprovedCountries, 'disapproved country');
assertSameValue23(['CA'], $productResult->destinationStatuses[1]->approvedCountries, 'mixed approved country');
assertSameValue23(2, count($productResult->itemLevelIssues), 'product issue count');
assertSameValue23('ERROR', $productResult->itemLevelIssues[0]->severity, 'product issue severity');
assertSameValue23('WARNING', $productResult->itemLevelIssues[1]->severity, 'second product issue severity');
assertSameValue23('MERCHANT_ACTION', $productResult->itemLevelIssues[0]->resolution, 'product issue resolution');
assertSameValue23(['US', 'CA'], $productResult->itemLevelIssues[0]->applicableCountries, 'issue countries');
assertSameValue23('2026-09-01T10:00:00Z', $productResult->creationDate, 'creation date');
assertSameValue23('2026-09-08T12:00:00Z', $productResult->lastUpdateDate, 'last update date');
assertSameValue23('2026-10-08T12:00:00Z', $productResult->googleExpirationDate, 'expiration date');
assertSameValue23(1, $productTransport->productCalls, 'product transport called once');
assertSameValue23($productRequest, $productTransport->receivedProductRequest, 'product request forwarded');

/** @var array<string, mixed> $unknownProductResponse */
$unknownProductResponse = phase23ProductResponse();
$unknownProductResponse['productStatus']['destinationStatuses'][0]['reportingContext'] = 'FUTURE_CONTEXT';
$unknownProductResponse['productStatus']['itemLevelIssues'][0]['severity'] = 'FUTURE_SEVERITY';
$unknownProductResponse['productStatus']['itemLevelIssues'][0]['resolution'] = 'FUTURE_RESOLUTION';
[, $unknownProductService] = phase23ProductService($unknownProductResponse);
$unknownProductResult = $unknownProductService->getProductDiagnostics($productRequest);
assertSameValue23('FUTURE_CONTEXT', $unknownProductResult->destinationStatuses[0]->reportingContext, 'unknown product context preserved');
assertSameValue23('FUTURE_SEVERITY', $unknownProductResult->itemLevelIssues[0]->severity, 'unknown product severity preserved');
assertSameValue23('FUTURE_RESOLUTION', $unknownProductResult->itemLevelIssues[0]->resolution, 'unknown product resolution preserved');

/** @var array<string, mixed> $missingProductStatus */
$missingProductStatus = ['name' => 'accounts/123/products/en~US~missing-status'];
[$missingProductTransport, $missingProductService] = phase23ProductService($missingProductStatus);
$missingProductResult = $missingProductService->getProductDiagnostics(
    new MerchantCenterProductRequestDTO('accounts/123/products/en~US~missing-status'),
);
assertSameValue23([], $missingProductResult->destinationStatuses, 'missing product status has no destinations');
assertSameValue23([], $missingProductResult->itemLevelIssues, 'missing product status has no issues');
assertSameValue23(null, $missingProductResult->creationDate, 'missing product status creation date');
assertSameValue23(null, $missingProductResult->lastUpdateDate, 'missing product status update date');
assertSameValue23(null, $missingProductResult->googleExpirationDate, 'missing product status expiration date');
assertSameValue23(1, $missingProductTransport->productCalls, 'missing product status still calls once');

/** @var array<string, mixed> $optionalProductStatus */
$optionalProductStatus = [
    'name' => 'accounts/123/products/en~US~optional-fields',
    'productStatus' => [
        'destinationStatuses' => [],
    ],
];
[, $optionalProductService] = phase23ProductService($optionalProductStatus);
$optionalProductResult = $optionalProductService->getProductDiagnostics(
    new MerchantCenterProductRequestDTO('accounts/123/products/en~US~optional-fields'),
);
assertSameValue23([], $optionalProductResult->itemLevelIssues, 'missing optional issue array is empty');
assertSameValue23([], $optionalProductResult->destinationStatuses, 'empty destination array maps to empty list');

$malformedProductCaught = false;
try {
    /** @var array<string, mixed> $malformedProductResponse */
    $malformedProductResponse = phase23ProductResponse();
    $malformedProductResponse['productStatus']['destinationStatuses'] = 'not-a-list';
    [, $malformedProductService] = phase23ProductService($malformedProductResponse);
    $malformedProductService->getProductDiagnostics($productRequest);
} catch (MerchantCenterMalformedResponseException $exception) {
    $malformedProductCaught = str_contains($exception->getMessage(), 'productStatus.destinationStatuses');
}
assertTrueValue23($malformedProductCaught, 'malformed product response throws dedicated exception');

$nonTwoHundredProductCaught = false;
try {
    [$nonTwoHundredProductTransport, $nonTwoHundredProductService] = phase23ProductService([], 403);
    $nonTwoHundredProductService->getProductDiagnostics($productRequest);
} catch (MerchantCenterTransportException $exception) {
    $nonTwoHundredProductCaught = $exception->httpStatus === 403;
    assertSameValue23(1, $nonTwoHundredProductTransport->productCalls, 'non-2xx product call count');
}
assertTrueValue23($nonTwoHundredProductCaught, 'non-2xx product response throws transport exception');

[$invalidProductTransport, $invalidProductService] = phase23ProductService(phase23ProductResponse());
try {
    $invalidProductService->getProductDiagnostics(new MerchantCenterProductRequestDTO('   '));
    assertTrueValue23(false, 'blank product name throws');
} catch (MerchantCenterInvalidRequestException) {
    assertSameValue23(0, $invalidProductTransport->productCalls, 'invalid product request does not call transport');
}

$productTransportFailure = new MerchantCenterTransportException('transport unavailable');
[$failureProductTransport, $failureProductService] = phase23ProductService([], 200, $productTransportFailure);
$sameProductFailure = false;
try {
    $failureProductService->getProductDiagnostics($productRequest);
} catch (MerchantCenterTransportException $exception) {
    $sameProductFailure = $exception === $productTransportFailure;
}
assertTrueValue23($sameProductFailure, 'product transport failure is propagated without wrapping');
assertSameValue23(1, $failureProductTransport->productCalls, 'transport failure has no retry');

$serviceConstructor = (new ReflectionClass(MerchantCenterDiagnosticsService::class))->getConstructor();
$constructorTypes = [];
if ($serviceConstructor !== null) {
    foreach ($serviceConstructor->getParameters() as $parameter) {
        $type = $parameter->getType();
        $constructorTypes[] = $type instanceof ReflectionNamedType ? $type->getName() : null;
    }
}
assertSameValue23(
    [MerchantCenterTransportInterface::class, MerchantCenterResponseMapper::class],
    $constructorTypes,
    'service depends only on merchant transport and mapper',
);
assertTrueValue23(
    !($productResult instanceof \Maatify\Seo\Web\Validation\DTO\SeoValidationResultDTO),
    'merchant result is not a core validation DTO',
);

$aggregateRequest = new MerchantCenterAggregateRequestDTO(
    parent: 'accounts/123',
    pageSize: 100,
    pageToken: 'page-token-from-host',
    filter: 'reportingContext = "SHOPPING_ADS" AND country = "US"',
);
[$aggregateTransport, $aggregateService] = phase23AggregateService(phase23AggregateResponse());
$aggregateResult = $aggregateService->listAggregateProductDiagnostics($aggregateRequest);
assertInstanceOf23(MerchantCenterAggregateStatusListResultDTO::class, $aggregateResult, 'aggregate result is typed');
assertSameValue23(2, count($aggregateResult->statuses), 'aggregate status count');
assertSameValue23('SHOPPING_ADS', $aggregateResult->statuses[0]->reportingContext, 'aggregate reporting context');
assertSameValue23('US', $aggregateResult->statuses[0]->country, 'aggregate country');
assertSameValue23('9223372036854775807', $aggregateResult->statuses[0]->stats?->activeCount, 'active int64 remains string');
assertSameValue23('12', $aggregateResult->statuses[0]->stats?->pendingCount, 'pending count');
assertSameValue23('3', $aggregateResult->statuses[0]->stats?->disapprovedCount, 'disapproved count');
assertSameValue23('1', $aggregateResult->statuses[0]->stats?->expiringCount, 'expiring count');
assertTrueValue23(is_string($aggregateResult->statuses[0]->stats?->activeCount), 'active count is a PHP string');
assertSameValue23(1, count($aggregateResult->statuses[0]->itemLevelIssues), 'aggregate issue count');
assertSameValue23('invalid_gtin', $aggregateResult->statuses[0]->itemLevelIssues[0]->code, 'aggregate issue code');
assertSameValue23('DISAPPROVED', $aggregateResult->statuses[0]->itemLevelIssues[0]->severity, 'aggregate issue severity');
assertSameValue23('MERCHANT_ACTION', $aggregateResult->statuses[0]->itemLevelIssues[0]->resolution, 'aggregate issue resolution');
assertSameValue23('18446744073709551615', $aggregateResult->statuses[0]->itemLevelIssues[0]->productCount, 'aggregate product count remains string');
assertSameValue23('https://support.google.com/merchants/answer/0002', $aggregateResult->statuses[0]->itemLevelIssues[0]->documentationUri, 'aggregate documentation URI');
assertSameValue23(null, $aggregateResult->statuses[1]->stats, 'missing aggregate stats maps to null');
assertSameValue23('next-page-token', $aggregateResult->nextPageToken, 'next page token');
assertSameValue23(1, $aggregateTransport->aggregateCalls, 'aggregate transport called exactly once');
assertSameValue23($aggregateRequest, $aggregateTransport->receivedAggregateRequest, 'aggregate request forwarded unchanged');

/** @var array<string, mixed> $unknownAggregateResponse */
$unknownAggregateResponse = phase23AggregateResponse();
$unknownAggregateResponse['aggregateProductStatuses'][0]['itemLevelIssues'][0]['severity'] = 'FUTURE_AGGREGATE_SEVERITY';
$unknownAggregateResponse['aggregateProductStatuses'][0]['itemLevelIssues'][0]['resolution'] = 'FUTURE_AGGREGATE_RESOLUTION';
[, $unknownAggregateService] = phase23AggregateService($unknownAggregateResponse);
$unknownAggregateResult = $unknownAggregateService->listAggregateProductDiagnostics($aggregateRequest);
assertSameValue23(
    'FUTURE_AGGREGATE_SEVERITY',
    $unknownAggregateResult->statuses[0]->itemLevelIssues[0]->severity,
    'unknown aggregate severity preserved',
);
assertSameValue23(
    'FUTURE_AGGREGATE_RESOLUTION',
    $unknownAggregateResult->statuses[0]->itemLevelIssues[0]->resolution,
    'unknown aggregate resolution preserved',
);

[$emptyAggregateTransport, $emptyAggregateService] = phase23AggregateService([]);
$emptyAggregateResult = $emptyAggregateService->listAggregateProductDiagnostics(
    new MerchantCenterAggregateRequestDTO('accounts/123'),
);
assertSameValue23([], $emptyAggregateResult->statuses, 'missing aggregate list maps to empty list');
assertSameValue23(null, $emptyAggregateResult->nextPageToken, 'missing aggregate next page token');
assertSameValue23(1, $emptyAggregateTransport->aggregateCalls, 'empty aggregate response calls once');

$malformedAggregateCaught = false;
try {
    /** @var array<string, mixed> $malformedAggregateResponse */
    $malformedAggregateResponse = phase23AggregateResponse();
    $malformedAggregateResponse['aggregateProductStatuses'][0]['stats']['activeCount'] = 123;
    [, $malformedAggregateService] = phase23AggregateService($malformedAggregateResponse);
    $malformedAggregateService->listAggregateProductDiagnostics($aggregateRequest);
} catch (MerchantCenterMalformedResponseException $exception) {
    $malformedAggregateCaught = str_contains($exception->getMessage(), 'activeCount');
}
assertTrueValue23($malformedAggregateCaught, 'malformed aggregate response throws dedicated exception');

$nonTwoHundredAggregateCaught = false;
try {
    [$nonTwoHundredAggregateTransport, $nonTwoHundredAggregateService] = phase23AggregateService([], 500);
    $nonTwoHundredAggregateService->listAggregateProductDiagnostics(
        new MerchantCenterAggregateRequestDTO('accounts/123'),
    );
} catch (MerchantCenterTransportException $exception) {
    $nonTwoHundredAggregateCaught = $exception->httpStatus === 500;
    assertSameValue23(1, $nonTwoHundredAggregateTransport->aggregateCalls, 'non-2xx aggregate call count');
}
assertTrueValue23($nonTwoHundredAggregateCaught, 'non-2xx aggregate response throws transport exception');

foreach ([
    new MerchantCenterAggregateRequestDTO(''),
    new MerchantCenterAggregateRequestDTO('accounts/123', pageSize: 0),
    new MerchantCenterAggregateRequestDTO('accounts/123', pageSize: 251),
    new MerchantCenterAggregateRequestDTO('accounts/123', pageToken: ''),
    new MerchantCenterAggregateRequestDTO('accounts/123', filter: ''),
] as $invalidAggregateRequest) {
    [$invalidAggregateTransport, $invalidAggregateService] = phase23AggregateService(phase23AggregateResponse());
    try {
        $invalidAggregateService->listAggregateProductDiagnostics($invalidAggregateRequest);
        assertTrueValue23(false, 'invalid aggregate request throws');
    } catch (MerchantCenterInvalidRequestException) {
        assertSameValue23(0, $invalidAggregateTransport->aggregateCalls, 'invalid aggregate request does not call transport');
    }
}

$aggregateTransportFailure = new MerchantCenterTransportException('aggregate transport unavailable');
[$failureAggregateTransport, $failureAggregateService] = phase23AggregateService([], 200, $aggregateTransportFailure);
$sameAggregateFailure = false;
try {
    $failureAggregateService->listAggregateProductDiagnostics(
        new MerchantCenterAggregateRequestDTO('accounts/123'),
    );
} catch (MerchantCenterTransportException $exception) {
    $sameAggregateFailure = $exception === $aggregateTransportFailure;
}
assertTrueValue23($sameAggregateFailure, 'aggregate transport failure is propagated without wrapping');
assertSameValue23(1, $failureAggregateTransport->aggregateCalls, 'aggregate transport failure has no retry');

echo "Phase 23 Merchant Center eligibility diagnostics tests passed.\n";
