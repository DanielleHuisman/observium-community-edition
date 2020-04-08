ALTER TABLE  `users` CHANGE  `user_id`  `user_id` BIGINT( 20 ) NOT NULL AUTO_INCREMENT ;
ALTER TABLE  `users_prefs` CHANGE  `user_id`  `user_id` BIGINT( 20 ) NOT NULL ;
ALTER TABLE  `entity_permissions` CHANGE  `user_id`  `user_id` BIGINT( 20 ) NOT NULL ;
