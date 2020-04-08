ALTER TABLE `ospf_instances` DROP `ospf_instance_id`;
ALTER TABLE `ospf_instances` ADD `ospf_instance_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST ;
