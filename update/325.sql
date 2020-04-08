ALTER TABLE `mempools` ADD `mempool_object`  VARCHAR(64) DEFAULT NULL;
ALTER TABLE `mempools` DROP `mempool_oid_free` 
ALTER TABLE `mempools` DROP `mempool_oid_total` 
ALTER TABLE `mempools` DROP `mempool_oid_used` 
