CREATE TABLE `weathermaps` (  `wmap_id` int NOT NULL,  `wmap_name` varchar(32) NOT NULL,  `wmap_descr` varchar(128) DEFAULT NULL,  `wmap_conf` mediumtext NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
ALTER TABLE `weathermaps`  ADD PRIMARY KEY (`wmap_id`),  ADD UNIQUE KEY `wmap_name` (`wmap_name`);
ALTER TABLE `weathermaps`  MODIFY `wmap_id` int NOT NULL AUTO_INCREMENT;
