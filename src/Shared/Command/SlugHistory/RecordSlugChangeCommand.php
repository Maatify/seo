<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\Command\SlugHistory;

use Maatify\Seo\Exception\SeoConflictException;
use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class RecordSlugChangeCommand
{
    public function __construct(
        public string $entityType,
        public string $entityId,
        public int $languageId,
        public string $oldSlug,
        public string $newSlug,
        public bool $createRedirect = true,
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
        if (trim($this->oldSlug) === '') {
            throw SeoInvalidArgumentException::emptyField('oldSlug');
        }
        if (trim($this->newSlug) === '') {
            throw SeoInvalidArgumentException::emptyField('newSlug');
        }
        if (trim($this->oldSlug) === trim($this->newSlug)) {
            throw SeoConflictException::dueToReason('Old slug and new slug must be different.');
        }
    }
}
