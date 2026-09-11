<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\Profile;

use Maatify\Seo\Web\Validation\DTO\SeoCompanionDiagnosticDTO;
use Maatify\Seo\Web\Validation\DTO\SeoCompanionValidationResultDTO;
use Maatify\Seo\Web\Validation\DTO\SeoDiagnosticTargetDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationContextDTO;
use Maatify\Seo\Web\Validation\Input\RobotsTxtValidationInputDTO;
use Maatify\Seo\Web\Validation\Profile\Internal\AbsoluteAuthorityUrlLexicalProfile;
use Maatify\Seo\Web\Validation\Profile\Internal\RobotsTxtParser;

final class GoogleRobotsTxtValidator
{
    private const GOOGLE_PARSE_LIMIT_BYTES = 512000;

    public function validate(
        RobotsTxtValidationInputDTO $input,
        ?SeoValidationContextDTO $context = null,
    ): SeoCompanionValidationResultDTO {
        $diagnostics = [];

        if (strlen($input->content) > self::GOOGLE_PARSE_LIMIT_BYTES) {
            $diagnostics[] = new SeoCompanionDiagnosticDTO(
                code: 'robots_google_document_size_exceeds_parse_limit',
                severity: 'warning',
                message: 'The robots.txt document exceeds the Google 500 KiB parse limit.',
                field: 'document',
                origin: 'provider',
                profile: 'google',
                target: new SeoDiagnosticTargetDTO('robots_document'),
            );
        }

        if (preg_match('//u', $input->content) !== 1) {
            $diagnostics[] = new SeoCompanionDiagnosticDTO(
                code: 'robots_google_document_invalid_utf8',
                severity: 'warning',
                message: 'The raw robots.txt document is not valid UTF-8.',
                field: 'document',
                origin: 'provider',
                profile: 'google',
                target: new SeoDiagnosticTargetDTO('robots_document'),
            );
        }

        foreach (RobotsTxtParser::parse($input->content) as $record) {
            if ($record['name'] === 'allow' || $record['name'] === 'disallow') {
                if ($record['value'] !== '' && !str_starts_with($record['value'], '/')) {
                    $diagnostics[] = new SeoCompanionDiagnosticDTO(
                        code: 'robots_google_present_path_leading_slash',
                        severity: 'warning',
                        message: 'A present Google Allow/Disallow path should begin with /.',
                        field: 'path',
                        origin: 'provider',
                        profile: 'google',
                        target: new SeoDiagnosticTargetDTO('robots_rule', line: $record['line']),
                    );
                }

                continue;
            }

            if ($record['name'] !== 'sitemap') {
                continue;
            }

            if (trim($record['value']) === '' || !AbsoluteAuthorityUrlLexicalProfile::accepts($record['value'], false)) {
                $diagnostics[] = new SeoCompanionDiagnosticDTO(
                    code: 'robots_google_sitemap_url_not_fully_qualified',
                    severity: 'warning',
                    message: 'The Google Sitemap directive must contain a fully-qualified absolute URL without a fragment.',
                    field: 'sitemap',
                    origin: 'provider',
                    profile: 'google',
                    target: new SeoDiagnosticTargetDTO('robots_rule', line: $record['line']),
                );
            }
        }

        return new SeoCompanionValidationResultDTO(diagnostics: $diagnostics);
    }
}
