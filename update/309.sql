ALTER TABLE `ipsec_tunnels` ADD `tunnel_ike_alive` VARCHAR(8) NULL DEFAULT NULL AFTER `tunnel_status`, ADD `tunnel_lifetime` INT(11) NULL DEFAULT NULL AFTER `tunnel_ike_alive`, ADD `tunnel_ike_lifetime` INT(11) NULL DEFAULT NULL AFTER `tunnel_lifetime`, ADD `tunnel_json` TEXT NULL DEFAULT NULL AFTER `tunnel_ike_lifetime`;