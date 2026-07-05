<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\DTO\Schema;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class BreadcrumbItemDTO implements \JsonSerializable
{
    public function __construct(
        public string $name,
        public string $url,
        public int $position,
    ) {
        if (trim($this->name) === '') {
            throw SeoInvalidArgumentException::emptyField('name');
        }
        if (trim($this->url) === '') {
            throw SeoInvalidArgumentException::emptyField('url');
        }
        if ($this->position < 1) {
            throw SeoInvalidArgumentException::invalidId('position');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            '@type' => 'ListItem',
            'position' => $this->position,
            'name' => trim($this->name),
            'item' => trim($this->url),
        ];
    }
}
