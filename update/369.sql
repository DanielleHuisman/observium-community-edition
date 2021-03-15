-- NOTE update syslog, may be long operation ~30-45min
ALTER TABLE `syslog` CHANGE `tag` `tag` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;
