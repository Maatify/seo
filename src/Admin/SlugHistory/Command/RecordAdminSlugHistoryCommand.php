<?php

declare(strict_types=1);

namespace Maatify\Seo\Admin\SlugHistory\Command;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class RecordAdminSlugHistoryCommand
{
    public function __construct(public string $entityType, public string $entityId, public int $languageId, public string $oldSlug)
    {
        if (trim($this->entityType) === '') throw SeoInvalidArgumentException::emptyField('entityType');
        if (trim($this->entityId) === '') throw SeoInvalidArgumentException::emptyField('entityId');
        if ($this->languageId < 1) throw SeoInvalidArgumentException::invalidId('languageId');
        if (trim($this->oldSlug) === '') throw SeoInvalidArgumentException::emptyField('oldSlug');
    }
}
