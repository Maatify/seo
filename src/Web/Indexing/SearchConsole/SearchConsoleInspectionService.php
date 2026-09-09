<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Indexing\SearchConsole;

use Maatify\Seo\Web\Indexing\SearchConsole\DTO\SearchConsoleInspectionRequestDTO;
use Maatify\Seo\Web\Indexing\SearchConsole\DTO\SearchConsoleInspectionResultDTO;
use Maatify\Seo\Web\Indexing\SearchConsole\Exception\SearchConsoleInvalidRequestException;
use Maatify\Seo\Web\Indexing\SearchConsole\Exception\SearchConsoleTransportException;
use Maatify\Seo\Web\Indexing\SearchConsole\Mapper\SearchConsoleResponseMapper;

final readonly class SearchConsoleInspectionService
{
    public function __construct(
        private SearchConsoleTransportInterface $transport,
        private SearchConsoleResponseMapper $responseMapper,
    ) {
    }

    public function inspect(SearchConsoleInspectionRequestDTO $request): SearchConsoleInspectionResultDTO
    {
        $this->validateRequest($request);

        $response = $this->transport->inspect($request);
        if ($response->httpStatus < 200 || $response->httpStatus >= 300) {
            throw SearchConsoleTransportException::forHttpStatus($response->httpStatus);
        }

        return $this->responseMapper->map($response->decodedBody);
    }

    private function validateRequest(SearchConsoleInspectionRequestDTO $request): void
    {
        $inspectionUrl = trim($request->inspectionUrl);
        if ($inspectionUrl === '' || !$this->isAbsoluteHttpUrl($inspectionUrl)) {
            throw SearchConsoleInvalidRequestException::forField(
                'inspectionUrl',
                'an absolute HTTP or HTTPS URL is required',
            );
        }

        $siteUrl = trim($request->siteUrl);
        if ($siteUrl === '') {
            throw SearchConsoleInvalidRequestException::forField('siteUrl', 'a Search Console property is required');
        }

        if (!$this->isValidSiteProperty($siteUrl)) {
            throw SearchConsoleInvalidRequestException::forField(
                'siteUrl',
                'a URL-prefix property or sc-domain property is required',
            );
        }

        if ($request->languageCode !== null && trim($request->languageCode) === '') {
            throw SearchConsoleInvalidRequestException::forField(
                'languageCode',
                'must be non-empty when provided',
            );
        }
    }

    private function isValidSiteProperty(string $siteUrl): bool
    {
        if (str_starts_with(strtolower($siteUrl), 'sc-domain:')) {
            $domain = substr($siteUrl, strlen('sc-domain:'));
            return $this->isValidHostname($domain);
        }

        return str_ends_with($siteUrl, '/') && $this->isAbsoluteHttpUrl($siteUrl);
    }

    private function isValidHostname(string $hostname): bool
    {
        if ($hostname === '' || strlen($hostname) > 253) {
            return false;
        }

        $labels = explode('.', $hostname);
        foreach ($labels as $label) {
            if ($label === '' || strlen($label) > 63) {
                return false;
            }

            if (preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?$/', $label) !== 1) {
                return false;
            }
        }

        return filter_var('https://' . $hostname, FILTER_VALIDATE_URL) !== false;
    }

    private function isAbsoluteHttpUrl(string $url): bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url($url);
        if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        return in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true);
    }
}
