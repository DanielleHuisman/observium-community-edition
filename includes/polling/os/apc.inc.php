<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

if (match_oid_num($device['sysObjectID'], '.1.3.6.1.4.1.5528')) {
    // Exclude old netbotz

    // These oids are in APC's "experimental" tree
    // PowerNet-Discovery-MIB::apcDiscoveryDeviceSerialNumber.1 = STRING: 00:C0:B7:8E:94:2F
    // PowerNet-Discovery-MIB::apcDiscoveryDeviceSerialNumber.2 = STRING: 00:C0:B7:8E:94:2F
    // PowerNet-Discovery-MIB::apcDiscoveryFirmwareName.1 = STRING: apc_bw04_aos_440.bin
    // PowerNet-Discovery-MIB::apcDiscoveryFirmwareName.2 = STRING: apc_bw04_bw_440.bin
    // PowerNet-Discovery-MIB::apcDiscoveryFirmwareRevision.1 = STRING: V4.4.0(20140827_1358)
    // PowerNet-Discovery-MIB::apcDiscoveryFirmwareRevision.2 = STRING: V4.4.0(20140827_1358)

    if (empty($version) && $AOSrev = snmp_get_oid($device, 'apcDiscoveryFirmwareRevision.1', 'PowerNet-Discovery-MIB')) {
        $version = explode('(', ltrim($AOSrev, 'vV'))[0];
        $serial  = snmp_get_oid($device, 'apcDiscoveryDeviceSerialNumber.1', 'PowerNet-Discovery-MIB');
    }
    return;
}

if (match_oid_num($device['sysObjectID'], '.1.3.6.1.4.1.52674')) {
    // Exclude netbotz v5

    // These oids are in APC's "experimental" tree
    // PowerNet-Discovery-MIB::apcDiscoveryDeviceSerialNumber.1 = STRING: QA19241XXXXX
    // PowerNet-Discovery-MIB::apcDiscoveryFirmwareName.1 = STRING: env_mon_nb075x
    // PowerNet-Discovery-MIB::apcDiscoveryFirmwareRevision.1 = STRING: 5.2.0.3061
    if (empty($version) && $AOSrev = snmp_get_oid($device, 'apcDiscoveryFirmwareRevision.1', 'PowerNet-Discovery-MIB')) {
        $version = explode('(', ltrim($AOSrev, 'vV'))[0];
        $serial  = snmp_get_oid($device, 'apcDiscoveryDeviceSerialNumber.1', 'PowerNet-Discovery-MIB');
    }

    // PowerNet-Discovery-MIB::apcDiscoveryModel.1 = STRING: NetBotz Rack Monitor 750
    // PowerNet-Discovery-MIB::apcDiscoverySerialNumber.1 = STRING: QA192417XXXXX
    // PowerNet-Discovery-MIB::apcDiscoveryStatus.1 = INTEGER: deviceNormal(2)
    // PowerNet-Discovery-MIB::apcDiscoveryLabelString.1 = STRING: NetBotz Rack Monitor 750
    if (empty($hardware) && $hw = snmp_get_oid($device, 'apcDiscoveryModel.1', 'PowerNet-Discovery-MIB')) {
        $hardware = $hw;
        //$serial   = snmp_get_oid($device, 'apcDiscoverySerialNumber.1', 'PowerNet-Discovery-MIB');
    }
    return;
}

