ALTER TABLE `entity_permissions` CHANGE `entity_type` `entity_type` VARCHAR(32) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL, CHANGE `access` `access` ENUM('ro','rw') CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL DEFAULT 'ro';
ALTER TABLE `entity_permissions` ADD `auth_mechanism` VARCHAR(11) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL AFTER `user_id`;
ALTER TABLE `entity_permissions` ADD UNIQUE `user_auth` (`user_id`, `auth_mechanism`, `entity_id`, `entity_type`);
ALTER TABLE `roles_users` ADD `auth_mechanism` VARCHAR(11) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL AFTER `user_id`;
ALTER TABLE `roles_users` ADD UNIQUE `user_auth` (`user_id`, `auth_mechanism`);
