<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\MerchantCenter\Mapper;

use Maatify\Seo\Web\MerchantCenter\DTO\MerchantCenterAggregateIssueDTO;
use Maatify\Seo\Web\MerchantCenter\DTO\MerchantCenterAggregateStatisticsDTO;
use Maatify\Seo\Web\MerchantCenter\DTO\MerchantCenterAggregateStatusListResultDTO;
use Maatify\Seo\Web\MerchantCenter\DTO\MerchantCenterAggregateStatusResultDTO;
use Maatify\Seo\Web\MerchantCenter\DTO\MerchantCenterDestinationStatusDTO;
use Maatify\Seo\Web\MerchantCenter\DTO\MerchantCenterItemIssueDTO;
use Maatify\Seo\Web\MerchantCenter\DTO\MerchantCenterProductStatusResultDTO;
use Maatify\Seo\Web\MerchantCenter\Exception\MerchantCenterMalformedResponseException;

final class MerchantCenterResponseMapper
{
    /**
     * @param array<string, mixed> $decodedProviderResponse
     */
    public function mapProductResponse(array $decodedProviderResponse): MerchantCenterProductStatusResultDTO
    {
        $productName = $this->requiredString(
            $decodedProviderResponse,
            'name',
            'name',
        );

        if (!array_key_exists('productStatus', $decodedProviderResponse)) {
            return new MerchantCenterProductStatusResultDTO(
                productName: $productName,
                destinationStatuses: [],
                itemLevelIssues: [],
                creationDate: null,
                lastUpdateDate: null,
                googleExpirationDate: null,
            );
        }

        $productStatus = $this->requiredObject(
            $decodedProviderResponse,
            'productStatus',
            'productStatus',
        );

        /** @var list<MerchantCenterDestinationStatusDTO> $destinationStatuses */
        $destinationStatuses = [];
        foreach ($this->optionalList($productStatus, 'destinationStatuses', 'productStatus.destinationStatuses') as $index => $rawDestinationStatus) {
            $destinationPath = 'productStatus.destinationStatuses.' . $index;
            $destinationStatus = $this->objectAt($rawDestinationStatus, $destinationPath);

            $destinationStatuses[] = new MerchantCenterDestinationStatusDTO(
                reportingContext: $this->requiredString(
                    $destinationStatus,
                    'reportingContext',
                    $destinationPath . '.reportingContext',
                ),
                approvedCountries: $this->optionalStringList(
                    $destinationStatus,
                    'approvedCountries',
                    $destinationPath . '.approvedCountries',
                ),
                pendingCountries: $this->optionalStringList(
                    $destinationStatus,
                    'pendingCountries',
                    $destinationPath . '.pendingCountries',
                ),
                disapprovedCountries: $this->optionalStringList(
                    $destinationStatus,
                    'disapprovedCountries',
                    $destinationPath . '.disapprovedCountries',
                ),
            );
        }

        /** @var list<MerchantCenterItemIssueDTO> $itemLevelIssues */
        $itemLevelIssues = [];
        foreach ($this->optionalList($productStatus, 'itemLevelIssues', 'productStatus.itemLevelIssues') as $index => $rawIssue) {
            $issuePath = 'productStatus.itemLevelIssues.' . $index;
            $issue = $this->objectAt($rawIssue, $issuePath);

            $itemLevelIssues[] = new MerchantCenterItemIssueDTO(
                code: $this->optionalString($issue, 'code', $issuePath . '.code'),
                severity: $this->optionalString($issue, 'severity', $issuePath . '.severity'),
                resolution: $this->optionalString($issue, 'resolution', $issuePath . '.resolution'),
                attribute: $this->optionalString($issue, 'attribute', $issuePath . '.attribute'),
                reportingContext: $this->optionalString(
                    $issue,
                    'reportingContext',
                    $issuePath . '.reportingContext',
                ),
                description: $this->optionalString($issue, 'description', $issuePath . '.description'),
                detail: $this->optionalString($issue, 'detail', $issuePath . '.detail'),
                documentation: $this->optionalString(
                    $issue,
                    'documentation',
                    $issuePath . '.documentation',
                ),
                applicableCountries: $this->optionalStringList(
                    $issue,
                    'applicableCountries',
                    $issuePath . '.applicableCountries',
                ),
            );
        }

        return new MerchantCenterProductStatusResultDTO(
            productName: $productName,
            destinationStatuses: $destinationStatuses,
            itemLevelIssues: $itemLevelIssues,
            creationDate: $this->optionalString(
                $productStatus,
                'creationDate',
                'productStatus.creationDate',
            ),
            lastUpdateDate: $this->optionalString(
                $productStatus,
                'lastUpdateDate',
                'productStatus.lastUpdateDate',
            ),
            googleExpirationDate: $this->optionalString(
                $productStatus,
                'googleExpirationDate',
                'productStatus.googleExpirationDate',
            ),
        );
    }

