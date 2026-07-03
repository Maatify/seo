<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Social;

final readonly class SocialMetaTag
{
    public function __construct(
        private string $name,
        private string $content,
        private string $attribute = 'property',
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getAttribute(): string
    {
        return $this->attribute;
    }

    /**
     * @return array{name: string, content: string, attribute: string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'content' => $this->content,
            'attribute' => $this->attribute,
        ];
    }

    public function toHtml(): string
    {
        return '<meta ' . $this->escape($this->attribute) . '="' . $this->escape($this->name) . '" content="' . $this->escape($this->content) . '">';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
