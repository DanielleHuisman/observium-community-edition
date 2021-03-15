ALTER TABLE `alert_tests` CHANGE `severity` `severity` ENUM('crit','warn','info') CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL DEFAULT 'crit';
