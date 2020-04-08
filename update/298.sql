ALTER TABLE  `ports` CHANGE  `port_descr_type`  `port_descr_type` VARCHAR( 16 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ;
ALTER TABLE  `ports` CHANGE  `ifAdminStatus`  `ifAdminStatus` ENUM(  'down',  'up', 'testing' ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ;
ALTER TABLE  `ports` CHANGE  `ifOperStatus` `ifOperStatus` ENUM('testing', 'notPresent', 'dormant','down','lowerLayerDown','unknown','up') CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ;
ALTER TABLE  `ports` ADD  `ifInErrors_delta` INT UNSIGNED NOT NULL ,ADD  `ifOutErrors_delta` INT UNSIGNED NOT NULL;
ALTER TABLE `lb_pool_members` CHANGE `member_state` `member_state` varchar(8) COLLATE utf8_unicode_ci NOT NULL, CHANGE `member_enabled` `member_enabled` varchar (16) COLLATE utf8_unicode_ci NOT NULL;
ALTER TABLE `lb_virtuals` CHANGE `virt_enabled` `virt_enabled` varchar(8) COLLATE utf8_unicode_ci NOT NULL, CHANGE `virt_state` `virt_state` varchar(16) COLLATE utf8_unicode_ci NOT NULL;