    /**
     * @param array<string, mixed> $decodedProviderResponse
     */
    public function mapAggregateStatusListResponse(
        array $decodedProviderResponse,
    ): MerchantCenterAggregateStatusListResultDTO {
        /** @var list<MerchantCenterAggregateStatusResultDTO> $statuses */
        $statuses = [];
        foreach ($this->optionalList(
            $decodedProviderResponse,
            'aggregateProductStatuses',
            'aggregateProductStatuses',
        ) as $index => $rawStatus) {
            $statusPath = 'aggregateProductStatuses.' . $index;
            $status = $this->objectAt($rawStatus, $statusPath);

            $statuses[] = new MerchantCenterAggregateStatusResultDTO(
                name: $this->requiredString($status, 'name', $statusPath . '.name'),
                reportingContext: $this->requiredString(
                    $status,
                    'reportingContext',
                    $statusPath . '.reportingContext',
                ),
                country: $this->requiredString($status, 'country', $statusPath . '.country'),
                stats: $this->mapAggregateStatistics($status, $statusPath),
                itemLevelIssues: $this->mapAggregateIssues($status, $statusPath),
            );
        }

        return new MerchantCenterAggregateStatusListResultDTO(
            statuses: $statuses,
            nextPageToken: $this->optionalString(
                $decodedProviderResponse,
                'nextPageToken',
                'nextPageToken',
            ),
        );
    }

    /**
     * @param array<string, mixed> $status
     */
    private function mapAggregateStatistics(
        array $status,
        string $statusPath,
    ): ?MerchantCenterAggregateStatisticsDTO {
        if (!array_key_exists('stats', $status) || $status['stats'] === null) {
            return null;
        }

        $stats = $this->requiredObject($status, 'stats', $statusPath . '.stats');

        return new MerchantCenterAggregateStatisticsDTO(
            activeCount: $this->requiredString($stats, 'activeCount', $statusPath . '.stats.activeCount'),
            pendingCount: $this->requiredString($stats, 'pendingCount', $statusPath . '.stats.pendingCount'),
            disapprovedCount: $this->requiredString(
                $stats,
                'disapprovedCount',
                $statusPath . '.stats.disapprovedCount',
            ),
            expiringCount: $this->requiredString($stats, 'expiringCount', $statusPath . '.stats.expiringCount'),
        );
    }

    /**
     * @param array<string, mixed> $status
     * @return list<MerchantCenterAggregateIssueDTO>
     */
    private function mapAggregateIssues(array $status, string $statusPath): array
    {
        /** @var list<MerchantCenterAggregateIssueDTO> $issues */
        $issues = [];
        foreach ($this->optionalList(
            $status,
            'itemLevelIssues',
            $statusPath . '.itemLevelIssues',
        ) as $index => $rawIssue) {
            $issuePath = $statusPath . '.itemLevelIssues.' . $index;
            $issue = $this->objectAt($rawIssue, $issuePath);

            $issues[] = new MerchantCenterAggregateIssueDTO(
                code: $this->optionalString($issue, 'code', $issuePath . '.code'),
                severity: $this->optionalString($issue, 'severity', $issuePath . '.severity'),
                resolution: $this->optionalString($issue, 'resolution', $issuePath . '.resolution'),
                attribute: $this->optionalString($issue, 'attribute', $issuePath . '.attribute'),
                description: $this->optionalString($issue, 'description', $issuePath . '.description'),
                detail: $this->optionalString($issue, 'detail', $issuePath . '.detail'),
                documentationUri: $this->optionalString(
                    $issue,
                    'documentationUri',
                    $issuePath . '.documentationUri',
                ),
                productCount: $this->optionalString($issue, 'productCount', $issuePath . '.productCount'),
            );
        }

        return $issues;
    }

    /**
     * @param array<string, mixed> $parent
     * @return array<string, mixed>
     */
    private function requiredObject(array $parent, string $field, string $path): array
    {
        if (!array_key_exists($field, $parent) || !is_array($parent[$field]) || array_is_list($parent[$field])) {
            throw MerchantCenterMalformedResponseException::forPath($path, 'an object is required');
        }

        /** @var array<string, mixed> $value */
        $value = $parent[$field];
        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function objectAt(mixed $value, string $path): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw MerchantCenterMalformedResponseException::forPath($path, 'an object is required');
        }

        /** @var array<string, mixed> $object */
        $object = $value;
        return $object;
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
            throw MerchantCenterMalformedResponseException::forPath($path, 'a list is required');
        }

        /** @var list<mixed> $value */
        $value = $parent[$field];
        return $value;
    }

    /**
     * @param array<string, mixed> $parent
     * @return list<string>
     */
    private function optionalStringList(array $parent, string $field, string $path): array
    {
        $values = $this->optionalList($parent, $field, $path);
        /** @var list<string> $strings */
        $strings = [];

        foreach ($values as $index => $value) {
            if (!is_string($value)) {
                throw MerchantCenterMalformedResponseException::forPath(
                    $path . '.' . $index,
                    'a string is required',
                );
            }

            $strings[] = $value;
        }

        return $strings;
    }

    /**
     * @param array<string, mixed> $parent
     */
    private function requiredString(array $parent, string $field, string $path): string
    {
        if (!array_key_exists($field, $parent) || !is_string($parent[$field])) {
            throw MerchantCenterMalformedResponseException::forPath($path, 'a string is required');
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
            throw MerchantCenterMalformedResponseException::forPath($path, 'a string or null is required');
        }

        return $parent[$field];
    }
}
