ALTER TABLE  `netscaler_services` CHANGE  `svc_fullname`  `svc_fullname` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL , CHANGE  `svc_label`  `svc_label` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ;
ALTER TABLE  `netscaler_services_vservers` CHANGE  `vsvr_name`  `vsvr_name` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,CHANGE  `svc_name`  `svc_name` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ;

