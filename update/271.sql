ALTER TABLE `toner` CHANGE `toner_id` `supply_id` INT(11) NOT NULL auto_increment
ALTER TABLE `toner` CHANGE `toner_index` `supply_index` VARCHAR(64) NOT NULL
ALTER TABLE `toner` CHANGE `toner_type` `supply_mib` VARCHAR(64) NOT NULL
ALTER TABLE `toner` CHANGE `toner_oid` `supply_oid` VARCHAR(64) NOT NULL
ALTER TABLE `toner` CHANGE `toner_descr` `supply_descr` VARCHAR(64) NOT NULL
ALTER TABLE `toner` CHANGE `toner_current` `supply_value` INT(11) NOT NULL
ALTER TABLE `toner` CHANGE `toner_capacity` `supply_capacity` INT(11) NOT NULL
ALTER TABLE `toner` CHANGE `toner_capacity_oid` `supply_capacity_oid` VARCHAR(64) NOT NULL
ALTER TABLE `toner` ADD `supply_type` VARCHAR(64) NOT NULL AFTER `device_id`
ALTER TABLE `toner` ADD `supply_colour` VARCHAR(10) NOT NULL AFTER `supply_descr`
RENAME TABLE `toner` TO `printersupplies`
UPDATE `alert_tests` SET `entity_type`='printersupply', `conditions`=REPLACE(conditions, 'toner_', 'supply_') WHERE entity_type='toner'
UPDATE `alert_assoc` SET `entity_type`='printersupply', `entity_attribs`=REPLACE(entity_attribs, 'toner_', 'supply_') WHERE entity_type='toner'
