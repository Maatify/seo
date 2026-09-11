<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\Validation\Input\Sitemap;

use Maatify\Seo\Exception\SeoInvalidArgumentException;

final readonly class SitemapValidationLocationDTO
{
    public function __construct(
        public string $scheme,
        public string $host,
        public ?int $port,
        public string $path,
    ) {
        if ($this->scheme === '') {
            throw SeoInvalidArgumentException::emptyField('scheme');
        }

        if ($this->host === '') {
            throw SeoInvalidArgumentException::emptyField('host');
        }

        if ($this->port !== null && $this->port <= 0) {
            throw SeoInvalidArgumentException::invalidValue('port', 'Expected null or a positive integer.');
        }

        if (!str_starts_with($this->path, '/')) {
            throw SeoInvalidArgumentException::invalidValue('path', 'Expected a path beginning with /.');
        }
    }
}
