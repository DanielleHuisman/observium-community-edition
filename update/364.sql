CREATE TABLE `dashboards` (  `dash_id` int(11) NOT NULL,  `dash_name` varchar(128) COLLATE utf8_unicode_ci NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
INSERT INTO `dashboards` (`dash_id`, `dash_name`) VALUES(1, 'Default Dashboard');
ALTER TABLE `dashboards`  ADD PRIMARY KEY (`dash_id`);
ALTER TABLE `dashboards`  MODIFY `dash_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
CREATE TABLE `dash_widgets` (  `widget_id` int(11) NOT NULL,  `dash_id` int(11) NOT NULL,  `widget_type` varchar(32) NOT NULL,  `widget_config` text NOT NULL,  `x` int(11) DEFAULT NULL,  `y` int(11) DEFAULT NULL,  `width` int(11) NOT NULL,  `height` int(11) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=latin1;
ALTER TABLE `dash_widgets`  ADD PRIMARY KEY (`widget_id`);
ALTER TABLE `dash_widgets`  MODIFY `widget_id` int(11) NOT NULL AUTO_INCREMENT;
