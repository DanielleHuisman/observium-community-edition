ALTER TABLE `ports_stack` ADD `port_stack_id` INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`port_stack_id`);
ALTER TABLE `ports_adsl` ADD `adsl_id` INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`adsl_id`);
ALTER TABLE `roles_users` ADD `role_user_id` INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`role_user_id`);
ALTER TABLE `ospf_areas` ADD `ospf_area_id` INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`ospf_area_id`);
ALTER TABLE `ospf_nbrs` ADD `ospf_nbrs_id` INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`ospf_nbrs_id`);
ALTER TABLE `ospf_ports` ADD `ospf_ports_id` INT NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`ospf_ports_id`);
ALTER TABLE `config` CHANGE `config_key` `config_key` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL;
ALTER TABLE `config` DROP INDEX `config_key`, ADD PRIMARY KEY (`config_key`) USING BTREE;
ALTER TABLE `wifi_aps_members` CHANGE `wifi_ap_member_id` `wifi_ap_member_id` INT NOT NULL AUTO_INCREMENT, add PRIMARY KEY (`wifi_ap_member_id`);
