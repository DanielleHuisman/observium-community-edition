ALTER TABLE `oids_entries` CHANGE `event` `event` ENUM('ok','warning','alert','ignore') CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL DEFAULT 'ignore';
