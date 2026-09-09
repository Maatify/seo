<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\MerchantCenter;

use Maatify\Seo\Web\MerchantCenter\DTO\MerchantCenterAggregateRequestDTO;
use Maatify\Seo\Web\MerchantCenter\DTO\MerchantCenterAggregateStatusListResultDTO;
use Maatify\Seo\Web\MerchantCenter\DTO\MerchantCenterProductRequestDTO;
use Maatify\Seo\Web\MerchantCenter\DTO\MerchantCenterProductStatusResultDTO;
use Maatify\Seo\Web\MerchantCenter\Exception\MerchantCenterInvalidRequestException;
use Maatify\Seo\Web\MerchantCenter\Exception\MerchantCenterTransportException;
use Maatify\Seo\Web\MerchantCenter\Mapper\MerchantCenterResponseMapper;

final readonly class MerchantCenterDiagnosticsService
{
    public function __construct(
        private MerchantCenterTransportInterface $transport,
        private MerchantCenterResponseMapper $responseMapper,
    ) {
    }

    public function getProductDiagnostics(
        MerchantCenterProductRequestDTO $request,
    ): MerchantCenterProductStatusResultDTO {
        $this->validateProductRequest($request);

        $response = $this->transport->getProduct($request);
        $this->assertSuccessfulResponse($response->httpStatus);

        return $this->responseMapper->mapProductResponse($response->decodedBody);
    }

    public function listAggregateProductDiagnostics(
        MerchantCenterAggregateRequestDTO $request,
    ): MerchantCenterAggregateStatusListResultDTO {
        $this->validateAggregateRequest($request);

        $response = $this->transport->listAggregateProductStatuses($request);
        $this->assertSuccessfulResponse($response->httpStatus);

        return $this->responseMapper->mapAggregateStatusListResponse($response->decodedBody);
    }

    private function validateProductRequest(MerchantCenterProductRequestDTO $request): void
    {
        if (trim($request->name) === '') {
            throw MerchantCenterInvalidRequestException::forField(
                'name',
                'a product resource name is required',
            );
        }
    }

    private function validateAggregateRequest(MerchantCenterAggregateRequestDTO $request): void
    {
        if (trim($request->parent) === '') {
            throw MerchantCenterInvalidRequestException::forField(
                'parent',
                'an account resource name is required',
            );
        }

        if ($request->pageSize !== null && $request->pageSize < 1) {
            throw MerchantCenterInvalidRequestException::forField(
                'pageSize',
                'must be positive when provided',
            );
        }

        if ($request->pageToken !== null && trim($request->pageToken) === '') {
            throw MerchantCenterInvalidRequestException::forField(
                'pageToken',
                'must be non-empty when provided',
            );
        }

        if ($request->filter !== null && trim($request->filter) === '') {
            throw MerchantCenterInvalidRequestException::forField(
                'filter',
                'must be non-empty when provided',
            );
        }
    }

    private function assertSuccessfulResponse(int $httpStatus): void
    {
        if ($httpStatus < 200 || $httpStatus >= 300) {
            throw MerchantCenterTransportException::forHttpStatus($httpStatus);
        }
    }
}
