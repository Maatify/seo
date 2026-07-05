<?php

declare(strict_types=1);

namespace Maatify\Seo\Admin\Redirect\Command;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class CreateAdminRedirectCommand
{
    public function __construct(public string $entityType, public int $languageId, public string $requestedSlug, public ?string $targetEntityType, public ?string $targetEntityId, public int $httpStatus = 301)
    {
        if (trim($this->entityType) === '') throw SeoInvalidArgumentException::emptyField('entityType');
        if ($this->languageId < 1) throw SeoInvalidArgumentException::invalidId('languageId');
        if (trim($this->requestedSlug) === '') throw SeoInvalidArgumentException::emptyField('requestedSlug');
        if ($this->httpStatus !== 301 && $this->httpStatus !== 410) throw SeoInvalidArgumentException::invalidHttpStatus($this->httpStatus);
        if ($this->httpStatus === 301 && ($this->targetEntityType === null || trim($this->targetEntityType) === '')) throw SeoInvalidArgumentException::emptyField('targetEntityType');
        if ($this->httpStatus === 301 && ($this->targetEntityId === null || trim($this->targetEntityId) === '')) throw SeoInvalidArgumentException::emptyField('targetEntityId');
    }
}
