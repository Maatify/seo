<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\Infrastructure\Persistence;

use Maatify\Seo\Shared\Command\SeoOverride\CreateSeoOverrideCommand;
use Maatify\Seo\Shared\Command\SeoOverride\UpdateSeoOverrideCommand;
use Maatify\Seo\Shared\Contract\SeoOverrideRepositoryInterface;
use Maatify\Seo\Shared\DTO\SeoOverride\SeoOverrideDTO;
use Maatify\Seo\Exception\SeoCodeAlreadyExistsException;
use PDO;

final readonly class PdoSeoOverrideRepository implements SeoOverrideRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function create(CreateSeoOverrideCommand $command): int
    {
        try {
            $stmt = $this->pdo->prepare('INSERT INTO maa_seo_overrides (entity_type, entity_id, language_id, meta_title, meta_description) VALUES (:entity_type, :entity_id, :language_id, :meta_title, :meta_description)');
            $stmt->bindValue('entity_type', $command->entityType);
            $stmt->bindValue('entity_id', $command->entityId);
            $stmt->bindValue('language_id', $command->languageId, PDO::PARAM_INT);
            $stmt->bindValue('meta_title', $command->metaTitle);
            $stmt->bindValue('meta_description', $command->metaDescription);
            $stmt->execute();
        } catch (\PDOException $e) {
            if (str_starts_with((string) $e->getCode(), '23')) {
                throw SeoCodeAlreadyExistsException::forUniqueKey($command->entityType . ':' . $command->entityId . ':' . (string) $command->languageId);
            }
            throw $e;
        }
        return (int) $this->pdo->lastInsertId();
    }

    public function update(UpdateSeoOverrideCommand $command): bool
    {
        $stmt = $this->pdo->prepare('UPDATE maa_seo_overrides SET meta_title = :meta_title, meta_description = :meta_description WHERE id = :id AND deleted_at IS NULL');
        $stmt->bindValue('meta_title', $command->metaTitle);
        $stmt->bindValue('meta_description', $command->metaDescription);
        $stmt->bindValue('id', $command->id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function findById(int $id): ?SeoOverrideDTO
    {
        $stmt = $this->pdo->prepare('SELECT * FROM maa_seo_overrides WHERE id = :id');
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->execute();
        /** @var array<string, mixed>|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : self::hydrate($row);
    }

    public function findActiveForEntity(string $entityType, string $entityId, int $languageId): ?SeoOverrideDTO
    {
        $stmt = $this->pdo->prepare('SELECT * FROM maa_seo_overrides WHERE entity_type = :entity_type AND entity_id = :entity_id AND language_id = :language_id AND deleted_at IS NULL LIMIT 1');
        $stmt->bindValue('entity_type', $entityType);
        $stmt->bindValue('entity_id', $entityId);
        $stmt->bindValue('language_id', $languageId, PDO::PARAM_INT);
        $stmt->execute();
        /** @var array<string, mixed>|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : self::hydrate($row);
    }


    /** @return list<SeoOverrideDTO> */
    public function findByEntity(string $entityType, string $entityId, ?int $languageId = null, bool $includeDeleted = false): array
    {
        $sql = 'SELECT * FROM maa_seo_overrides WHERE entity_type = :entity_type AND entity_id = :entity_id';
        if ($languageId !== null) {
            $sql .= ' AND language_id = :language_id';
        }
        if (! $includeDeleted) {
            $sql .= ' AND deleted_at IS NULL';
        }
        $sql .= ' ORDER BY id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue('entity_type', $entityType);
        $stmt->bindValue('entity_id', $entityId);
        if ($languageId !== null) {
            $stmt->bindValue('language_id', $languageId, PDO::PARAM_INT);
        }
        $stmt->execute();

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(static fn (array $row): SeoOverrideDTO => self::hydrate($row), $rows);
    }

    public function softDelete(int $id): bool
    {
        $stmt = $this->pdo->prepare('UPDATE maa_seo_overrides SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id AND deleted_at IS NULL');
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function hardDelete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM maa_seo_overrides WHERE id = :id');
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /** @param array<string, mixed> $row */
    private static function hydrate(array $row): SeoOverrideDTO
    {
        return new SeoOverrideDTO(self::intValue($row, 'id'), self::stringValue($row, 'entity_type'), self::stringValue($row, 'entity_id'), self::intValue($row, 'language_id'), self::nullableStringValue($row, 'meta_title'), self::nullableStringValue($row, 'meta_description'), self::stringValue($row, 'created_at'), self::stringValue($row, 'updated_at'), self::nullableStringValue($row, 'deleted_at'));
    }

    /** @param array<string, mixed> $row */
    private static function stringValue(array $row, string $key): string { $value = $row[$key] ?? null; return is_string($value) ? $value : (is_int($value) ? (string) $value : ''); }
    /** @param array<string, mixed> $row */
    private static function nullableStringValue(array $row, string $key): ?string { $value = $row[$key] ?? null; return is_string($value) ? $value : null; }
    /** @param array<string, mixed> $row */
    private static function intValue(array $row, string $key): int { $value = $row[$key] ?? null; return is_int($value) ? $value : (is_string($value) && ctype_digit($value) ? (int) $value : 0); }
}
