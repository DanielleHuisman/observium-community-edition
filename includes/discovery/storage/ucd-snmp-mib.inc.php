<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// NOTE. Here only walking, because needed additional checks by HOST-RESOURCES-MIB (see host-resources-mib.inc.php in current directory)

$cache_discovery['ucd-snmp-mib'] = snmpwalk_cache_oid($device, 'dskEntry', [], 'UCD-SNMP-MIB');

// EOF
