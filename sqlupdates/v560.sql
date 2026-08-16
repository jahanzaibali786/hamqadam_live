ALTER TABLE `astrologies` 
ADD `nakshatra` VARCHAR(255) NULL AFTER `city_of_birth`,
ADD `gana` VARCHAR(255) NULL AFTER `nakshatra`,
ADD `nadi` VARCHAR(255) NULL AFTER `gana`,
ADD `manglik` VARCHAR(255) NULL AFTER `nadi`;

ALTER TABLE `members` 
ADD `auto_horoscope_profile_match` TINYINT(1) DEFAULT 0 AFTER `auto_profile_match`;

ALTER TABLE `users` 
ADD `refresh_updated_at` timestamp NULL AFTER `email`,
ADD `match_refresh_updated_at` timestamp NULL AFTER `refresh_updated_at`,
ADD `access_token` LONGTEXT NULL AFTER `match_refresh_updated_at`,
ADD `has_purchased_free_package` int(2) DEFAULT 0 AFTER `access_token`;

UPDATE `users` 
SET `has_purchased_free_package` = 1;

ALTER TABLE `packages` 
ADD `auto_horoscope_profile_match` TINYINT(1) DEFAULT 0 AFTER `auto_profile_match`;

CREATE TABLE `horoscope_profile_matches` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` bigint(20) NOT NULL,
  `match_id` bigint(20) NOT NULL,
  `match_count` int(4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

UPDATE `settings` SET `value` = '5.6.0' WHERE `settings`.`type` = 'current_version';
COMMIT;
