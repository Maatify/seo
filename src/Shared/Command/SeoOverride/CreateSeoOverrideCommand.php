<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\Command\SeoOverride;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class CreateSeoOverrideCommand
{
    public function __construct(
        public string $entityType,
        public string $entityId,
        public int $languageId,
        public ?string $metaTitle,
        public ?string $metaDescription,
    ) {
        if (trim($this->entityType) === '') {
            throw SeoInvalidArgumentException::emptyField('entityType');
        }
        if (trim($this->entityId) === '') {
            throw SeoInvalidArgumentException::emptyField('entityId');
        }
        if ($this->languageId < 1) {
            throw SeoInvalidArgumentException::invalidId('languageId');
        }
    }
}
