ALTER TABLE `bgpPeers` CHANGE `astext` `astext` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `ports_cbqos` CHANGE `policy_index` `policy_index` INT(11) UNSIGNED NOT NULL, CHANGE `object_index` `object_index` INT(11) UNSIGNED NOT NULL;
