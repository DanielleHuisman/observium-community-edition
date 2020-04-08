ALTER TABLE `counters` ADD `counter_limit_by` ENUM('sec','min','5min','hour','value') CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL DEFAULT '5min' AFTER `counter_limit_low_warn`;
ALTER TABLE `counters` ADD `counter_rate_5min` FLOAT(14,5) NULL DEFAULT NULL AFTER `counter_rate`;
