<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\Command\SeoOverride;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class UpdateSeoOverrideCommand
{
    public function __construct(
        public int $id,
        public ?string $metaTitle,
        public ?string $metaDescription,
    ) {
        if ($this->id < 1) {
            throw SeoInvalidArgumentException::invalidId('id');
        }
    }
}
