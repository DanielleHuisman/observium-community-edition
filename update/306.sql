ALTER TABLE `ospf_instances` ADD PRIMARY KEY (`ospf_instance_id`);
ALTER TABLE `ospf_instances` CHANGE `ospf_instance_id` `ospf_instance_id` INT(11) NOT NULL AUTO_INCREMENT FIRST;