<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

interface JsonLdBuilderInterface
{
    public function set(string $key, mixed $value): static;

    public function remove(string $key): static;

    public function has(string $key): bool;

    public function get(string $key): mixed;

    /** @return array<string, mixed> */
    public function toArray(): array;

    public function toJson(int $flags = 0): string;
}
