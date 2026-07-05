<?php

declare(strict_types=1);

namespace Maatify\Seo\Admin\Redirect\DTO;

use Maatify\Seo\Shared\DTO\RedirectDTO;

final readonly class AdminRedirectDTO implements \JsonSerializable
{
    public function __construct(public int $id, public string $entityType, public int $languageId, public string $requestedSlug, public ?string $targetEntityType, public ?string $targetEntityId, public int $httpStatus, public bool $isDeleted, public string $createdAt, public ?string $deletedAt) {}
    public static function fromShared(RedirectDTO $dto): self { return new self($dto->id, $dto->entityType, $dto->languageId, $dto->requestedSlug, $dto->targetEntityType, $dto->targetEntityId, $dto->httpStatus, $dto->deletedAt !== null, $dto->createdAt, $dto->deletedAt); }
    /** @return array<string, mixed> */
    public function jsonSerialize(): array { return ['id'=>$this->id,'entity_type'=>$this->entityType,'language_id'=>$this->languageId,'requested_slug'=>$this->requestedSlug,'target_entity_type'=>$this->targetEntityType,'target_entity_id'=>$this->targetEntityId,'http_status'=>$this->httpStatus,'is_deleted'=>$this->isDeleted,'created_at'=>$this->createdAt,'deleted_at'=>$this->deletedAt]; }
}
