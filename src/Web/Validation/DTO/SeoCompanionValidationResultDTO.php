<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\DTO;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class SeoCompanionValidationResultDTO implements \JsonSerializable
{
    /** @var list<SeoCompanionDiagnosticDTO> */
    public array $diagnostics;

    /** @param array<mixed> $diagnostics */
    public function __construct(
        public ?SeoValidationResultDTO $legacy = null,
        array $diagnostics = [],
    ) {
        if (!array_is_list($diagnostics)) {
            throw SeoInvalidArgumentException::invalidValue('diagnostics', 'Expected a list of companion diagnostics.');
        }

        $validatedDiagnostics = [];
        foreach ($diagnostics as $index => $diagnostic) {
            if (!$diagnostic instanceof SeoCompanionDiagnosticDTO) {
                throw SeoInvalidArgumentException::invalidValue("diagnostics.{$index}", 'Expected a SeoCompanionDiagnosticDTO.');
            }

            $validatedDiagnostics[] = $diagnostic;
        }

        $this->diagnostics = $validatedDiagnostics;
    }

    /** @return array{legacy: array<string, mixed>|null, diagnostics: list<array<string, mixed>>} */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /** @return array{legacy: array<string, mixed>|null, diagnostics: list<array<string, mixed>>} */
    public function toArray(): array
    {
        return [
            'legacy' => $this->legacy?->toArray(),
            'diagnostics' => array_map(
                static fn (SeoCompanionDiagnosticDTO $diagnostic): array => $diagnostic->toArray(),
                $this->diagnostics,
            ),
        ];
    }
}
