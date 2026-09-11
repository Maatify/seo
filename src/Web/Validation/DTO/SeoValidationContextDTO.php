<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\DTO;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class SeoValidationContextDTO
{
    /**
     * @var array<string, array{shape: string, states: list<string>}>
     */
    private const EVIDENCE_CONTRACTS = [
        'google_sitemap.lastmod_accuracy' => ['shape' => 'indexed', 'states' => ['accurate', 'inaccurate', 'unknown']],
        'google_sitemap.host_verification' => ['shape' => 'scalar', 'states' => ['verified_host', 'unverified_host', 'unknown']],
        'sitemaps.cross_submission_authority' => ['shape' => 'indexed', 'states' => ['authorized', 'unauthorized', 'unknown']],
        'google_image.cross_domain_verification' => ['shape' => 'nested', 'states' => ['verified', 'unverified', 'unknown']],
        'google_image.crawlability' => ['shape' => 'nested', 'states' => ['accessible', 'inaccessible', 'unknown']],
        'google_video.relevance' => ['shape' => 'nested', 'states' => ['relevant', 'irrelevant', 'unknown']],
        'google_video.title_host_page_match' => ['shape' => 'nested', 'states' => ['matches', 'differs', 'unknown']],
        'google_video.description_host_page_match' => ['shape' => 'nested', 'states' => ['matches', 'differs', 'unknown']],
        'google_news.original_publication' => ['shape' => 'nested', 'states' => ['original', 'not_original', 'unknown']],
        'google_news.publication_name_match' => ['shape' => 'nested', 'states' => ['matched', 'mismatched', 'unknown']],
        'google_news.freshness' => ['shape' => 'nested', 'states' => ['within_window', 'outside_window', 'unknown']],
        'google_news.title_content_conformance' => ['shape' => 'nested', 'states' => ['conforming', 'nonconforming', 'unknown']],
        'robots_meta.unavailable_after_recognizability' => ['shape' => 'scalar', 'states' => ['recognized', 'unrecognized', 'unknown']],
    ];

    /** @param array<string|int, mixed>|null $evidence */
    public function __construct(
        public ?array $evidence = null,
    ) {
        if ($this->evidence === null) {
            return;
        }

        foreach ($this->evidence as $key => $value) {
            if (!is_string($key) || !isset(self::EVIDENCE_CONTRACTS[$key])) {
                continue;
            }

            $contract = self::EVIDENCE_CONTRACTS[$key];
            if ($contract['shape'] === 'scalar') {
                self::assertState($key, $value, $contract['states']);
                continue;
            }

            if (!is_array($value)) {
                throw SeoInvalidArgumentException::invalidValue("evidence.{$key}", 'Expected an indexed evidence map.');
            }

            foreach ($value as $index => $stateOrChildren) {
                self::assertIndex("evidence.{$key}", $index);

                if ($contract['shape'] === 'indexed') {
                    self::assertState("evidence.{$key}.{$index}", $stateOrChildren, $contract['states']);
                    continue;
                }

                if (!is_array($stateOrChildren)) {
                    throw SeoInvalidArgumentException::invalidValue("evidence.{$key}.{$index}", 'Expected a nested indexed evidence map.');
                }

                foreach ($stateOrChildren as $childIndex => $state) {
                    self::assertIndex("evidence.{$key}.{$index}", $childIndex);
                    self::assertState("evidence.{$key}.{$index}.{$childIndex}", $state, $contract['states']);
                }
            }
        }
    }

    /** @param list<string> $states */
    private static function assertState(string $field, mixed $value, array $states): void
    {
        if (!is_string($value) || !in_array($value, $states, true)) {
            throw SeoInvalidArgumentException::invalidValue($field, 'Expected one of the authorized evidence states.');
        }
    }

    private static function assertIndex(string $field, mixed $index): void
    {
        if (!is_int($index) || $index < 0) {
            throw SeoInvalidArgumentException::invalidValue($field, 'Expected a zero-based non-negative integer index.');
        }
    }
}
