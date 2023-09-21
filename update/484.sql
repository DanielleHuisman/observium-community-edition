ALTER TABLE `vlans_fdb` DROP INDEX `device_id`, ADD INDEX `device` (`device_id`, `deleted`) USING BTREE;
