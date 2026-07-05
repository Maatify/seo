-- -----------------------------------------------------------------------------
-- Table: maa_seo_slug_history
-- Purpose: Records old slugs when an entity's slug changes, to prevent reuse
--          and facilitate automatic redirects.
-- Soft Delete Policy: deleted_at DATETIME NULL COMMENT 'NULL = active, NOT NULL = soft-deleted.'
-- FK Policy: No foreign keys to host tables (entity_id, language_id).
-- Uniqueness Policy: Unique across (entity_type, entity_id, language_id, old_slug).
-- Lifecycle Policy: Kept indefinitely to prevent old slug reuse, unless the entity
--                   is permanently deleted by the host application.
-- -----------------------------------------------------------------------------

CREATE TABLE `maa_seo_slug_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `entity_type` VARCHAR(50) NOT NULL COMMENT 'Host-defined entity type. No FK.',
  `entity_id` VARCHAR(36) NOT NULL COMMENT 'Host-provided ID. No FK.',
  `language_id` INT UNSIGNED NOT NULL COMMENT 'Host-provided ID. No FK.',
  `old_slug` VARCHAR(255) NOT NULL COMMENT 'The old slug of the entity',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation timestamp',
  `deleted_at` DATETIME NULL COMMENT 'NULL = active, NOT NULL = soft-deleted.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_maa_seo_sh_unique` (`entity_type`, `entity_id`, `language_id`, `old_slug`),
  KEY `idx_maa_seo_sh_lookup` (`entity_type`, `language_id`, `old_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
