<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\Profile;

use Maatify\Seo\Web\Validation\DTO\SeoCompanionValidationResultDTO;
use Maatify\Seo\Web\Validation\DTO\SeoDiagnosticTargetDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationContextDTO;
use Maatify\Seo\Web\Validation\Profile\Internal\SitemapValidationSupport;

final class GoogleCanonicalValidator
{
    public function validate(
        string $canonical,
        ?SeoValidationContextDTO $context = null,
    ): SeoCompanionValidationResultDTO {
        unset($context);

        if ($canonical === '' || preg_match('/\A[A-Za-z][A-Za-z0-9+.-]*:\/\//', $canonical) === 1) {
            return SitemapValidationSupport::result([]);
        }

        return SitemapValidationSupport::result([
            new \Maatify\Seo\Web\Validation\DTO\SeoCompanionDiagnosticDTO(
                code: 'canonical_relative_provider_best_practice',
                severity: 'warning',
                message: 'Google recommends an absolute canonical URL instead of a relative canonical path.',
                field: 'href',
                origin: 'provider',
                profile: 'google',
                target: new SeoDiagnosticTargetDTO('canonical'),
            ),
        ]);
    }
}
