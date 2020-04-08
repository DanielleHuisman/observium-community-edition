ALTER TABLE  `applications` ADD  `app_lastpolled` INT NOT NULL DEFAULT  '0', ADD  `app_json` TEXT NULL DEFAULT NULL COMMENT  'JSON array of application data';
