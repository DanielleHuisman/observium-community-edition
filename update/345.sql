ALTER TABLE `bgpPeers` ADD INDEX `bgp_local_peer` (`device_id`, `bgpPeerLocalAddr`);
ALTER TABLE `bgpPeers` ADD INDEX `bgp_remote_peer` (`device_id`, `bgpPeerRemoteAs`, `bgpPeerRemoteAddr`);
