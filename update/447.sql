ALTER TABLE `status` CHANGE `measured_entity` `measured_entity` INT UNSIGNED NULL DEFAULT NULL;
UPDATE `status` SET `measured_entity`=NULL WHERE `measured_entity` = 0;
ALTER TABLE `status` ADD `measured_entity_label` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `measured_entity`;
ALTER TABLE `sensors` CHANGE `measured_entity` `measured_entity` INT UNSIGNED NULL DEFAULT NULL;
UPDATE `sensors` SET `measured_entity`=NULL WHERE `measured_entity` = 0;
ALTER TABLE `sensors` ADD `measured_entity_label` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `measured_entity`;
ALTER TABLE `counters` CHANGE `measured_entity` `measured_entity` INT UNSIGNED NULL DEFAULT NULL;
UPDATE `counters` SET `measured_entity`=NULL WHERE `measured_entity` = 0;
ALTER TABLE `counters` ADD `measured_entity_label` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `measured_entity`;
