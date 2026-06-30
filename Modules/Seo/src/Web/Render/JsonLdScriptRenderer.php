<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Render;

final readonly class JsonLdScriptRenderer
{
    public function render(mixed $schemas): string
    {
        $normalizedSchemas = $this->normalizeSchemas($schemas);
        $scripts = [];

        foreach ($normalizedSchemas as $schema) {
            if ($schema === []) {
                continue;
            }

            $scripts[] = '<script type="application/ld+json">'
                . json_encode($schema, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
                . '</script>';
        }

        return implode("\n", $scripts);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeSchemas(mixed $schemas): array
    {
        if ($schemas === null) {
            return [];
        }

        if ($schemas instanceof \JsonSerializable) {
            $serialized = $schemas->jsonSerialize();
            return is_array($serialized) ? [$serialized] : [];
        }

        if (!is_array($schemas) || $schemas === []) {
            return [];
        }

        if (!$this->isList($schemas)) {
            /** @var array<string, mixed> $schemas */
            return [$schemas];
        }

        $normalized = [];
        foreach ($schemas as $schema) {
            if ($schema instanceof \JsonSerializable) {
                $schema = $schema->jsonSerialize();
            }

            if (is_array($schema)) {
                /** @var array<string, mixed> $schema */
                $normalized[] = $schema;
            }
        }

        return $normalized;
    }

    /** @param array<mixed> $value */
    private function isList(array $value): bool
    {
        return array_is_list($value);
    }
}
