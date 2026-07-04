<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Indexing;

final class CanonicalUrlBuilder
{
    private ?string $baseUrl;
    private string $path = '';

    /** @var array<string, string|int|float|bool|null> */
    private array $queryParams = [];

    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = $baseUrl;
    }

    public function setBaseUrl(string $baseUrl): static
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    public function setPath(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @param array<string, string|int|float|bool|null> $queryParams
     */
    public function setQueryParams(array $queryParams): static
    {
        $this->queryParams = $queryParams;

        return $this;
    }

    public function addQueryParam(string $key, string|int|float|bool|null $value): static
    {
        $this->queryParams[$key] = $value;

        return $this;
    }

    public function removeQueryParam(string $key): static
    {
        unset($this->queryParams[$key]);

        return $this;
    }

    public function clearQueryParams(): static
    {
        $this->queryParams = [];

        return $this;
    }

    /**
     * @param list<string> $allowedKeys
     */
    public function preserveQueryParams(array $allowedKeys): static
    {
        $allowed = array_flip($allowedKeys);

        $this->queryParams = array_filter(
            $this->queryParams,
            static fn (string $key): bool => isset($allowed[$key]),
            ARRAY_FILTER_USE_KEY
        );

        return $this;
    }

    public function build(): string
    {
        $url = $this->buildUrlWithoutQueryString();
        $queryString = http_build_query($this->normalizedQueryParams(), '', '&', PHP_QUERY_RFC3986);

        if ($queryString === '') {
            return $url;
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . $queryString;
    }

    public function toHtml(): string
    {
        $href = htmlspecialchars($this->build(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<link rel="canonical" href="' . $href . '">';
    }

    private function buildUrlWithoutQueryString(): string
    {
        if ($this->baseUrl === null) {
            return $this->path;
        }

        if ($this->path === '') {
            return rtrim($this->baseUrl, '/');
        }

        return rtrim($this->baseUrl, '/') . '/' . ltrim($this->path, '/');
    }

    /**
     * @return array<string, string|int|float|bool>
     */
    private function normalizedQueryParams(): array
    {
        $queryParams = [];

        foreach ($this->queryParams as $key => $value) {
            if ($value === null) {
                continue;
            }

            $queryParams[$key] = $value;
        }

        return $queryParams;
    }
}
