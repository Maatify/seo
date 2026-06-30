<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Render;

use Maatify\Seo\Shared\DTO\MetaTagsDTO;
use Maatify\Seo\Web\SeoRender\DTO\SeoPagePayloadDTO;

final readonly class SeoHeadHtmlRenderer
{
    public function __construct(
        private MetaTagsHtmlRenderer $metaTagsHtmlRenderer = new MetaTagsHtmlRenderer(),
        private OpenGraphHtmlRenderer $openGraphHtmlRenderer = new OpenGraphHtmlRenderer(),
        private TwitterCardHtmlRenderer $twitterCardHtmlRenderer = new TwitterCardHtmlRenderer(),
        private JsonLdScriptRenderer $jsonLdScriptRenderer = new JsonLdScriptRenderer(),
    ) {
    }

    /** @param array<mixed> $schemas */
    public function render(MetaTagsDTO $metaTags, array $schemas = []): string
    {
        return $this->joinSections([
            $this->metaTagsHtmlRenderer->render($metaTags),
            $this->openGraphHtmlRenderer->render($metaTags),
            $this->twitterCardHtmlRenderer->render($metaTags),
            $this->jsonLdScriptRenderer->render($schemas),
        ]);
    }

    public function renderPayload(SeoPagePayloadDTO $payload): string
    {
        return $this->render($payload->metaTags, $payload->schemas);
    }

    /** @param list<string> $sections */
    private function joinSections(array $sections): string
    {
        return implode("\n", array_values(array_filter($sections, static fn (string $section): bool => $section !== '')));
    }
}
