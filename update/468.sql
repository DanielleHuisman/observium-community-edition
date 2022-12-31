ALTER TABLE `users` CHANGE `type` `type` VARCHAR(16) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL DEFAULT 'mysql';
ALTER TABLE `users` DROP INDEX `username`, ADD UNIQUE `username` (`username`, `type`) USING BTREE;
