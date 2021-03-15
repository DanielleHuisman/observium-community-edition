-- IGNORE_ERROR
ALTER TABLE `accesspoints-state` ENGINE = INNODB;
ALTER TABLE `applications-state` ENGINE = INNODB;
ALTER TABLE `applications-state` DROP INDEX `application_id`, ADD PRIMARY KEY (`application_id`) USING BTREE;
