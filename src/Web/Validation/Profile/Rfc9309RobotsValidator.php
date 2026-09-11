<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\Profile;

use Maatify\Seo\Web\Validation\DTO\SeoCompanionDiagnosticDTO;
use Maatify\Seo\Web\Validation\DTO\SeoCompanionValidationResultDTO;
use Maatify\Seo\Web\Validation\DTO\SeoDiagnosticTargetDTO;
use Maatify\Seo\Web\Validation\DTO\SeoValidationContextDTO;
use Maatify\Seo\Web\Validation\Input\RobotsTxtValidationInputDTO;
use Maatify\Seo\Web\Validation\Profile\Internal\RobotsTxtParser;

final class Rfc9309RobotsValidator
{
    public function validate(
        RobotsTxtValidationInputDTO $input,
        ?SeoValidationContextDTO $context = null,
    ): SeoCompanionValidationResultDTO {
        $diagnostics = [];

        foreach (RobotsTxtParser::parse($input->content) as $record) {
            if ($record['name'] === 'user-agent') {
                if (self::hasForbiddenControl($record['value'])) {
                    $diagnostics[] = self::diagnostic(
                        'robots_rfc9309_control_character_invalid',
                        'user_agent',
                        $record['line'],
                        'A parsed User-agent product-token contains a forbidden control character.',
                    );
                } elseif (!self::isProductToken($record['value'])) {
                    $diagnostics[] = self::diagnostic(
                        'robots_rfc9309_product_token_invalid',
                        'user_agent',
                        $record['line'],
                        'The User-agent product-token must be an RFC identifier or *.',
                    );
                }

                continue;
            }

            if ($record['name'] !== 'allow' && $record['name'] !== 'disallow') {
                continue;
            }

            if (self::hasForbiddenControl($record['value'])) {
                $diagnostics[] = self::diagnostic(
                    'robots_rfc9309_control_character_invalid',
                    'path',
                    $record['line'],
                    'A parsed rule path contains a forbidden control character.',
                );
                continue;
            }

            if ($record['value'] === '') {
                continue;
            }

            if (str_starts_with($record['value'], '*')) {
                $diagnostics[] = self::diagnostic(
                    'robots_rfc9309_leading_wildcard_compatibility',
                    'path',
                    $record['line'],
                    'A leading wildcard path is preserved as a compatibility case.',
                );
                continue;
            }

            if (!str_starts_with($record['value'], '/')) {
                $diagnostics[] = self::diagnostic(
                    'robots_rfc9309_path_pattern_invalid',
                    'path',
                    $record['line'],
                    'A non-empty RFC path-pattern must begin with / or use the fixed leading-wildcard compatibility form.',
                );
            }
        }

        return new SeoCompanionValidationResultDTO(diagnostics: $diagnostics);
    }

    private static function isProductToken(string $value): bool
    {
        return $value === '*' || preg_match('/\A[-A-Za-z_]+\z/', $value) === 1;
    }

    private static function hasForbiddenControl(string $value): bool
    {
        return preg_match('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', $value) === 1;
    }

    private static function diagnostic(
        string $code,
        string $field,
        int $line,
        string $message,
    ): SeoCompanionDiagnosticDTO {
        return new SeoCompanionDiagnosticDTO(
            code: $code,
            severity: $code === 'robots_rfc9309_leading_wildcard_compatibility' ? 'warning' : 'error',
            message: $message,
            field: $field,
            origin: 'protocol',
            profile: 'rfc9309',
            target: new SeoDiagnosticTargetDTO('robots_rule', line: $line),
        );
    }
}
