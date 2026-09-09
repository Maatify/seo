<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Indexing\SearchConsole\Mapper;

use Maatify\Seo\Web\Indexing\SearchConsole\DTO\SearchConsoleDetectedItemDTO;
use Maatify\Seo\Web\Indexing\SearchConsole\DTO\SearchConsoleIndexStatusResultDTO;
use Maatify\Seo\Web\Indexing\SearchConsole\DTO\SearchConsoleInspectionResultDTO;
use Maatify\Seo\Web\Indexing\SearchConsole\DTO\SearchConsoleRichResultIssueDTO;
use Maatify\Seo\Web\Indexing\SearchConsole\DTO\SearchConsoleRichResultItemDTO;
use Maatify\Seo\Web\Indexing\SearchConsole\DTO\SearchConsoleRichResultsResultDTO;
use Maatify\Seo\Web\Indexing\SearchConsole\Exception\SearchConsoleMalformedResponseException;

final class SearchConsoleResponseMapper
{
    /**
     * @param array<string, mixed> $decodedProviderResponse
     */
    public function map(array $decodedProviderResponse): SearchConsoleInspectionResultDTO
    {
        $inspectionResult = $this->requiredObject($decodedProviderResponse, 'inspectionResult', 'inspectionResult');
        $indexStatusResult = $this->requiredObject(
            $inspectionResult,
            'indexStatusResult',
            'inspectionResult.indexStatusResult',
        );

        return new SearchConsoleInspectionResultDTO(
            providerIdentity: 'Google Search Console',
            inspectionResultLink: $this->optionalString(
                $inspectionResult,
                'inspectionResultLink',
                'inspectionResult.inspectionResultLink',
            ),
            indexStatusResult: new SearchConsoleIndexStatusResultDTO(
                verdict: $this->optionalString($indexStatusResult, 'verdict', 'indexStatusResult.verdict'),
                coverageState: $this->optionalString($indexStatusResult, 'coverageState', 'indexStatusResult.coverageState'),
                robotsTxtState: $this->optionalString($indexStatusResult, 'robotsTxtState', 'indexStatusResult.robotsTxtState'),
                indexingState: $this->optionalString($indexStatusResult, 'indexingState', 'indexStatusResult.indexingState'),
                lastCrawlTime: $this->optionalString($indexStatusResult, 'lastCrawlTime', 'indexStatusResult.lastCrawlTime'),
                pageFetchState: $this->optionalString($indexStatusResult, 'pageFetchState', 'indexStatusResult.pageFetchState'),
                googleCanonical: $this->optionalString($indexStatusResult, 'googleCanonical', 'indexStatusResult.googleCanonical'),
                userCanonical: $this->optionalString($indexStatusResult, 'userCanonical', 'indexStatusResult.userCanonical'),
                crawledAs: $this->optionalString($indexStatusResult, 'crawledAs', 'indexStatusResult.crawledAs'),
            ),
            richResultsResult: $this->mapRichResults($inspectionResult),
        );
    }

