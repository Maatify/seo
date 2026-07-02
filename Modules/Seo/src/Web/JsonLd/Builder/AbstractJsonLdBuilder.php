<?php

declare(strict_types=1);

namespace Maatify\Seo\Web\JsonLd\Builder;

abstract class AbstractJsonLdBuilder implements JsonLdBuilderInterface
{
    use JsonLdBuilderTrait;

    /** @param array<string, mixed> $schema */
    public function __construct(array $schema = [])
    {
        $this->schema = $schema;
    }
}
