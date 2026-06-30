<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Render;

use Maatify\Seo\Shared\DTO\MetaTagsDTO;

final readonly class TwitterCardHtmlRenderer
{
    public function render(MetaTagsDTO $metaTags): string
    {
        $tags = [];
        $this->appendName($tags, 'twitter:title', $metaTags->twitterTitle);
        $this->appendName($tags, 'twitter:description', $metaTags->twitterDescription);

        return implode("\n", $tags);
    }

    /** @param list<string> $tags */
    private function appendName(array &$tags, string $name, ?string $value): void
    {
        if ($value === null || trim($value) === '') {
            return;
        }

        $tags[] = '<meta name="' . $this->escapeAttribute($name) . '" content="' . $this->escapeAttribute($value) . '">';
    }

    private function escapeAttribute(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
