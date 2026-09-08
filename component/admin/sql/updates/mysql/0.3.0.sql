CREATE TABLE IF NOT EXISTS `#__xdecaronotifications_preferences` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `recipient_type` VARCHAR(32) NOT NULL,
  `recipient_id` VARCHAR(128) NOT NULL,
  `category` VARCHAR(64) NOT NULL DEFAULT '*',
  `channel` VARCHAR(64) NOT NULL,
  `enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `created` DATETIME NOT NULL,
  `created_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `modified` DATETIME NOT NULL,
  `modified_by` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_notifications_preference` (`recipient_type`, `recipient_id`, `category`, `channel`),
  KEY `idx_notifications_preference_channel` (`channel`),
  KEY `idx_notifications_preference_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xdecaronotifications_deliveries` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `notification_id` BIGINT UNSIGNED NOT NULL,
  `channel` VARCHAR(64) NOT NULL,
  `state` VARCHAR(16) NOT NULL DEFAULT 'pending',
  `context` MEDIUMTEXT NULL,
  `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
  `available_at` DATETIME NOT NULL,
  `created` DATETIME NOT NULL,
  `updated` DATETIME NOT NULL,
  `delivered_at` DATETIME NULL,
  `provider_reference` VARCHAR(191) NULL,
  `last_error` VARCHAR(1000) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_notifications_delivery_channel` (`notification_id`, `channel`),
  KEY `idx_notifications_delivery_pending` (`state`, `available_at`),
  KEY `idx_notifications_delivery_notification` (`notification_id`),
  KEY `idx_notifications_delivery_channel` (`channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__xdecaronotifications_delivery_attempts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `delivery_id` BIGINT UNSIGNED NOT NULL,
  `provider` VARCHAR(64) NOT NULL,
  `state` VARCHAR(16) NOT NULL,
  `provider_reference` VARCHAR(191) NULL,
  `error_code` VARCHAR(64) NULL,
  `error_message` VARCHAR(1000) NULL,
  `created` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_attempt_delivery` (`delivery_id`, `created`),
  KEY `idx_notifications_attempt_state` (`state`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
