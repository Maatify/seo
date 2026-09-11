<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\Profile;

use Maatify\Seo\Web\Validation\DTO\SeoCompanionValidationResultDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationContextDTO;
use Maatify\Seo\Web\Validation\Input\Sitemap\SitemapUrlValidationInputDTO;
use Maatify\Seo\Web\Validation\Input\Sitemap\SitemapValidationDocumentDTO;
use Maatify\Seo\Web\Validation\Profile\Internal\SitemapValidationSupport;

final class GoogleImageSitemapValidator
{
    public function validate(
        SitemapValidationDocumentDTO $document,
        ?SeoValidationContextDTO $context = null,
    ): SeoCompanionValidationResultDTO {
        $diagnostics = [];
        if ($document->type !== 'urlset') {
            return SitemapValidationSupport::result($diagnostics);
        }

        foreach ($document->entries as $entryIndex => $entry) {
            if (!$entry instanceof SitemapUrlValidationInputDTO) {
                continue;
            }

            if (count($entry->images) > 1000) {
                $diagnostics[] = SitemapValidationSupport::diagnostic(
                    'google_image_count_exceeds_limit',
                    'warning',
                    'The URL contains more than 1,000 image entries.',
                    'image',
                    'sitemap_url',
                    $entryIndex,
                );
            }

            foreach ($entry->images as $imageIndex => $_image) {
                $verificationState = SitemapValidationSupport::indexedEvidence(
                    $context,
                    'google_image.cross_domain_verification',
                    $entryIndex,
                    $imageIndex,
                );
                $diagnostics[] = SitemapValidationSupport::diagnostic(
                    'google_image_cross_domain_verification',
                    $verificationState === 'unverified' ? 'warning' : 'info',
                    'Google cross-domain image verification is represented only by caller-supplied evidence.',
                    'image',
                    'sitemap_image',
                    $entryIndex,
                    $imageIndex,
                    $verificationState,
                );

                $crawlabilityState = SitemapValidationSupport::indexedEvidence(
                    $context,
                    'google_image.crawlability',
                    $entryIndex,
                    $imageIndex,
                );
                $diagnostics[] = SitemapValidationSupport::diagnostic(
                    'google_image_crawlability_context',
                    $crawlabilityState === 'inaccessible' ? 'warning' : 'info',
                    'Google image crawlability is represented only by caller-supplied evidence.',
                    'image',
                    'sitemap_image',
                    $entryIndex,
                    $imageIndex,
                    $crawlabilityState,
                );
            }
        }

        return SitemapValidationSupport::result($diagnostics);
    }
}
