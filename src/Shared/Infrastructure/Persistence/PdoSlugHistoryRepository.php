<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\Infrastructure\Persistence;

use Maatify\Seo\Exception\SeoCodeAlreadyExistsException;
use Maatify\Seo\Shared\Command\CreateSlugHistoryCommand;
use Maatify\Seo\Shared\Contract\SlugHistoryRepositoryInterface;
use Maatify\Seo\Shared\DTO\SlugHistoryDTO;
use PDO;

final readonly class PdoSlugHistoryRepository implements SlugHistoryRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function create(CreateSlugHistoryCommand $command): int
    {
        try {
            $stmt = $this->pdo->prepare('INSERT INTO maa_seo_slug_history (entity_type, entity_id, language_id, old_slug) VALUES (:entity_type, :entity_id, :language_id, :old_slug)');
            $stmt->execute([
                'entity_type' => $command->entityType,
                'entity_id' => $command->entityId,
                'language_id' => $command->languageId,
                'old_slug' => $command->oldSlug,
            ]);
        } catch (\PDOException $e) {
            if (str_starts_with((string) $e->getCode(), '23')) {
                throw SeoCodeAlreadyExistsException::forUniqueKey($command->entityType . ':' . $command->entityId . ':' . (string) $command->languageId . ':' . $command->oldSlug);
            }
            throw $e;
        }

        return (int) $this->pdo->lastInsertId();
    }

    public function findById(int $id): ?SlugHistoryDTO
    {
        $stmt = $this->pdo->prepare('SELECT * FROM maa_seo_slug_history WHERE id = :id');
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->execute();
        /** @var array<string, mixed>|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : self::hydrate($row);
    }

    public function findActiveBySlug(string $entityType, int $languageId, string $oldSlug): ?SlugHistoryDTO
    {
        $stmt = $this->pdo->prepare('SELECT * FROM maa_seo_slug_history WHERE entity_type = :entity_type AND language_id = :language_id AND old_slug = :old_slug AND deleted_at IS NULL LIMIT 1');
        $stmt->bindValue('entity_type', $entityType);
        $stmt->bindValue('language_id', $languageId, PDO::PARAM_INT);
        $stmt->bindValue('old_slug', $oldSlug);
        $stmt->execute();
        /** @var array<string, mixed>|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : self::hydrate($row);
    }

    public function findActiveForEntity(string $entityType, string $entityId, int $languageId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM maa_seo_slug_history WHERE entity_type = :entity_type AND entity_id = :entity_id AND language_id = :language_id AND deleted_at IS NULL ORDER BY id DESC');
        $stmt->bindValue('entity_type', $entityType);
        $stmt->bindValue('entity_id', $entityId);
        $stmt->bindValue('language_id', $languageId, PDO::PARAM_INT);
        $stmt->execute();
        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(static fn (array $row): SlugHistoryDTO => self::hydrate($row), $rows);
    }

    public function softDelete(int $id): bool
    {
        $stmt = $this->pdo->prepare('UPDATE maa_seo_slug_history SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id AND deleted_at IS NULL');
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function hardDelete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM maa_seo_slug_history WHERE id = :id');
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /** @param array<string, mixed> $row */
    private static function hydrate(array $row): SlugHistoryDTO
    {
        return new SlugHistoryDTO(
            self::intValue($row, 'id'), self::stringValue($row, 'entity_type'), self::stringValue($row, 'entity_id'), self::intValue($row, 'language_id'), self::stringValue($row, 'old_slug'), self::stringValue($row, 'created_at'), self::nullableStringValue($row, 'deleted_at')
        );
    }

    /** @param array<string, mixed> $row */
    private static function stringValue(array $row, string $key): string { $value = $row[$key] ?? null; return is_string($value) ? $value : (is_int($value) ? (string) $value : ''); }
    /** @param array<string, mixed> $row */
    private static function nullableStringValue(array $row, string $key): ?string { $value = $row[$key] ?? null; return is_string($value) ? $value : null; }
    /** @param array<string, mixed> $row */
    private static function intValue(array $row, string $key): int { $value = $row[$key] ?? null; return is_int($value) ? $value : (is_string($value) && ctype_digit($value) ? (int) $value : 0); }
}
