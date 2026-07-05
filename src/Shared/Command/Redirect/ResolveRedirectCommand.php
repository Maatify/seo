<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\Command\Redirect;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class ResolveRedirectCommand
{
    public function __construct(
        public string $entityType,
        public int $languageId,
        public string $requestedSlug,
        public ?string $requestedPath = null,
    ) {
        if (trim($this->entityType) === '') {
            throw SeoInvalidArgumentException::emptyField('entityType');
        }
        if ($this->languageId < 1) {
            throw SeoInvalidArgumentException::invalidId('languageId');
        }
        if (trim($this->requestedSlug) === '') {
            throw SeoInvalidArgumentException::emptyField('requestedSlug');
        }
        if ($this->requestedPath !== null && trim($this->requestedPath) === '') {
            throw SeoInvalidArgumentException::emptyField('requestedPath');
        }
    }
}
