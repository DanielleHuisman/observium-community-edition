ALTER TABLE `roles_entity_permissions` CHANGE `access_level` `access_level` ENUM('ro','rw') NOT NULL DEFAULT 'ro';
ALTER TABLE `roles_entity_permissions` CHANGE `access_level` `access` ENUM('ro','rw') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'ro';
ALTER TABLE `entity_permissions` CHANGE `access_level` `access` ENUM('ro','rw') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'ro';
