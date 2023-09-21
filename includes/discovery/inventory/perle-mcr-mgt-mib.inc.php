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

$ChasProductType = snmp_get_oid($device, 'chassisModelName.1', 'PERLE-MCR-MGT-MIB');

if ($ChasProductType) {
    $ChasDesc     = snmp_get_oid($device, 'chassisModelDesc.1', 'PERLE-MCR-MGT-MIB');
    $ChasSerNum   = snmp_get_oid($device, 'chassisSerialNumber.1', 'PERLE-MCR-MGT-MIB');
    $ChasMgmtSlot = snmp_get_oid($device, 'chassisCfgMgmtSlot.1', 'PERLE-MCR-MGT-MIB');

    // Insert chassis as index 1, everything hangs off of this.
    $system_index             = 1;
    $inventory[$system_index] = [
      'entPhysicalDescr'        => $ChasProductType,
      'entPhysicalClass'        => 'chassis',
      'entPhysicalName'         => $ChasDesc,
      'entPhysicalSerialNum'    => $ChasSerNum,
      'entPhysicalIsFRU'        => 'true',
      'entPhysicalContainedIn'  => 0,
      'entPhysicalParentRelPos' => -1,
      'entPhysicalMfgName'      => 'Perle'
    ];

    discover_inventory($device, $system_index, $inventory[$system_index], $mib);

    // Now fetch data for the rest of the hardware in the chassis
    $data     = snmpwalk_cache_oid($device, 'mcrChassisSlotTable', [], 'PERLE-MCR-MGT-MIB');
    $data_sfp = snmpwalk_cache_oid($device, 'mcrSfpDmiModuleTable', [], 'PERLE-MCR-MGT-MIB');

    $relPos = 0;

    foreach ($data as $part) {
        $system_index = $part['mcrChassisSlotIndex'] * 256;
        $slotindex    = $part['mcrChassisSlotIndex'];

        if ($system_index != 0) {
            $containedIn = 1; // Attach to chassis inserted above

            // snAgentBrdModuleStatus.6 = moduleRunning
            // snAgentBrdModuleStatus.7 = moduleEmpty
            if ($part['mcrModuleModelName'] != '') {
                $relPos++;

                $inventory[$system_index] = [
                  'entPhysicalDescr'        => $part['mcrUserDefinedModuleName'] . "(" . $part['mcrModuleModelDesc'] . ")",
                  'entPhysicalClass'        => 'module',
                  'entPhysicalName'         => $part['mcrModuleModelName'],
                  'entPhysicalSerialNum'    => $part['mcrModuleSerialNumber'],
                  'entPhysicalIsFRU'        => 'true',
                  'entPhysicalContainedIn'  => $containedIn,
                  'entPhysicalParentRelPos' => $relPos,
                  'entPhysicalFirmwareRev'  => $part['mcrModuleBootloaderVersion'],
                  'entPhysicalSoftwareRev'  => $part['mcrModuleFirmwareVersion'],
                  'entPhysicalMfgName'      => 'Perle',
                ];

                discover_inventory($device, $system_index, $inventory[$system_index], $mib);
            }

            foreach ($data_sfp as $part_sfp) {

                if ($part_sfp['sfpDmiSlotIndex'] == $slotindex) {
                    $system_index_sfp = $part_sfp['sfpDmiSlotIndex'] * 256 + 1;

                    $relPos++;
                    if ($part_sfp['sfpDmiLinkReach625125'] != 0) {
                        $range = $part_sfp['sfpDmiLinkReach625125'] . "m";
                    }
                    if ($part_sfp['sfpDmiLinkReach50125'] != 0) {
                        $range = $part_sfp['sfpDmiLinkReach50125'] . "m";
                    }
                    if ($part_sfp['sfpDmiLinkReach9125'] != 0) {
                        $range = ($part_sfp['sfpDmiLinkReach9125'] / 1000) . "km";
                    }

                    $inventory[$system_index] = [
                      'entPhysicalDescr'        => $part_sfp['sfpDmiVendorName'] . " SFP (" . $part_sfp['sfpDmiFiberWaveLength'] . "nm " . $range . " " . $part_sfp['sfpDmiNominalBitRate'] . "Mbps)",
                      'entPhysicalClass'        => 'module',
                      'entPhysicalName'         => $part_sfp['sfpDmiVendorPartNumber'],
                      'entPhysicalSerialNum'    => $part_sfp['sfpDmiVendorSerialNumber'],
                      'entPhysicalIsFRU'        => 'true',
                      'entPhysicalContainedIn'  => $system_index,
                      'entPhysicalParentRelPos' => $relPos,
                      'entPhysicalMfgName'      => $part_sfp['sfpDmiVendorName'],
                    ];

                    discover_inventory($device, $system_index_sfp, $inventory[$system_index], $mib);

                }
            }
        }
    }
}

// EOF
