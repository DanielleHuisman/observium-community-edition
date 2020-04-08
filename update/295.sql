ALTER TABLE `ipsec_tunnels` ADD  `deleted` BOOLEAN NOT NULL DEFAULT FALSE;
DELETE FROM `ipv4_addresses` WHERE `port_id` NOT IN (SELECT port_id FROM ports);
DELETE FROM `ipv6_addresses` WHERE `port_id` NOT IN (SELECT port_id FROM ports);
DELETE FROM `ipv4_networks` WHERE `ipv4_network_id` NOT IN (SELECT ipv4_network_id FROM ipv4_addresses GROUP BY ipv4_network_id);
DELETE FROM `ipv6_networks` WHERE `ipv6_network_id` NOT IN (SELECT ipv6_network_id FROM ipv6_addresses GROUP BY ipv6_network_id);

