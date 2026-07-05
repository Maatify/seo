<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\Command;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class GenerateMetaTagsCommand
{
    public function __construct(
        public string $entityType,
        public string $entityId,
        public int $languageId,
        public string $defaultTitle,
        public ?string $defaultDescription,
        public ?string $slug = null,
        public ?string $canonicalUrl = null,
        public string $robots = 'index,follow',
    ) {
        if (trim($this->entityType) === '') throw SeoInvalidArgumentException::emptyField('entityType');
        if (trim($this->entityId) === '') throw SeoInvalidArgumentException::emptyField('entityId');
        if ($this->languageId < 1) throw SeoInvalidArgumentException::invalidId('languageId');
        if (trim($this->defaultTitle) === '') throw SeoInvalidArgumentException::emptyField('defaultTitle');
        if (trim($this->robots) === '') throw SeoInvalidArgumentException::emptyField('robots');
    }
}
