<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Render;

use Maatify\Seo\Shared\DTO\MetaTagsDTO;

final readonly class MetaTagsHtmlRenderer
{
    public function render(MetaTagsDTO $metaTags): string
    {
        $tags = [];
        $this->appendTag($tags, '<title>' . $this->escapeText($metaTags->title) . '</title>', $metaTags->title);
        $this->appendTag($tags, '<meta name="description" content="' . $this->escapeAttribute((string) $metaTags->description) . '">', $metaTags->description);
        $this->appendTag($tags, '<link rel="canonical" href="' . $this->escapeAttribute((string) $metaTags->canonicalUrl) . '">', $metaTags->canonicalUrl);
        $this->appendTag($tags, '<meta name="robots" content="' . $this->escapeAttribute($metaTags->robots) . '">', $metaTags->robots);

        return implode("\n", $tags);
    }

    /** @param list<string> $tags */
    private function appendTag(array &$tags, string $tag, ?string $value): void
    {
        if ($value !== null && trim($value) !== '') {
            $tags[] = $tag;
        }
    }

    private function escapeText(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    private function escapeAttribute(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
