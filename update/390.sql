CREATE TABLE `pollers` (  `poller_id` int(11) NOT NULL,  `poller_name` varchar(128) COLLATE utf8_unicode_ci NOT NULL,  `poller_stats` text COLLATE utf8_unicode_ci) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
ALTER TABLE `pollers`  ADD PRIMARY KEY (`poller_id`),  ADD UNIQUE KEY `poller_name` (`poller_name`);
ALTER TABLE `pollers`  MODIFY `poller_id` int(11) NOT NULL AUTO_INCREMENT;
