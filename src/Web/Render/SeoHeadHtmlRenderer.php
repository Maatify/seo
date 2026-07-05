<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Render;

use Maatify\Seo\Shared\DTO\MetaTagsDTO;
use Maatify\Seo\Web\DTO\SeoHeadHtmlDTO;
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
        return $this->renderDto($metaTags, $schemas)->fullHtml;
    }

    /** @param array<mixed> $schemas */
    public function renderDto(MetaTagsDTO $metaTags, array $schemas = []): SeoHeadHtmlDTO
    {
        $metaHtml = $this->metaTagsHtmlRenderer->render($metaTags);
        $openGraphHtml = $this->openGraphHtmlRenderer->render($metaTags);
        $twitterCardHtml = $this->twitterCardHtmlRenderer->render($metaTags);
        $jsonLdHtml = $this->jsonLdScriptRenderer->render($schemas);

        return new SeoHeadHtmlDTO(
            metaHtml: $metaHtml,
            openGraphHtml: $openGraphHtml,
            twitterCardHtml: $twitterCardHtml,
            jsonLdHtml: $jsonLdHtml,
            fullHtml: $this->joinSections([
                $metaHtml,
                $openGraphHtml,
                $twitterCardHtml,
                $jsonLdHtml,
            ]),
        );
    }

    public function renderPayload(SeoPagePayloadDTO $payload): string
    {
        return $this->render($payload->metaTags, $payload->schemas);
    }

    public function renderPayloadDto(SeoPagePayloadDTO $payload): SeoHeadHtmlDTO
    {
        return $this->renderDto($payload->metaTags, $payload->schemas);
    }

    /** @param list<string> $sections */
    private function joinSections(array $sections): string
    {
        return implode("\n", array_values(array_filter($sections, static fn (string $section): bool => $section !== '')));
    }
}
