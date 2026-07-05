<?php

declare(strict_types=1);

namespace Maatify\Seo\Shared\Infrastructure\Persistence;

use Maatify\Seo\Exception\SeoCodeAlreadyExistsException;
use Maatify\Seo\Shared\Command\CreateRedirectCommand;
use Maatify\Seo\Shared\Command\UpdateRedirectCommand;
use Maatify\Seo\Shared\Contract\RedirectRepositoryInterface;
use Maatify\Seo\Shared\DTO\RedirectDTO;
use PDO;

final readonly class PdoRedirectRepository implements RedirectRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function create(CreateRedirectCommand $command): int
    {
        try {
            $stmt = $this->pdo->prepare('INSERT INTO maa_seo_redirects (entity_type, language_id, requested_slug, target_entity_type, target_entity_id, http_status) VALUES (:entity_type, :language_id, :requested_slug, :target_entity_type, :target_entity_id, :http_status)');
            $stmt->bindValue('entity_type', $command->entityType);
            $stmt->bindValue('language_id', $command->languageId, PDO::PARAM_INT);
            $stmt->bindValue('requested_slug', $command->requestedSlug);
            $stmt->bindValue('target_entity_type', $command->targetEntityType);
            $stmt->bindValue('target_entity_id', $command->targetEntityId);
            $stmt->bindValue('http_status', $command->httpStatus, PDO::PARAM_INT);
            $stmt->execute();
        } catch (\PDOException $e) {
            if (str_starts_with((string) $e->getCode(), '23')) {
                throw SeoCodeAlreadyExistsException::forUniqueKey($command->entityType . ':' . (string) $command->languageId . ':' . $command->requestedSlug);
            }
            throw $e;
        }
        return (int) $this->pdo->lastInsertId();
    }

    public function update(UpdateRedirectCommand $command): bool
    {
        $stmt = $this->pdo->prepare('UPDATE maa_seo_redirects SET target_entity_type = :target_entity_type, target_entity_id = :target_entity_id, http_status = :http_status WHERE id = :id AND deleted_at IS NULL');
        $stmt->bindValue('target_entity_type', $command->targetEntityType);
        $stmt->bindValue('target_entity_id', $command->targetEntityId);
        $stmt->bindValue('http_status', $command->httpStatus, PDO::PARAM_INT);
        $stmt->bindValue('id', $command->id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function findById(int $id): ?RedirectDTO
    {
        $stmt = $this->pdo->prepare('SELECT * FROM maa_seo_redirects WHERE id = :id');
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->execute();
        /** @var array<string, mixed>|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : self::hydrate($row);
    }

    public function findActiveByRequestedSlug(string $entityType, int $languageId, string $requestedSlug): ?RedirectDTO
    {
        $stmt = $this->pdo->prepare('SELECT * FROM maa_seo_redirects WHERE entity_type = :entity_type AND language_id = :language_id AND requested_slug = :requested_slug AND deleted_at IS NULL LIMIT 1');
        $stmt->bindValue('entity_type', $entityType);
        $stmt->bindValue('language_id', $languageId, PDO::PARAM_INT);
        $stmt->bindValue('requested_slug', $requestedSlug);
        $stmt->execute();
        /** @var array<string, mixed>|false $row */
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : self::hydrate($row);
    }


    /** @return list<RedirectDTO> */
    public function findByEntity(string $entityType, ?int $languageId = null, bool $includeDeleted = false): array
    {
        $sql = 'SELECT * FROM maa_seo_redirects WHERE entity_type = :entity_type';
        if ($languageId !== null) {
            $sql .= ' AND language_id = :language_id';
        }
        if (! $includeDeleted) {
            $sql .= ' AND deleted_at IS NULL';
        }
        $sql .= ' ORDER BY id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue('entity_type', $entityType);
        if ($languageId !== null) {
            $stmt->bindValue('language_id', $languageId, PDO::PARAM_INT);
        }
        $stmt->execute();

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(static fn (array $row): RedirectDTO => self::hydrate($row), $rows);
    }

    public function softDelete(int $id): bool
    {
        $stmt = $this->pdo->prepare('UPDATE maa_seo_redirects SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id AND deleted_at IS NULL');
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function hardDelete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM maa_seo_redirects WHERE id = :id');
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /** @param array<string, mixed> $row */
    private static function hydrate(array $row): RedirectDTO
    {
        return new RedirectDTO(self::intValue($row, 'id'), self::stringValue($row, 'entity_type'), self::intValue($row, 'language_id'), self::stringValue($row, 'requested_slug'), self::nullableStringValue($row, 'target_entity_type'), self::nullableStringValue($row, 'target_entity_id'), self::intValue($row, 'http_status'), self::stringValue($row, 'created_at'), self::nullableStringValue($row, 'deleted_at'));
    }

    /** @param array<string, mixed> $row */
    private static function stringValue(array $row, string $key): string { $value = $row[$key] ?? null; return is_string($value) ? $value : (is_int($value) ? (string) $value : ''); }
    /** @param array<string, mixed> $row */
    private static function nullableStringValue(array $row, string $key): ?string { $value = $row[$key] ?? null; return is_string($value) ? $value : null; }
    /** @param array<string, mixed> $row */
    private static function intValue(array $row, string $key): int { $value = $row[$key] ?? null; return is_int($value) ? $value : (is_string($value) && ctype_digit($value) ? (int) $value : 0); }
}
