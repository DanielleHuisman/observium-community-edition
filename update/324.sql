ALTER TABLE `mempools` CHANGE `mempool_oid_free`  `mempool_oid_free` VARCHAR(128) DEFAULT NULL;
ALTER TABLE `mempools` CHANGE `mempool_oid_total` `mempool_oid_total` VARCHAR(128) DEFAULT NULL;
ALTER TABLE `mempools` CHANGE `mempool_oid_used`  `mempool_oid_used` VARCHAR(128) DEFAULT NULL;
