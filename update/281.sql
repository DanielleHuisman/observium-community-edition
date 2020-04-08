ALTER TABLE  `bill_ports` ADD  `bill_ent_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST ;
ALTER TABLE  `bill_ports` ADD  `entity_type` VARCHAR( 20 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT  'port';
ALTER TABLE  `bill_ports` CHANGE  `port_id`  `entity_id` INT( 11 ) NOT NULL ;
RENAME TABLE  `bill_ports` TO  `bill_entities` ;

