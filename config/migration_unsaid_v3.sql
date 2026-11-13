CREATE TABLE IF NOT EXISTS `profile_views` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `profile_id` int(11) NOT NULL,
  `visitor_hash` char(32) NOT NULL,
  `viewed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pv_profile_time` (`profile_id`,`viewed_at`),
  KEY `idx_pv_profile_hash` (`profile_id`,`visitor_hash`),
  CONSTRAINT `fk_pv_profile` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `profiles`
  ADD COLUMN IF NOT EXISTS `success_message` varchar(150) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `success_effect` varchar(20) NOT NULL DEFAULT 'confirm';

ALTER TABLE `messages`
  ADD COLUMN IF NOT EXISTS `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  ADD INDEX IF NOT EXISTS `idx_messages_profile_archived` (`profile_id`,`is_archived`,`created_at`);
