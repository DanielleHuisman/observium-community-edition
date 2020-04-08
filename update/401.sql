ALTER TABLE `devices` ADD `vendor` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL COMMENT 'Hardware vendor' AFTER `hardware`;
