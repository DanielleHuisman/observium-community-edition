ALTER TABLE `storage` ADD `storage_polled` INT NOT NULL AFTER `storage_ignore`, ADD `storage_size` BIGINT NOT NULL AFTER `storage_polled`, ADD `storage_units` INT NOT NULL AFTER `storage_size`, ADD `storage_used` BIGINT NOT NULL AFTER `storage_units`, ADD `storage_free` BIGINT NOT NULL AFTER `storage_used`, ADD `storage_perc` INT NOT NULL AFTER `storage_free`;
UPDATE `storage` p, `storage-state` s SET p.`storage_used` = s.`storage_used`,p.`storage_size` = s.`storage_size`,p.`storage_units` = s.`storage_units`,p.`storage_polled` = s.`storage_polled`, p.`storage_free` = s.`storage_free`, p.`storage_perc` = s.`storage_perc` WHERE p.`storage_id` = s.`storage_id`;
DROP TABLE `storage-state`;

