<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

trait JsonLdBuilderTrait
{
    /** @var array<string, mixed> */
    protected array $schema = [];

    public function set(string $key, mixed $value): static
    {
        $this->schema[$key] = $value;

        return $this;
    }

    public function remove(string $key): static
    {
        unset($this->schema[$key]);

        return $this;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->schema);
    }

    public function get(string $key): mixed
    {
        return $this->schema[$key] ?? null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $schema = $this->schema;

        foreach ($schema as $key => $value) {
            $schema[$key] = $this->resolveNode($value);
        }

        return $schema;
    }

    protected function resolveNode(mixed $node): mixed
    {
        if ($node instanceof JsonLdBuilderInterface) {
            $resolved = $node->toArray();
            unset($resolved['@context']);

            foreach ($resolved as $key => $value) {
                $resolved[$key] = $this->resolveNode($value);
            }

            return $resolved;
        }

        if (is_array($node)) {
            $resolved = $node;

            foreach ($resolved as $key => $value) {
                $resolved[$key] = $this->resolveNode($value);
            }

            return $resolved;
        }

        return $node;
    }

    public function toJson(int $flags = 0): string
    {
        try {
            return json_encode($this->toArray(), $flags | JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw JsonLdBuildException::encodingFailed($exception);
        }
    }
}
