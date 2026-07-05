<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\DTO;

final readonly class SeoHeadHtmlDTO implements \JsonSerializable
{
    public function __construct(
        public string $metaHtml,
        public string $openGraphHtml,
        public string $twitterCardHtml,
        public string $jsonLdHtml,
        public string $fullHtml,
    ) {
    }

    /**
     * @return array{meta_html: string, open_graph_html: string, twitter_card_html: string, json_ld_html: string, full_html: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'meta_html' => $this->metaHtml,
            'open_graph_html' => $this->openGraphHtml,
            'twitter_card_html' => $this->twitterCardHtml,
            'json_ld_html' => $this->jsonLdHtml,
            'full_html' => $this->fullHtml,
        ];
    }
}
