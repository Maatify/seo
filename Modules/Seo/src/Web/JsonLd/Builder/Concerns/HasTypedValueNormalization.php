<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder\Concerns;

trait HasTypedValueNormalization
{
    /**
     * @param string|array<string, mixed> $value
     * @return array<string, mixed>
     */
    protected function normalizeTypedValue(string|array $value, string $type, string $stringKey): array
    {
        if (is_string($value)) {
            return ['@type' => $type, $stringKey => $value];
        }

        if (!isset($value['@type'])) {
            $value['@type'] = $type;
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $value
     * @return array<string, mixed>
     */
    protected function defaultTypedValue(array $value, string $type): array
    {
        if (!isset($value['@type'])) {
            $value['@type'] = $type;
        }

        return $value;
    }
}
