<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

final class ProductGroupJsonLdBuilder extends AbstractJsonLdBuilder
{
    public function __construct()
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => 'ProductGroup',
        ]);
    }

    public function setName(string $name): static
    {
        return $this->set('name', $name);
    }

    public function setDescription(string $description): static
    {
        return $this->set('description', $description);
    }

    /** @param string|array<int|string, mixed>|JsonLdBuilderInterface $brand */
    public function setBrand(string|array|JsonLdBuilderInterface $brand): static
    {
        if (is_string($brand)) {
            $brand = [
                '@type' => 'Brand',
                'name' => $brand,
            ];
        }

        return $this->set('brand', $brand);
    }

    public function setUrl(string $url): static
    {
        return $this->set('url', $url);
    }

    public function setProductGroupID(string $id): static
    {
        return $this->set('productGroupID', $id);
    }

    /** @param array<int, string> $properties */
    public function setVariesBy(array $properties): static
    {
        return $this->set('variesBy', $properties);
    }

    /** @param array<int|string, mixed>|JsonLdBuilderInterface ...$variants */
    public function setHasVariant(array|JsonLdBuilderInterface ...$variants): static
    {
        if ($variants === []) {
            return $this;
        }

        if (count($variants) === 1 && is_array($variants[0]) && $variants[0] === []) {
            return $this;
        }

        $nodes = $variants;
        if (count($variants) === 1 && is_array($variants[0]) && array_is_list($variants[0])) {
            $nodes = $variants[0];
        }

        return $this->set('hasVariant', count($nodes) === 1 ? $nodes[0] : $nodes);
    }

    /** @param array<int|string, mixed>|JsonLdBuilderInterface $variant */
    public function addVariant(array|JsonLdBuilderInterface $variant): static
    {
        $variants = $this->get('hasVariant');
        if ($variants === null) {
            $variants = $variant;
        } elseif (is_array($variants) && array_is_list($variants)) {
            $variants[] = $variant;
        } else {
            $variants = [$variants, $variant];
        }

        return $this->set('hasVariant', $variants);
    }
}
