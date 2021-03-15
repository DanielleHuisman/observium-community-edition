ALTER TABLE `ports` CHANGE  `port_label_base`  `port_label_base` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;
ALTER TABLE `ports` CHANGE  `port_label_short`  `port_label_short` VARCHAR( 96 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;
ALTER TABLE `ports` CHANGE  `port_descr_circuit`  `port_descr_circuit` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ;
ALTER TABLE `ports` CHANGE  `port_label`  `port_label` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;
ALTER TABLE `ports` ADD `ifInNUcastPkts` bigint(20) UNSIGNED NULL DEFAULT NULL, ADD `ifInNUcastPkts_rate` int(11) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `ports` ADD `ifOutNUcastPkts` bigint(20) UNSIGNED NULL DEFAULT NULL, ADD `ifOutNUcastPkts_rate` int(11) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `ports` ADD `ifInBroadcastPkts` bigint(20) UNSIGNED NULL DEFAULT NULL, ADD `ifInBroadcastPkts_rate` int(11) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `ports` ADD `ifOutBroadcastPkts` bigint(20) UNSIGNED NULL DEFAULT NULL, ADD `ifOutBroadcastPkts_rate` int(11) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `ports` ADD `ifInMulticastPkts` bigint(20) UNSIGNED NULL DEFAULT NULL, ADD `ifInMulticastPkts_rate` int(11) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `ports` ADD `ifOutMulticastPkts` bigint(20) UNSIGNED NULL DEFAULT NULL, ADD `ifOutMulticastPkts_rate` int(11) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `ports` CHANGE  `ifInUcastPkts`  `ifInUcastPkts` BIGINT( 20 ) UNSIGNED NULL DEFAULT NULL ,CHANGE  `ifOutUcastPkts`  `ifOutUcastPkts` BIGINT( 20 ) UNSIGNED NULL DEFAULT NULL ,CHANGE  `ifInErrors`  `ifInErrors` BIGINT( 20 ) UNSIGNED NULL DEFAULT NULL ,CHANGE  `ifOutErrors`  `ifOutErrors` BIGINT( 20 ) UNSIGNED NULL DEFAULT NULL ,CHANGE  `ifInOctets`  `ifInOctets` BIGINT( 20 ) UNSIGNED NULL DEFAULT NULL ,CHANGE  `ifOutOctets`  `ifOutOctets` BIGINT( 20 ) UNSIGNED NULL DEFAULT NULL ,CHANGE  `ifInNUcastPkts`  `ifInNUcastPkts` BIGINT( 20 ) UNSIGNED NULL DEFAULT NULL ,CHANGE  `ifOutNUcastPkts`  `ifOutNUcastPkts` BIGINT( 20 ) UNSIGNED NULL DEFAULT NULL ,CHANGE  `ifInBroadcastPkts`  `ifInBroadcastPkts` BIGINT( 20 ) UNSIGNED NULL DEFAULT NULL ,CHANGE  `ifOutBroadcastPkts`  `ifOutBroadcastPkts` BIGINT( 20 ) UNSIGNED NULL DEFAULT NULL ,CHANGE  `ifInMulticastPkts`  `ifInMulticastPkts` BIGINT( 20 ) UNSIGNED NULL DEFAULT NULL ,CHANGE  `ifOutMulticastPkts`  `ifOutMulticastPkts` BIGINT( 20 ) UNSIGNED NULL DEFAULT NULL ;
ALTER TABLE `ports` ADD `port_mcbc` BOOLEAN NULL DEFAULT NULL AFTER  `ifOutMulticastPkts_rate` ;
ALTER TABLE `ports` ADD `ifInDiscards` BIGINT(20) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `ports` ADD `ifInDiscards_rate` INT(11) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `ports` ADD `ifOutDiscards` BIGINT(20) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `ports` ADD `ifOutDiscards_rate` INT(11) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `ports` CHANGE `ifOperStatus` `ifOperStatus` ENUM( 'testing', 'notPresent', 'dormant', 'down', 'lowerLayerDown', 'unknown', 'up', 'monitoring' ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ;
-- NOTE update syslog, may be long operation ~15-30min
ALTER TABLE `syslog` CHANGE `tag` `tag` VARCHAR(32) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
