ALTER TABLE `snmp_errors` CHANGE `snmp_cmd` `snmp_cmd` ENUM('snmpget','snmpwalk','snmpgetnext') CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL COMMENT 'Latin charset for 1byte chars!';
