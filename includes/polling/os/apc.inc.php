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

if (match_oid_num($device['sysObjectID'], '.1.3.6.1.4.1.5528')) {
    // Exclude old netbotz
    return;
}

// PowerNet-MIB::networkAir
if (match_oid_num($device['sysObjectID'], '.1.3.6.1.4.1.318.1.3.14')) {
    // Cooling units use different hardware/serial table

    // PowerNet-MIB::coolingUnitAboutDescription.1.1 = STRING: "Model Number"
    // PowerNet-MIB::coolingUnitAboutDescription.1.2 = STRING: "Serial Number"
    // PowerNet-MIB::coolingUnitAboutDescription.1.3 = STRING: "Firmware Revision"
    // PowerNet-MIB::coolingUnitAboutDescription.1.4 = STRING: "Hardware Revision"
    // PowerNet-MIB::coolingUnitAboutDescription.1.5 = STRING: "Manufacture Date"
    // PowerNet-MIB::coolingUnitAboutDescription.1.6 = STRING: "Application Version"
    // PowerNet-MIB::coolingUnitAboutDescription.1.7 = STRING: "OS Version"
    // PowerNet-MIB::coolingUnitAboutDescription.1.8 = STRING: "APC Boot Monitor"
    // PowerNet-MIB::coolingUnitAboutValue.1.1 = STRING: "ACRC301S"
    // PowerNet-MIB::coolingUnitAboutValue.1.2 = STRING: "JK161600XXXX"
    // PowerNet-MIB::coolingUnitAboutValue.1.3 = STRING: "1.4.2"
    // PowerNet-MIB::coolingUnitAboutValue.1.4 = STRING: "01"
    // PowerNet-MIB::coolingUnitAboutValue.1.5 = STRING: "04/20/2016"
    // PowerNet-MIB::coolingUnitAboutValue.1.6 = STRING: "v6.1.0"
    // PowerNet-MIB::coolingUnitAboutValue.1.7 = STRING: "v6.1.0"
    // PowerNet-MIB::coolingUnitAboutValue.1.8 = STRING: "v1.0.8"
    $coolingUnitAboutTable = snmpwalk_cache_twopart_oid($device, 'coolingUnitAboutTable', [], 'PowerNet-MIB');
    foreach ($coolingUnitAboutTable[1] as $entry) {
        switch ($entry['coolingUnitAboutDescription']) {
            case 'Model Number':
                $hardware = $entry['coolingUnitAboutValue'];
                break;
            case 'Serial Number':
                $serial = $entry['coolingUnitAboutValue'];
                break;
            case 'Firmware Revision': // or 'OS Version'?
                $version = $entry['coolingUnitAboutValue'];
                break;
            case 'Hardware Revision':
                if ($hardware) {
                    $hardware .= ' ' . $entry['coolingUnitAboutValue'];
                }
                break;
            case 'Application Version':
                $features = $entry['coolingUnitAboutValue'];
                break;
        }
    }
}

// Mostly hardware/version detected by sysDescr definitions
if (empty($hardware)) {
    $apc_oids = [
      'ups'     => ['model' => 'upsBasicIdentModel', 'hwrev' => 'upsAdvIdentFirmwareRevision', 'fwrev' => 'upsAdvIdentFirmwareRevision'],      # UPS
      'ats'     => ['model' => 'atsIdentModelNumber', 'hwrev' => 'atsIdentHardwareRev', 'fwrev' => 'atsIdentFirmwareRev'],              # ATS
      'rPDU'    => ['model' => 'rPDUIdentModelNumber', 'hwrev' => 'rPDUIdentHardwareRev', 'fwrev' => 'rPDUIdentFirmwareRev'],             # PDU
      'rPDU2'   => ['model' => 'rPDU2IdentModelNumber', 'hwrev' => 'rPDU2IdentHardwareRev', 'fwrev' => 'rPDU2IdentFirmwareRev'],            # PDU
      'sPDU'    => ['model' => 'sPDUIdentModelNumber', 'hwrev' => 'sPDUIdentHardwareRev', 'fwrev' => 'sPDUIdentFirmwareRev'],             # Masterswitch/AP9606
      'ems'     => ['model' => 'emsIdentProductNumber', 'hwrev' => 'emsIdentHardwareRev', 'fwrev' => 'emsIdentFirmwareRev'],              # NetBotz 200
      'airIRRC' => ['model' => 'airIRRCUnitIdentModelNumber', 'hwrev' => 'airIRRCUnitIdentHardwareRevision', 'fwrev' => 'airIRRCUnitIdentFirmwareRevision'], # In-Row Chiller
      'airPA'   => ['model' => 'airPAModelNumber', 'hwrev' => 'airPAHardwareRevision', 'fwrev' => 'airPAFirmwareRevision'],            # A/C
      //'xPDU'    => [ 'model' => 'xPDUIdentModelNumber',          'hwrev' => 'xPDUIdentHardwareRev',              'fwrev' => 'xPDUIdentFirmwareAppRev' ],          # PDU, moved to definitions
      'xATS'    => ['model' => 'xATSIdentModelNumber', 'hwrev' => 'xATSIdentHardwareRev', 'fwrev' => 'xATSIdentFirmwareAppRev'],          # ATS
      'isx'     => ['model' => 'isxModularPduIdentModelNumber', 'hwrev' => 'isxModularPduIdentMonitorCardHardwareRev', 'fwrev' => 'isxModularPduIdentMonitorCardFirmwareAppRev'], # Modular PDU
    ];

    // These oids are in APC's "experimental" tree, but there is no "real" UPS equivalent for the firmware versions.
    $AOSrev = snmp_get_oid($device, '.1.3.6.1.4.1.318.1.4.2.4.1.4.1', 'PowerNet-MIB');
    if ($AOSrev) {
        $version  = $AOSrev;
        $features = snmp_get_oid($device, '.1.3.6.1.4.1.318.1.4.2.4.1.4.2', 'PowerNet-MIB');
    }

    foreach ($apc_oids as $oid_list) {
        if (!$hardware) {
            $model = snmp_getnext_oid($device, $oid_list['model'], 'PowerNet-MIB');
            if (empty($model)) {
                continue;
            }

            $hardware = trim($model . ' ' . snmp_getnext_oid($device, $oid_list['hwrev'], 'PowerNet-MIB'));

            if (!$AOSrev) {
                $version = snmp_getnext_oid($device, $oid_list['fwrev'], 'PowerNet-MIB');
            }

            break;
        }
    }
}

// v3.7.4 -> 3.7.4
if (strlen($version)) {
    $version = ltrim($version, 'v');
}

if (strlen($features) && preg_match('/^v?\d/', $features)) {
    $features = 'App ' . ltrim($features, 'v');
}

// EOF
