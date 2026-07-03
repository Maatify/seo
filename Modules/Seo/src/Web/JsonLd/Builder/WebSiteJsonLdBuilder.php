<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

final class WebSiteJsonLdBuilder extends AbstractJsonLdBuilder
{
    public function __construct()
    {
        parent::__construct([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
        ]);
    }

    public function setName(string $name): static
    {
        return $this->set('name', $name);
    }

    public function setUrl(string $url): static
    {
        return $this->set('url', $url);
    }

    public function setDescription(string $description): static
    {
        return $this->set('description', $description);
    }

    /** @param string|array<string, mixed> $publisher */
    public function setPublisher(string|array $publisher): static
    {
        if (is_string($publisher)) {
            $publisher = [
                '@type' => 'Organization',
                'name' => $publisher,
            ];
        }

        return $this->set('publisher', $publisher);
    }

    public function setSearchAction(string $targetUrlTemplate, string $searchTermParameter = 'search_term_string'): static
    {
        return $this->set('potentialAction', [
            '@type' => 'SearchAction',
            'target' => $targetUrlTemplate,
            'query-input' => 'required name=' . $searchTermParameter,
        ]);
    }

    /** @param array<string, mixed> $potentialAction */
    public function setPotentialAction(array $potentialAction): static
    {
        if (!isset($potentialAction['@type'])) {
            $potentialAction['@type'] = 'SearchAction';
        }

        return $this->set('potentialAction', $potentialAction);
    }
}
