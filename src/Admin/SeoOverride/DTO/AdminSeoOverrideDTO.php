<?php

declare(strict_types=1);

namespace Maatify\Seo\Admin\SeoOverride\DTO;

use Maatify\Seo\Shared\DTO\SeoOverride\SeoOverrideDTO;

final readonly class AdminSeoOverrideDTO implements \JsonSerializable
{
    public function __construct(
        public int $id,
        public string $entityType,
        public string $entityId,
        public int $languageId,
        public ?string $metaTitle,
        public ?string $metaDescription,
        public bool $isDeleted,
        public string $createdAt,
        public string $updatedAt,
        public ?string $deletedAt,
    ) {
    }

    public static function fromShared(SeoOverrideDTO $dto): self
    {
        return new self($dto->id, $dto->entityType, $dto->entityId, $dto->languageId, $dto->metaTitle, $dto->metaDescription, $dto->deletedAt !== null, $dto->createdAt, $dto->updatedAt, $dto->deletedAt);
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
            'is_deleted' => $this->isDeleted,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->deletedAt,
        ];
    }
}
