<?php

declare(strict_types=1);

namespace Maatify\Seo\Admin\SlugHistory\DTO;

use Maatify\Seo\Shared\DTO\SlugHistoryDTO;

final readonly class AdminSlugHistoryDTO implements \JsonSerializable
{
    public function __construct(public int $id, public string $entityType, public string $entityId, public int $languageId, public string $oldSlug, public bool $isDeleted, public string $createdAt, public ?string $deletedAt) {}
    public static function fromShared(SlugHistoryDTO $dto): self { return new self($dto->id, $dto->entityType, $dto->entityId, $dto->languageId, $dto->oldSlug, $dto->deletedAt !== null, $dto->createdAt, $dto->deletedAt); }
    /** @return array<string, mixed> */
    public function jsonSerialize(): array { return ['id'=>$this->id,'entity_type'=>$this->entityType,'entity_id'=>$this->entityId,'language_id'=>$this->languageId,'old_slug'=>$this->oldSlug,'is_deleted'=>$this->isDeleted,'created_at'=>$this->createdAt,'deleted_at'=>$this->deletedAt]; }
}
