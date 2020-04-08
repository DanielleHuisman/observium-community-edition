ALTER TABLE `ipsec_tunnels` ADD `tunnel_index` VARCHAR(64) NULL DEFAULT NULL AFTER `device_id`, ADD `tunnel_ike_index` VARCHAR(64) NULL DEFAULT NULL AFTER `tunnel_index`;
ALTER TABLE `ipsec_tunnels` ADD `tunnel_endpoint` TEXT NULL DEFAULT NULL AFTER `tunnel_json`, ADD `tunnel_endhash` VARCHAR(32) NULL DEFAULT NULL AFTER `tunnel_endpoint`;
ALTER TABLE `ipsec_tunnels` ADD `tunnel_added` INT(11) NULL DEFAULT NULL AFTER `tunnel_endhash`, ADD `mib` VARCHAR(128) NULL DEFAULT NULL AFTER `tunnel_added`;
-- Recreate unique index
ALTER TABLE `ipsec_tunnels` DROP INDEX `unique_index`;
ALTER TABLE `ipsec_tunnels` ADD UNIQUE `unique_index` (`device_id`, `local_addr`, `peer_addr`, `tunnel_endhash`);
