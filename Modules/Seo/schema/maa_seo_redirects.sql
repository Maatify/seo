-- -----------------------------------------------------------------------------
-- Table: maa_seo_redirects
-- Purpose: Fast lookup table for routing layer to find if a requested URL/slug
--          should 301 to a new one, or 410 (Gone).
-- Soft Delete Policy: deleted_at DATETIME NULL COMMENT 'NULL = active, NOT NULL = soft-deleted.'
-- FK Policy: No foreign keys to host tables (language_id, target_entity_id).
-- Uniqueness Policy: Unique across (entity_type, language_id, requested_slug).
-- Lifecycle Policy: Redirects are kept to handle legacy URLs. May be purged if
--                   the target entity is permanently deleted or replaced.
-- -----------------------------------------------------------------------------

CREATE TABLE `maa_seo_redirects` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `entity_type` VARCHAR(50) NOT NULL COMMENT 'Host-defined entity type. No FK.',
  `language_id` INT UNSIGNED NOT NULL COMMENT 'Host-provided ID. No FK.',
  `requested_slug` VARCHAR(255) NOT NULL COMMENT 'The old or requested slug/path to redirect from',
  `target_entity_type` VARCHAR(50) DEFAULT NULL COMMENT 'Host-defined entity type. No FK. Nullable if 410 Gone.',
  `target_entity_id` VARCHAR(36) DEFAULT NULL COMMENT 'Host-provided ID. No FK. Nullable if 410 Gone.',
  `http_status` SMALLINT UNSIGNED NOT NULL DEFAULT 301 COMMENT '301 for permanent redirect, 410 for gone',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Creation timestamp',
  `deleted_at` DATETIME DEFAULT NULL COMMENT 'NULL = active, NOT NULL = soft-deleted.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_maa_seo_redir_unique` (`entity_type`, `language_id`, `requested_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
