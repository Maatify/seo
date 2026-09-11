<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\Profile;

use Maatify\Seo\Shared\DTO\Sitemap\SitemapUrlDTO;
use Maatify\Seo\Web\Validation\DTO\SeoCompanionValidationResultDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationContextDTO;
use Maatify\Seo\Web\Validation\Input\Sitemap\SitemapIndexEntryValidationInputDTO;
use Maatify\Seo\Web\Validation\Input\Sitemap\SitemapUrlValidationInputDTO;
use Maatify\Seo\Web\Validation\Input\Sitemap\SitemapValidationDocumentDTO;
use Maatify\Seo\Web\Validation\Profile\Internal\SitemapValidationSupport;

final class SitemapProtocolValidator
{
    private const MAX_ENTRIES = 50000;
    private const MAX_UNCOMPRESSED_SIZE_BYTES = 52428800;

    public function validate(
        SitemapValidationDocumentDTO $document,
        ?SeoValidationContextDTO $context = null,
    ): SeoCompanionValidationResultDTO {
        $diagnostics = [];
        $entryCount = count($document->entries);

        if ($document->type === 'urlset' && $entryCount > self::MAX_ENTRIES) {
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'sitemap_url_count_exceeds_limit',
                'error',
                'The URL Sitemap contains more than 50,000 URL entries.',
                'urlset',
                'sitemap_document',
            );
        }

