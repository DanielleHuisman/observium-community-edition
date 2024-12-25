<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) Adam Armstrong
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
