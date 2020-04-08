ALTER TABLE `vlans` ADD `vlan_context` BOOLEAN NOT NULL DEFAULT FALSE AFTER `vlan_status`;
UPDATE `vlans` SET `vlan_context` = '1' WHERE `device_id` IN (SELECT `device_id` FROM `devices` WHERE `os` IN ('ios','iosxr','iosxe')) AND `vlan_type` = 'ethernet' AND `vlan_status` != '';
