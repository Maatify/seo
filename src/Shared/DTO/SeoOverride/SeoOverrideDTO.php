<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\DTO\SeoOverride;

final readonly class SeoOverrideDTO implements \JsonSerializable
{
    public function __construct(
        public int $id,
        public string $entityType,
        public string $entityId,
        public int $languageId,
        public ?string $metaTitle,
        public ?string $metaDescription,
        public string $createdAt,
        public string $updatedAt,
        public ?string $deletedAt,
    ) {
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'language_id' => $this->languageId,
            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->deletedAt,
        ];
    }
}
