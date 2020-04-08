ALTER TABLE `bgpPeers_cbgp` ADD `bgpPeer_id` INT NOT NULL AFTER `device_id`;
ALTER TABLE `bgpPeers_cbgp` DROP INDEX `unique_index`;
ALTER TABLE `bgpPeers_cbgp` DROP INDEX `device_id`;
UPDATE bgpPeers_cbgp SET bgpPeers_cbgp.bgpPeer_id = (SELECT bgpPeers.bgpPeer_id FROM bgpPeers WHERE bgpPeers.device_id = bgpPeers_cbgp.device_id AND bgpPeers.bgpPeerRemoteAddr = bgpPeers_cbgp.bgpPeerRemoteAddr);
DELETE FROM `bgpPeers_cbgp` WHERE `bgpPeer_id` = 0;
ALTER TABLE `bgpPeers_cbgp` DROP `bgpPeerRemoteAddr`;
ALTER TABLE `bgpPeers_cbgp` ADD UNIQUE KEY `unique_index` (`bgpPeer_id`,`device_id`,`afi`,`safi`);
