<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\DTO;

final readonly class SlugHistoryDTO implements \JsonSerializable
{
    public function __construct(
        public int $id,
        public string $entityType,
        public string $entityId,
        public int $languageId,
        public string $oldSlug,
        public string $createdAt,
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
            'old_slug' => $this->oldSlug,
            'created_at' => $this->createdAt,
            'deleted_at' => $this->deletedAt,
        ];
    }
}
