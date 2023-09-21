<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// WISI-GTMODULES-MIB::gtThisModuleSlot.0 = 7

$gtThisModuleSlot = snmp_get_oid($device, 'gtThisModuleSlot.0', 'WISI-GTMODULES-MIB');

if (!safe_empty($gtThisModuleSlot)) {
    $data = snmp_get_multi_oid($device, ['gtModuleFWID.' . $gtThisModuleSlot, 'gtModuleSerNo.' . $gtThisModuleSlot, 'gtModuleName.' . $gtThisModuleSlot], [], 'WISI-GTMODULES-MIB');

    $version  = $data[$gtThisModuleSlot]['gtModuleFWID'];
    $serial   = $data[$gtThisModuleSlot]['gtModuleSerNo'];
    $hardware = $data[$gtThisModuleSlot]['gtModuleName'];
}

unset($gtThisModuleSlot, $data);

// EOF
