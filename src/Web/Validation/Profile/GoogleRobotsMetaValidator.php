<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\Profile;

use Maatify\Seo\Web\Validation\DTO\SeoCompanionDiagnosticDTO;
use Maatify\Seo\Web\Validation\DTO\SeoCompanionValidationResultDTO;
use Maatify\Seo\Web\Validation\DTO\SeoDiagnosticTargetDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationContextDTO;
use Maatify\Seo\Web\Validation\Input\RobotsMetaValidationInputDTO;

final class GoogleRobotsMetaValidator
{
    public function validate(
        RobotsMetaValidationInputDTO $input,
        ?SeoValidationContextDTO $context = null,
    ): SeoCompanionValidationResultDTO {
        $diagnostics = [];
        $hasNoIndex = false;
        $unavailableAfterValues = [];

        foreach ($input->directives as $directive) {
            $trimmedDirective = trim($directive);
            $normalized = strtolower($trimmedDirective);
            if ($normalized === 'noindex') {
                $hasNoIndex = true;
                continue;
            }

            $separator = strpos($normalized, ':');
            if ($separator === false || substr($normalized, 0, $separator) !== 'unavailable_after') {
                continue;
            }

            $unavailableAfterValues[] = substr($trimmedDirective, $separator + 1);
        }

        foreach ($input->directives as $directive) {
            if (strtolower(trim($directive)) !== 'indexifembedded' || $hasNoIndex) {
                continue;
            }

            $diagnostics[] = new SeoCompanionDiagnosticDTO(
                code: 'robots_meta_indexifembedded_without_noindex',
                severity: 'warning',
                message: 'indexifembedded is present without noindex.',
                field: 'meta',
                origin: 'provider',
                profile: 'google',
                target: new SeoDiagnosticTargetDTO('robots_meta'),
            );
        }

        $evidenceState = self::recognizabilityEvidence($context);
        foreach ($unavailableAfterValues as $value) {
            if (trim($value) === '') {
                $diagnostics[] = new SeoCompanionDiagnosticDTO(
                    code: 'robots_meta_unavailable_after_missing',
                    severity: 'warning',
                    message: 'unavailable_after requires a non-empty value.',
                    field: 'meta',
                    origin: 'provider',
                    profile: 'google',
                    target: new SeoDiagnosticTargetDTO('robots_meta'),
                );
                continue;
            }

            $diagnostics[] = new SeoCompanionDiagnosticDTO(
                code: 'robots_meta_unavailable_after_recognizability',
                severity: $evidenceState === 'unrecognized' ? 'warning' : 'info',
                message: 'The recognizability of unavailable_after is represented by caller-supplied evidence.',
                field: 'meta',
                origin: 'provider',
                profile: 'google',
                evidenceState: $evidenceState,
                target: new SeoDiagnosticTargetDTO('robots_meta'),
            );
        }

        return new SeoCompanionValidationResultDTO(diagnostics: $diagnostics);
    }

    private static function recognizabilityEvidence(?SeoValidationContextDTO $context): string
    {
        $evidence = $context?->evidence['robots_meta.unavailable_after_recognizability'] ?? null;

        return is_string($evidence) && in_array($evidence, ['recognized', 'unrecognized', 'unknown'], true)
            ? $evidence
            : 'unknown';
    }
}
