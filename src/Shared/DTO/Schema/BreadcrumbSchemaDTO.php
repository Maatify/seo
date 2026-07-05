<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\DTO\Schema;

final readonly class BreadcrumbSchemaDTO implements \JsonSerializable
{
    public function __construct(
        public BreadcrumbListDTO $breadcrumbs,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->breadcrumbs->jsonSerialize();
    }
}