    /**
     * @param array<string, mixed> $inspectionResult
     */
    private function mapRichResults(array $inspectionResult): ?SearchConsoleRichResultsResultDTO
    {
        if (!array_key_exists('richResultsResult', $inspectionResult)) {
            return null;
        }

        $rawRichResults = $inspectionResult['richResultsResult'];
        if ($rawRichResults === null) {
            return null;
        }
        if (!is_array($rawRichResults) || array_is_list($rawRichResults)) {
            throw SearchConsoleMalformedResponseException::forPath(
                'inspectionResult.richResultsResult',
                'an object or null is required',
            );
        }

        /** @var array<string, mixed> $richResults */
        $richResults = $rawRichResults;
        $detectedItems = [];
        foreach ($this->optionalList($richResults, 'detectedItems', 'richResultsResult.detectedItems') as $index => $rawDetectedItem) {
            if (!is_array($rawDetectedItem) || array_is_list($rawDetectedItem)) {
                throw SearchConsoleMalformedResponseException::forPath(
                    'richResultsResult.detectedItems.' . $index,
                    'an object is required',
                );
            }

            /** @var array<string, mixed> $detectedItem */
            $detectedItem = $rawDetectedItem;
            $items = [];
            foreach ($this->requiredList($detectedItem, 'items', 'richResultsResult.detectedItems.' . $index . '.items') as $itemIndex => $rawItem) {
                if (!is_array($rawItem) || array_is_list($rawItem)) {
                    throw SearchConsoleMalformedResponseException::forPath(
                        'richResultsResult.detectedItems.' . $index . '.items.' . $itemIndex,
                        'an object is required',
                    );
                }

                /** @var array<string, mixed> $item */
                $item = $rawItem;
                $issues = [];
                foreach ($this->optionalList($item, 'issues', 'richResultsResult.detectedItems.' . $index . '.items.' . $itemIndex . '.issues') as $issueIndex => $rawIssue) {
                    if (!is_array($rawIssue) || array_is_list($rawIssue)) {
                        throw SearchConsoleMalformedResponseException::forPath(
                            'richResultsResult.detectedItems.' . $index . '.items.' . $itemIndex . '.issues.' . $issueIndex,
                            'an object is required',
                        );
                    }

                    /** @var array<string, mixed> $issue */
                    $issue = $rawIssue;
                    $issues[] = new SearchConsoleRichResultIssueDTO(
                        issueMessage: $this->requiredString(
                            $issue,
                            'issueMessage',
                            'richResultsResult.detectedItems.' . $index . '.items.' . $itemIndex . '.issues.' . $issueIndex . '.issueMessage',
                        ),
                        severity: $this->requiredString(
                            $issue,
                            'severity',
                            'richResultsResult.detectedItems.' . $index . '.items.' . $itemIndex . '.issues.' . $issueIndex . '.severity',
                        ),
                    );
                }

                $items[] = new SearchConsoleRichResultItemDTO(
                    name: $this->optionalString(
                        $item,
                        'name',
                        'richResultsResult.detectedItems.' . $index . '.items.' . $itemIndex . '.name',
                    ),
                    issues: $issues,
                );
            }

            $detectedItems[] = new SearchConsoleDetectedItemDTO(
                richResultType: $this->requiredString(
                    $detectedItem,
                    'richResultType',
                    'richResultsResult.detectedItems.' . $index . '.richResultType',
                ),
                items: $items,
            );
        }

        return new SearchConsoleRichResultsResultDTO(
            verdict: $this->requiredString($richResults, 'verdict', 'richResultsResult.verdict'),
            detectedItems: $detectedItems,
        );
    }

    /**
     * @param array<string, mixed> $parent
     * @return array<string, mixed>
     */
    private function requiredObject(array $parent, string $field, string $path): array
    {
        if (!array_key_exists($field, $parent) || !is_array($parent[$field]) || array_is_list($parent[$field])) {
            throw SearchConsoleMalformedResponseException::forPath($path, 'an object is required');
        }

        /** @var array<string, mixed> $value */
        $value = $parent[$field];
        return $value;
    }

    /**
     * @param array<string, mixed> $parent
     * @return list<mixed>
     */
    private function requiredList(array $parent, string $field, string $path): array
    {
        if (!array_key_exists($field, $parent) || !is_array($parent[$field]) || !array_is_list($parent[$field])) {
            throw SearchConsoleMalformedResponseException::forPath($path, 'a list is required');
        }

        /** @var list<mixed> $value */
        $value = $parent[$field];
        return $value;
    }

    /**
     * @param array<string, mixed> $parent
     * @return list<mixed>
     */
    private function optionalList(array $parent, string $field, string $path): array
    {
        if (!array_key_exists($field, $parent)) {
            return [];
        }

        if (!is_array($parent[$field]) || !array_is_list($parent[$field])) {
            throw SearchConsoleMalformedResponseException::forPath($path, 'a list is required');
        }

        /** @var list<mixed> $value */
        $value = $parent[$field];
        return $value;
    }

    /**
     * @param array<string, mixed> $parent
     */
    private function requiredString(array $parent, string $field, string $path): string
    {
        if (!array_key_exists($field, $parent) || !is_string($parent[$field])) {
            throw SearchConsoleMalformedResponseException::forPath($path, 'a string is required');
        }

        return $parent[$field];
    }

    /**
     * @param array<string, mixed> $parent
     */
    private function optionalString(array $parent, string $field, string $path): ?string
    {
        if (!array_key_exists($field, $parent) || $parent[$field] === null) {
            return null;
        }
        if (!is_string($parent[$field])) {
            throw SearchConsoleMalformedResponseException::forPath($path, 'a string or null is required');
        }

        return $parent[$field];
    }
}
