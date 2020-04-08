ALTER TABLE `status-state` CHANGE `status_event` `status_event` ENUM('ok','warning','alert','down','ignore') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
