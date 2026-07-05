<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\SeoRender\Command;

use Maatify\Seo\Exception\SeoInvalidArgumentException;
use Maatify\Seo\Shared\DTO\Schema\BreadcrumbListDTO;

final readonly class RenderSeoPageCommand
{
    /**
     * @param array<mixed> $schemas
     */
    public function __construct(
        public string $entityType,
        public string $entityId,
        public int $languageId,
        public string $defaultTitle,
        public ?string $defaultDescription,
        public ?string $slug = null,
        public ?string $canonicalUrl = null,
        public string $robots = 'index,follow',
        public array $schemas = [],
        public ?BreadcrumbListDTO $breadcrumbs = null,
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
        if (trim($this->defaultTitle) === '') {
            throw SeoInvalidArgumentException::emptyField('defaultTitle');
        }
        if (trim($this->robots) === '') {
            throw SeoInvalidArgumentException::emptyField('robots');
        }
        if ($this->slug !== null && trim($this->slug) === '') {
            throw SeoInvalidArgumentException::emptyField('slug');
        }
        if ($this->canonicalUrl !== null && trim($this->canonicalUrl) === '') {
            throw SeoInvalidArgumentException::emptyField('canonicalUrl');
        }

        foreach ($this->schemas as $schema) {
            if (!$schema instanceof \JsonSerializable) {
                throw SeoInvalidArgumentException::emptyField('schemas');
            }
        }
    }
}
