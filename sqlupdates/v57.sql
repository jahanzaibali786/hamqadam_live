-- HamQadam Help Center (real-time support chat)
-- Raw-SQL equivalent of database/migrations/2026_09_08_000001_create_help_chat_tables.php
-- for servers where migrations are not run (CyberPanel deployment via phpMyAdmin).

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `help_chat_threads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'open',
  `admin_unread_count` int(11) NOT NULL DEFAULT 0,
  `user_unread_count` int(11) NOT NULL DEFAULT 0,
  `last_message_id` bigint(20) unsigned DEFAULT NULL,
  `last_message_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `help_chat_threads_user_id_unique` (`user_id`),
  KEY `help_chat_threads_status_index` (`status`),
  KEY `help_chat_threads_last_message_at_index` (`last_message_at`),
  CONSTRAINT `help_chat_threads_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `help_chat_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `thread_id` bigint(20) unsigned NOT NULL,
  `sender_user_id` bigint(20) unsigned NOT NULL,
  `message` text,
  `message_type` varchar(20) NOT NULL DEFAULT 'text',
  `attachment` text,
  `seen` tinyint(4) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `help_chat_messages_thread_id_created_at_index` (`thread_id`, `created_at`),
  CONSTRAINT `help_chat_messages_thread_id_foreign` FOREIGN KEY (`thread_id`) REFERENCES `help_chat_threads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `help_chat_messages_sender_user_id_foreign` FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;

UPDATE `settings` SET `value` = '5.7' WHERE `settings`.`type` = 'current_version';
COMMIT;