        if ($document->type === 'sitemapindex' && $entryCount > self::MAX_ENTRIES) {
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'sitemap_index_count_exceeds_limit',
                'error',
                'The Sitemap Index contains more than 50,000 Sitemap entries.',
                'sitemapindex',
                'sitemap_document',
            );
        }

        if ($document->uncompressedSizeBytes !== null && $document->uncompressedSizeBytes > self::MAX_UNCOMPRESSED_SIZE_BYTES) {
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'sitemap_document_size_exceeds_boundary',
                'error',
                'The supplied uncompressed Sitemap document size exceeds 50 MB.',
                null,
                'sitemap_document',
            );
        }

        if ($document->type === 'urlset') {
            foreach ($document->entries as $entryIndex => $entry) {
                if (!$entry instanceof SitemapUrlValidationInputDTO) {
                    continue;
                }
                $this->validateUrlEntry($entry, $entryIndex, $document, $context, $diagnostics);
            }
        } else {
            foreach ($document->entries as $entryIndex => $entry) {
                if (!$entry instanceof SitemapIndexEntryValidationInputDTO) {
                    continue;
                }
                $this->validateIndexEntry($entry, $entryIndex, $document, $diagnostics);
            }
        }

        return SitemapValidationSupport::result($diagnostics);
    }

    /** @param list<\Maatify\Seo\Web\Validation\DTO\SeoCompanionDiagnosticDTO> $diagnostics */
    private function validateUrlEntry(
        SitemapUrlValidationInputDTO $entry,
        int $entryIndex,
        SitemapValidationDocumentDTO $document,
        ?SeoValidationContextDTO $context,
        array &$diagnostics,
    ): void {
        $loc = $entry->loc;
        if (SitemapValidationSupport::isMissing($loc)) {
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'sitemap_loc_missing',
                'error',
                'A URL Sitemap entry requires a non-empty loc value.',
                'loc',
                'sitemap_url',
                $entryIndex,
            );
        } else {
            if ($loc === null) {
                return;
            }
            if (strlen($loc) >= 2048) {
                $diagnostics[] = SitemapValidationSupport::diagnostic(
                    'sitemap_loc_length_exceeds_measure_boundary',
                    'warning',
                    'The supplied loc reaches the conservative 2,048-byte Sitemap measurement boundary.',
                    'loc',
                    'sitemap_url',
                    $entryIndex,
                );
            }
            $components = SitemapValidationSupport::urlComponents($loc, false);
            if ($components === null) {
                $diagnostics[] = SitemapValidationSupport::diagnostic(
                    'sitemap_loc_invalid_uri_iri',
                    'error',
                    'The URL Sitemap loc is not a valid absolute URI/IRI under the Sitemap lexical profile.',
                    'loc',
                    'sitemap_url',
                    $entryIndex,
                );
            }

            if ($components !== null && $document->location !== null && !SitemapValidationSupport::isWithinUrlScope($components, $document->location)) {
                $this->appendUrlScopeDiagnostic($entryIndex, $context, $diagnostics);
            }
        }

        if ($entry->lastmod !== null && !SitemapValidationSupport::isSitemapLastmod($entry->lastmod)) {
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'sitemap_lastmod_invalid_lexical',
                'error',
                'The Sitemap lastmod value is outside the fixed lexical contract.',
                'lastmod',
                'sitemap_url',
                $entryIndex,
            );
        }

        if ($entry->changefreq !== null && trim($entry->changefreq) !== '' && !in_array($entry->changefreq, SitemapUrlDTO::allowedChangefreqValues(), true)) {
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'sitemap_changefreq_invalid',
                'error',
                'The changefreq value is outside the fixed Sitemap vocabulary.',
                'changefreq',
                'sitemap_url',
                $entryIndex,
            );
        }

        if ($entry->priority !== null && (!is_finite((float) $entry->priority) || $entry->priority < 0 || $entry->priority > 1)) {
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'sitemap_priority_out_of_range',
                'error',
                'The priority value must be finite and within the inclusive 0..1 range.',
                'priority',
                'sitemap_url',
                $entryIndex,
            );
        }
    }

    /** @param list<\Maatify\Seo\Web\Validation\DTO\SeoCompanionDiagnosticDTO> $diagnostics */
    private function validateIndexEntry(
        SitemapIndexEntryValidationInputDTO $entry,
        int $entryIndex,
        SitemapValidationDocumentDTO $document,
        array &$diagnostics,
    ): void {
        $loc = $entry->loc;
        if (SitemapValidationSupport::isMissing($loc)) {
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'sitemap_loc_missing',
                'error',
                'A Sitemap Index entry requires a non-empty sitemap value.',
                'sitemap',
                'sitemap_index_entry',
                $entryIndex,
            );
        } else {
            $components = SitemapValidationSupport::urlComponents($loc, false);
            if ($components === null) {
                $diagnostics[] = SitemapValidationSupport::diagnostic(
                    'sitemap_loc_invalid_uri_iri',
                    'error',
                    'The Sitemap Index child loc is not a valid absolute URI/IRI under the Sitemap lexical profile.',
                    'sitemap',
                    'sitemap_index_entry',
                    $entryIndex,
                );
            } elseif ($document->location !== null && !SitemapValidationSupport::isSameAuthority($components, $document->location)) {
                $diagnostics[] = SitemapValidationSupport::diagnostic(
                    'sitemap_location_scope_violation',
                    'error',
                    'A Sitemap Index child must remain on the same site authority as the index location.',
                    'sitemap',
                    'sitemap_index_entry',
                    $entryIndex,
                );
            }
        }

        if ($entry->lastmod !== null && !SitemapValidationSupport::isSitemapLastmod($entry->lastmod)) {
            $diagnostics[] = SitemapValidationSupport::diagnostic(
                'sitemap_lastmod_invalid_lexical',
                'error',
                'The Sitemap Index lastmod value is outside the fixed lexical contract.',
                'lastmod',
                'sitemap_index_entry',
                $entryIndex,
            );
        }
    }

    /** @param list<\Maatify\Seo\Web\Validation\DTO\SeoCompanionDiagnosticDTO> $diagnostics */
    private function appendUrlScopeDiagnostic(int $entryIndex, ?SeoValidationContextDTO $context, array &$diagnostics): void
    {
        if (SitemapValidationSupport::crossSubmissionState($context, $entryIndex) !== 'unauthorized') {
            return;
        }

        $diagnostics[] = SitemapValidationSupport::diagnostic(
            'sitemap_location_scope_violation',
            'error',
            'The URL Sitemap entry is outside the document location scope without authorized cross-submission evidence.',
            'loc',
            'sitemap_url',
            $entryIndex,
        );
    }
}
