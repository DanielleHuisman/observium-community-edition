ALTER TABLE `alerts_maint` ADD COLUMN `maint_interval` ENUM('daily', 'weekly', 'monthly') COLLATE utf8_unicode_ci DEFAULT NULL;
ALTER TABLE `alerts_maint` ADD COLUMN `maint_interval_count` int NOT NULL DEFAULT '1';
