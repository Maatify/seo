<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\DTO;

final readonly class RedirectDTO implements \JsonSerializable
{
    public function __construct(
        public int $id,
        public string $entityType,
        public int $languageId,
        public string $requestedSlug,
        public ?string $targetEntityType,
        public ?string $targetEntityId,
        public int $httpStatus,
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
            'language_id' => $this->languageId,
            'requested_slug' => $this->requestedSlug,
            'target_entity_type' => $this->targetEntityType,
            'target_entity_id' => $this->targetEntityId,
            'http_status' => $this->httpStatus,
            'created_at' => $this->createdAt,
            'deleted_at' => $this->deletedAt,
        ];
    }
}
