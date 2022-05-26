ALTER TABLE `processors` CHANGE `processor_index` `processor_index` VARCHAR(128) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL;
ALTER TABLE `mempools` CHANGE `mempool_index` `mempool_index` VARCHAR(128) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL;
ALTER TABLE `storage` CHANGE `storage_index` `storage_index` VARCHAR(128) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL;
ALTER TABLE `printersupplies` CHANGE `supply_index` `supply_index` VARCHAR(128) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL;
ALTER TABLE `lsp` CHANGE `lsp_index` `lsp_index` VARCHAR(128) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL;
