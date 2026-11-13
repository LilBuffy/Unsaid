ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `tutorial_pending` tinyint(1) NOT NULL DEFAULT 0;

ALTER TABLE `reports`
  ADD COLUMN IF NOT EXISTS `report_type` enum('message','profile') NOT NULL DEFAULT 'message',
  ADD COLUMN IF NOT EXISTS `reason` varchar(30) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `details` varchar(500) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `reporter_hash` char(32) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `resolution` varchar(12) DEFAULT NULL,
  ADD INDEX IF NOT EXISTS `idx_reports_type_profile` (`report_type`,`profile_id`,`reporter_hash`);
