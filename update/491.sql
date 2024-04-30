DROP TABLE IF EXISTS `netmaps`;
CREATE TABLE `netmaps` (  `netmap_id` int NOT NULL,  `name` char(64) NOT NULL,  `info` mediumtext NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
ALTER TABLE `netmaps`  ADD PRIMARY KEY (`netmap_id`);
ALTER TABLE `netmaps`  MODIFY `netmap_id` int NOT NULL AUTO_INCREMENT;
