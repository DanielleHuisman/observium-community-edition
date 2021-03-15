CREATE TABLE `winservices` (  `winsrv_id` int(11) NOT NULL,  `device_id` int(11) NOT NULL,  `name` varchar(96) NOT NULL,  `display-name` varchar(96) NOT NULL,  `State` varchar(40) NOT NULL,  `StartMode` varchar(40) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8;
ALTER TABLE `winservices`  ADD PRIMARY KEY (`winsrv_id`);
ALTER TABLE `winservices`  MODIFY `winsrv_id` int(11) NOT NULL AUTO_INCREMENT;
