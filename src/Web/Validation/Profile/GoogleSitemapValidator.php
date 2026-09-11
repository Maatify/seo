<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\Profile;

use Maatify\Seo\Exception\SeoInvalidArgumentException;
use Maatify\Seo\Web\Validation\DTO\SeoCompanionValidationResultDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationContextDTO;
use Maatify\Seo\Web\Validation\Input\Sitemap\SitemapUrlValidationInputDTO;
use Maatify\Seo\Web\Validation\Input\Sitemap\SitemapValidationDocumentDTO;
use Maatify\Seo\Web\Validation\Profile\Internal\SitemapValidationSupport;

final class GoogleSitemapValidator
{
    public function validate(
        SitemapValidationDocumentDTO $document,
        ?SeoValidationContextDTO $context = null,
    ): SeoCompanionValidationResultDTO {
        if ($document->type !== 'urlset') {
            throw SeoInvalidArgumentException::invalidValue('type', 'Google Sitemap validation requires a urlset document.');
        }

        $diagnostics = [];

        $hostState = SitemapValidationSupport::scalarEvidence($context, 'google_sitemap.host_verification');
        $diagnostics[] = SitemapValidationSupport::diagnostic(
            'google_sitemap_host_context',
            $hostState === 'unverified_host' ? 'warning' : 'info',
            'Google host verification is represented only by caller-supplied evidence.',
            null,
            'sitemap_document',
            evidenceState: $hostState,
        );

        foreach ($document->entries as $entryIndex => $entry) {
            if (!$entry instanceof SitemapUrlValidationInputDTO) {
                continue;
            }

            if ($entry->priority !== null) {
                $diagnostics[] = SitemapValidationSupport::diagnostic(
                    'google_sitemap_priority_ignored',
                    'info',
                    'Google ignores the Sitemap priority value.',
                    'priority',
                    'sitemap_url',
                    $entryIndex,
                );
            }

            if ($entry->changefreq !== null) {
                $diagnostics[] = SitemapValidationSupport::diagnostic(
                    'google_sitemap_changefreq_ignored',
                    'info',
                    'Google ignores the Sitemap changefreq value.',
                    'changefreq',
                    'sitemap_url',
                    $entryIndex,
                );
            }

            if ($entry->lastmod !== null && trim($entry->lastmod) !== '') {
                $lastmodState = SitemapValidationSupport::indexedEvidence($context, 'google_sitemap.lastmod_accuracy', $entryIndex);
                $diagnostics[] = SitemapValidationSupport::diagnostic(
                    'google_sitemap_lastmod_accuracy',
                    $lastmodState === 'inaccurate' ? 'warning' : 'info',
                    'Google lastmod accuracy is represented only by caller-supplied evidence.',
                    'lastmod',
                    'sitemap_url',
                    $entryIndex,
                    evidenceState: $lastmodState,
                );
            }
        }

        return SitemapValidationSupport::result($diagnostics);
    }
}
