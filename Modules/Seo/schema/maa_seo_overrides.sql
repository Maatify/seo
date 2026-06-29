-- -----------------------------------------------------------------------------
-- Table: maa_seo_overrides
-- Purpose: Allows marketers/admins to manually override generated Meta Title
--          and Description per entity without polluting the host's primary tables.
-- Soft Delete Policy: deleted_at DATETIME NULL COMMENT 'NULL = active, NOT NULL = soft-deleted.'
-- FK Policy: No foreign keys to host tables (entity_id, language_id).
-- Uniqueness Policy: Unique across (entity_type, entity_id, language_id).
-- -----------------------------------------------------------------------------

CREATE TABLE `maa_seo_overrides` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `entity_type` VARCHAR(50) NOT NULL COMMENT 'Host-defined entity type. No FK.',
  `entity_id` VARCHAR(36) NOT NULL COMMENT 'Host-provided ID. No FK.',
  `language_id` INT UNSIGNED NOT NULL COMMENT 'Host-provided ID. No FK.',
  `meta_title` VARCHAR(255) DEFAULT NULL COMMENT 'Manually overridden meta title',
  `meta_description` TEXT DEFAULT NULL COMMENT 'Manually overridden meta description',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation timestamp',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp',
  `deleted_at` DATETIME DEFAULT NULL COMMENT 'NULL = active, NOT NULL = soft-deleted.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_maa_seo_override_unique` (`entity_type`, `entity_id`, `language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
