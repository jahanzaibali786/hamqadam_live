-- Help Center ticket lock — raw-SQL twin of
-- database/migrations/2026_09_26_000001_add_lock_columns_to_help_chat_threads.php

ALTER TABLE `help_chat_threads`
  ADD COLUMN `admin_replied_at` TIMESTAMP NULL AFTER `last_message_at`,
  ADD COLUMN `member_replied_at` TIMESTAMP NULL AFTER `admin_replied_at`,
  ADD COLUMN `locked_at` TIMESTAMP NULL AFTER `member_replied_at`;

-- "Start New chat" needs a second thread row for the same member.
ALTER TABLE `help_chat_threads` DROP INDEX `help_chat_threads_user_id_unique`;
ALTER TABLE `help_chat_threads` ADD INDEX `help_chat_threads_user_id_index` (`user_id`);
