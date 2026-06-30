<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Render;

use Maatify\Seo\Shared\DTO\MetaTagsDTO;

final readonly class OpenGraphHtmlRenderer
{
    public function render(MetaTagsDTO $metaTags): string
    {
        $tags = [];
        $this->appendProperty($tags, 'og:title', $metaTags->openGraphTitle);
        $this->appendProperty($tags, 'og:description', $metaTags->openGraphDescription);
        $this->appendProperty($tags, 'og:type', $metaTags->openGraphType);
        $this->appendProperty($tags, 'og:url', $metaTags->openGraphUrl);
        $this->appendProperty($tags, 'og:image', $metaTags->openGraphImage);

        return implode("\n", $tags);
    }

    /** @param list<string> $tags */
    private function appendProperty(array &$tags, string $property, ?string $value): void
    {
        if ($value === null || trim($value) === '') {
            return;
        }

        $tags[] = '<meta property="' . $this->escapeAttribute($property) . '" content="' . $this->escapeAttribute($value) . '">';
    }

    private function escapeAttribute(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
