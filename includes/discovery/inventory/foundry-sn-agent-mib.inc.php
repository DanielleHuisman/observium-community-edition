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

$snChasProductType = snmp_get($device, 'snChasProductType.0', '-OQv', 'FOUNDRY-SN-AGENT-MIB');

if ($snChasProductType) {
    $snChasSerNum = snmp_get($device, 'snChasSerNum.0', '-OQv', 'FOUNDRY-SN-AGENT-MIB');

    // Insert chassis as index 1, everything hangs off of this.
    $system_index             = 1;
    $inventory[$system_index] = [
      'entPhysicalDescr'        => $snChasProductType,
      'entPhysicalClass'        => 'chassis',
      'entPhysicalName'         => 'Chassis',
      'entPhysicalSerialNum'    => $snChasSerNum,
      'entPhysicalIsFRU'        => 'true',
      'entPhysicalContainedIn'  => 0,
      'entPhysicalParentRelPos' => -1,
      'entPhysicalMfgName'      => 'Brocade'
    ];

    discover_inventory($device, $system_index, $inventory[$system_index], $mib);

    // Now fetch data for the rest of the hardware in the chassis
    $data = snmpwalk_cache_oid($device, 'snAgentBrdTable', [], 'FOUNDRY-SN-AGENT-MIB');

    $relPos = 0;

    foreach ($data as $part) {
        $system_index = $part['snAgentBrdIndex'] * 256;

        if ($system_index != 0) {
            $containedIn = 1; // Attach to chassis inserted above

            // snAgentBrdModuleStatus.6 = moduleRunning
            // snAgentBrdModuleStatus.7 = moduleEmpty
            if ($part['snAgentBrdModuleStatus'] != 'moduleEmpty') {
                $relPos++;

                $inventory[$system_index] = [
                  'entPhysicalDescr'        => $part['snAgentBrdMainBrdDescription'],
                  'entPhysicalClass'        => 'module',
                  'entPhysicalName'         => $part['snAgentBrdMainBrdDescription'],
                  'entPhysicalSerialNum'    => $part['snAgentBrdSerialNumber'],
                  'entPhysicalIsFRU'        => 'true',
                  'entPhysicalContainedIn'  => $containedIn,
                  'entPhysicalParentRelPos' => $relPos,
                  'entPhysicalMfgName'      => 'Brocade'
                ];

                discover_inventory($device, $system_index, $inventory[$system_index], $mib);
            }
        }
    }
}

// EOF
