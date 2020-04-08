ALTER TABLE `ports` CHANGE `ifInErrors_rate` `ifInErrors_rate` FLOAT UNSIGNED NOT NULL DEFAULT '0', CHANGE `ifOutErrors_rate` `ifOutErrors_rate` FLOAT UNSIGNED NOT NULL DEFAULT '0';
