<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\DTO;

final readonly class SeoValidationResultDTO implements \JsonSerializable
{
    public bool $isValid;
    public bool $hasWarnings;

    /** @var list<SeoValidationIssueDTO> */
    public array $errors;

    /** @var list<SeoValidationIssueDTO> */
    public array $warnings;

    /** @var list<SeoValidationIssueDTO> */
    public array $info;

    /**
     * @param list<SeoValidationIssueDTO> $issues
     */
    public function __construct(
        public array $issues = [],
    ) {
        $errors = [];
        $warnings = [];
        $info = [];

        foreach ($this->issues as $issue) {
            if ($issue->severity === SeoValidationIssueDTO::SEVERITY_ERROR) {
                $errors[] = $issue;
                continue;
            }

            if ($issue->severity === SeoValidationIssueDTO::SEVERITY_WARNING) {
                $warnings[] = $issue;
                continue;
            }

            $info[] = $issue;
        }

        $this->errors = $errors;
        $this->warnings = $warnings;
        $this->info = $info;
        $this->isValid = $this->errors === [];
        $this->hasWarnings = $this->warnings !== [];
    }

    /**
     * @return array{is_valid: bool, has_warnings: bool, errors: list<array{code: string, severity: string, message: string, field: string|null}>, warnings: list<array{code: string, severity: string, message: string, field: string|null}>, info: list<array{code: string, severity: string, message: string, field: string|null}>, issues: list<array{code: string, severity: string, message: string, field: string|null}>}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return array{is_valid: bool, has_warnings: bool, errors: list<array{code: string, severity: string, message: string, field: string|null}>, warnings: list<array{code: string, severity: string, message: string, field: string|null}>, info: list<array{code: string, severity: string, message: string, field: string|null}>, issues: list<array{code: string, severity: string, message: string, field: string|null}>}
     */
    public function toArray(): array
    {
        return [
            'is_valid' => $this->isValid,
            'has_warnings' => $this->hasWarnings,
            'errors' => array_map(static fn (SeoValidationIssueDTO $issue): array => $issue->toArray(), $this->errors),
            'warnings' => array_map(static fn (SeoValidationIssueDTO $issue): array => $issue->toArray(), $this->warnings),
            'info' => array_map(static fn (SeoValidationIssueDTO $issue): array => $issue->toArray(), $this->info),
            'issues' => array_map(static fn (SeoValidationIssueDTO $issue): array => $issue->toArray(), $this->issues),
        ];
    }
}