// PowerNet-MIB::networkAir
if (match_oid_num($device['sysObjectID'], '.1.3.6.1.4.1.318.1.3.14')) {
    // Cooling units use different hardware/serial table

    // PowerNet-Discovery-MIB::apcDiscoveryModel.1 = STRING: RC
    // PowerNet-Discovery-MIB::apcDiscoverySerialNumber.1 = STRING: JK161600XXXX
    // PowerNet-Discovery-MIB::apcDiscoveryStatus.1 = INTEGER: deviceNormal(2)
    // PowerNet-Discovery-MIB::apcDiscoveryLabelString.1 = STRING: RC

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
        # UPS
        // PowerNet-Discovery-MIB::apcDiscoveryModel.1 = STRING: Smart-UPS
        // PowerNet-Discovery-MIB::apcDiscoverySerialNumber.1 = STRING: AS11272XXXXX
        // PowerNet-Discovery-MIB::apcDiscoveryStatus.1 = INTEGER: deviceNormal(2)
        // PowerNet-Discovery-MIB::apcDiscoveryLabelString.1 = STRING: Smart-UPS 3000 XL
        'ups' => [
            'model'  => 'upsBasicIdentModel',
            'hwrev'  => 'upsAdvIdentFirmwareRevision',
            'fwrev'  => 'upsAdvIdentFirmwareRevision',
            'serial' => 'upsAdvIdentSerialNumber'
        ],
        # ATS
        // PowerNet-Discovery-MIB::apcDiscoveryModel.1 = STRING: ATS
        // PowerNet-Discovery-MIB::apcDiscoverySerialNumber.1 = STRING: 5A1910TXXXXX
        // PowerNet-Discovery-MIB::apcDiscoveryStatus.1 = INTEGER: deviceNormal(2)
        // PowerNet-Discovery-MIB::apcDiscoveryLabelString.1 = STRING: Automatic Transfer Switch
        'ats' => [
            'model'  => 'atsIdentModelNumber',
            'hwrev'  => 'atsIdentHardwareRev',
            'fwrev'  => 'atsIdentFirmwareRev',
            'serial' => 'atsIdentSerialNumber'
        ],
        # PDU
        'rPDU' => [
            'model'  => 'rPDUIdentModelNumber',
            'hwrev'  => 'rPDUIdentHardwareRev',
            'fwrev'  => 'rPDUIdentFirmwareRev',
            'serial' => 'rPDUIdentSerialNumber'
        ],
        # PDU2
        'rPDU2' => [
            'model'  => 'rPDU2IdentModelNumber',
            'hwrev'  => 'rPDU2IdentHardwareRev',
            'fwrev'  => 'rPDU2IdentFirmwareRev',
            'serial' => 'rPDU2IdentSerialNumber'
        ],
        # Masterswitch/AP9606
        'sPDU'  => [
            'model'  => 'sPDUIdentModelNumber',
            'hwrev'  => 'sPDUIdentHardwareRev',
            'fwrev'  => 'sPDUIdentFirmwareRev',
            'serial' => 'sPDUIdentSerialNumber'
        ],
        # NetBotz 200
        'ems' => [
            'model'  => 'emsIdentProductNumber',
            'hwrev'  => 'emsIdentHardwareRev',
            'fwrev'  => 'emsIdentFirmwareRev',
            'serial' => 'emsIdentSerialNumber'
        ],
        # In-Row Chiller
        'airIRRC' => [
            'model'  => 'airIRRCUnitIdentModelNumber',
            'hwrev'  => 'airIRRCUnitIdentHardwareRevision',
            'fwrev'  => 'airIRRCUnitIdentFirmwareRevision',
            'serial' => 'airIRRCUnitIdentSerialNumber'
        ],
        # A/C
        'airPA' => [
            'model'  => 'airPAModelNumber',
            'hwrev'  => 'airPAHardwareRevision',
            'fwrev'  => 'airPAFirmwareRevision',
            'serial' => 'airPASerialNumber'
        ],
        # PDU, moved to definitions
        // 'xPDU' => [
        //     'model'  => 'xPDUIdentModelNumber',
        //     'hwrev'  => 'xPDUIdentHardwareRev',
        //     'fwrev'  => 'xPDUIdentFirmwareAppRev',
        //     'serial' => 'xPDUIdentSerialNumber'
        // ],
        # ATS
        'xATS' => [
            'model'  => 'xATSIdentModelNumber',
            'hwrev'  => 'xATSIdentHardwareRev',
            'fwrev'  => 'xATSIdentFirmwareAppRev',
            'serial' => 'xATSIdentSerialNumber'
        ],
        # Modular PDU
        'isx' => [
            'model'  => 'isxModularPduIdentModelNumber',
            'hwrev'  => 'isxModularPduIdentMonitorCardHardwareRev',
            'fwrev'  => 'isxModularPduIdentMonitorCardFirmwareAppRev',
            'serial' => 'isxModularPduIdentSerialNumber'
        ],
    ];
    $apc_keys = array_keys($apc_oids); // all keys

    // These oids are in APC's "experimental" tree, but there is no "real" UPS equivalent for the firmware versions.
    if (empty($version) && $AOSrev = snmp_get_oid($device, 'apcDiscoveryFirmwareRevision.1', 'PowerNet-Discovery-MIB')) {
        $version  = $AOSrev;
        $serial   = snmp_get_oid($device, 'apcDiscoveryDeviceSerialNumber.1', 'PowerNet-Discovery-MIB');
        $features = snmp_get_oid($device, 'apcDiscoveryModel.1', 'PowerNet-Discovery-MIB');
        if (preg_match('/^AP\d/', $features) &&
            $label = snmp_get_oid($device, 'apcDiscoveryLabelString.1', 'PowerNet-Discovery-MIB')) {
            // PowerNet-Discovery-MIB::apcDiscoveryModel.1 = STRING: AP8953
            // PowerNet-Discovery-MIB::apcDiscoverySerialNumber.1 = STRING: ZA10470XXXXX
            // PowerNet-Discovery-MIB::apcDiscoveryStatus.1 = INTEGER: deviceSevere(4)
            // PowerNet-Discovery-MIB::apcDiscoveryLabelString.1 = STRING: Switched Rack PDU
            $features = $label;
        }

        // Force known oids by model/features
        switch (TRUE) {
            case str_contains_array($features, [ 'Smart-UPS', 'Symmetra', 'GVSUPS', 'Galaxy' ]):
                $apc_keys = [ 'ups' ];
                break;

            case str_starts_with($features, 'ATS'):
                $apc_keys = [ 'ats' ];
                break;

            case str_starts_with($features, 'rd3xx'):
                $apc_keys = [ 'airIRRC' ];
                break;

            case str_contains($features, 'Rack PDU'):
            case match_oid_num($device['sysObjectID'], '.1.3.6.1.4.1.318.1.3.4'):
                $apc_keys = [ 'rPDU2', 'rPDU' ];
                break;

            case str_contains($features, 'InfraStruXure PDU'):
            case match_oid_num($device['sysObjectID'], '.1.3.6.1.4.1.318.1.3.15'):
                $apc_keys = [ 'xPDU' ];
                break;
        }
    }

    foreach ($apc_keys as $key) {
        if (!safe_empty($hardware)) {
            break;
        }
        $oids = $apc_oids[$key];

        $model = snmp_getnext_oid($device, $oids['model'], 'PowerNet-MIB');
        if (empty($model)) {
            continue;
        }
        $hardware = trim($model . ' ' . snmp_getnext_oid($device, $oids['hwrev'], 'PowerNet-MIB'));

        if (empty($version)) {
            $version = snmp_getnext_oid($device, $oids['fwrev'], 'PowerNet-MIB');
        }
        if (empty($serial)) {
            $serial  = snmp_getnext_oid($device, $oids['serial'], 'PowerNet-MIB');
        }
    }
}

// v3.7.4 -> 3.7.4
if (strlen($version)) {
    $version = ltrim($version, 'vV');
}

if (strlen($features) && preg_match('/^v?\d/', $features)) {
    $features = 'App ' . ltrim($features, 'v');
}

// EOF
