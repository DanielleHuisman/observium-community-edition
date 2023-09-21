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

echo("ENTITY-MIB ");

$snmp_flags = OBS_SNMP_ALL;
//snmp_log_error(OBS_SNMP_ERROR_OID_NOT_INCREASING, FALSE); // disable log error for next snmpwalk
$entity_array = snmpwalk_cache_oid($device, "entPhysicalEntry", [], snmp_mib_entity_vendortype($device, 'ENTITY-MIB'));
if (!snmp_status()) {

    if (snmp_error_code() === OBS_SNMP_ERROR_OID_NOT_INCREASING) {
        // Try refetch with NOINCREASE
        $snmp_flags |= OBS_SNMP_NOINCREASE;
        print_debug("WARNING! snmpwalk error 'OID not increasing' detected, try snmpwalk with -Cc option.");

        $entity_array = snmpwalk_cache_oid($device, "entPhysicalEntry", $entity_array, snmp_mib_entity_vendortype($device, 'ENTITY-MIB'), NULL, $snmp_flags);
        if (!snmp_status()) {
            return;
        }
    } else {
        return;
    }
}

$entity_array = snmpwalk_cache_twopart_oid($device, "entAliasMappingIdentifier", $entity_array, 'ENTITY-MIB:IF-MIB', NULL, $snmp_flags);

$GLOBALS['cache']['snmp']['ENTITY-MIB'][$device['device_id']] = $entity_array; // Cache this array for sensors discovery (see in cisco-entity-sensor-mib or entity-sensor-mib)

// Some vendor specific inventory expander's
$vendor_oids = [];
$vendor_mib  = NULL;
if (is_device_mib($device, 'ELTEX-MES-PHYSICAL-DESCRIPTION-MIB')) {
    $vendor_mib  = 'ELTEX-MES-PHYSICAL-DESCRIPTION-MIB';
    $vendor_oids = snmpwalk_cache_oid($device, "eltPhdTransceiverInfoTable", $vendor_oids, $vendor_mib, NULL, $snmp_flags);
    //print_vars($entity_array);
    //print_debug_vars($vendor_oids);
} elseif (is_device_mib($device, 'DGS3627G-L2MGMT-MIB')) {
    $vendor_mib  = 'DGS3627G-L2MGMT-MIB';
    $vendor_oids = snmpwalk_cache_oid($device, "swL2PortSfpInfoTable", $vendor_oids, $vendor_mib, NULL, $snmp_flags);
    //print_vars($entity_array);
    //print_debug_vars($vendor_oids);
} elseif (is_device_mib($device, 'DGS3120-24SC-L2MGMT-MIB')) {
    $vendor_mib  = 'DGS3120-24SC-L2MGMT-MIB';
    $vendor_oids = snmpwalk_cache_oid($device, "swL2PortSfpInfoTable", $vendor_oids, $vendor_mib, NULL, $snmp_flags);
    //print_vars($entity_array);
    //print_debug_vars($vendor_oids);
} elseif (is_device_mib($device, 'DGS-3420-28SC-L2MGMT-MIB')) {
    $vendor_mib  = 'DGS-3420-28SC-L2MGMT-MIB';
    $vendor_oids = snmpwalk_cache_oid($device, "swL2PortSfpInfoTable", $vendor_oids, $vendor_mib, NULL, $snmp_flags);
    //print_vars($entity_array);
    //print_debug_vars($vendor_oids);
} elseif (is_device_mib($device, 'DGS-3420-26SC-L2MGMT-MIB')) {
    $vendor_mib  = 'DGS-3420-26SC-L2MGMT-MIB';
    $vendor_oids = snmpwalk_cache_oid($device, "swL2PortSfpInfoTable", $vendor_oids, $vendor_mib, NULL, $snmp_flags);
    //print_vars($entity_array);
    //print_debug_vars($vendor_oids);
} elseif (is_device_mib($device, 'DGS-3620-28SC-L2MGMT-MIB')) {
    $vendor_mib  = 'DGS-3620-28SC-L2MGMT-MIB';
    $vendor_oids = snmpwalk_cache_oid($device, "swL2PortSfpInfoTable", $vendor_oids, $vendor_mib, NULL, $snmp_flags);
    //print_vars($entity_array);
    //print_debug_vars($vendor_oids);
} elseif (is_device_mib($device, 'DGS-3620-26SC-L2MGMT-MIB')) {
    $vendor_mib  = 'DGS-3620-26SC-L2MGMT-MIB';
    $vendor_oids = snmpwalk_cache_oid($device, "swL2PortSfpInfoTable", $vendor_oids, $vendor_mib, NULL, $snmp_flags);
    //print_vars($entity_array);
    //print_debug_vars($vendor_oids);
} elseif (is_device_mib($device, 'HUAWEI-ENTITY-EXTENT-MIB') &&
          $vendor_oids = snmpwalk_cache_oid($device, "hwEntityOpticalVendorSn", $vendor_oids, 'HUAWEI-ENTITY-EXTENT-MIB', NULL, $snmp_flags)) {
    $vendor_mib  = 'HUAWEI-ENTITY-EXTENT-MIB';
    $vendor_oids = snmpwalk_cache_oid($device, "hwEntityOpticalType",       $vendor_oids, $vendor_mib, NULL, $snmp_flags);
    $vendor_oids = snmpwalk_cache_oid($device, "hwEntityOpticalVenderName", $vendor_oids, $vendor_mib, NULL, $snmp_flags);
    $vendor_oids = snmpwalk_cache_oid($device, "hwEntityOpticalVenderPn",   $vendor_oids, $vendor_mib, NULL, $snmp_flags);
    $vendor_oids = snmpwalk_cache_oid($device, "hwEntityOpticalTransType",  $vendor_oids, $vendor_mib, NULL, $snmp_flags);
    //print_vars($entity_array);
    //print_debug_vars($vendor_oids);
} elseif (is_device_mib($device, 'S5-CHASSIS-MIB')) {
    $vendor_mib  = 'S5-CHASSIS-MIB';
    $vendor_oids = snmpwalk_cache_oid($device, "s5ChasGbicInfoTable", $vendor_oids, $vendor_mib, NULL, $snmp_flags);
}

foreach ($entity_array as $entPhysicalIndex => $entry) {
    unset($entAliasMappingIdentifier);
    foreach ([ 0, 1, 2 ] as $i) {
        if (isset($entity_array[$entPhysicalIndex][$i]['entAliasMappingIdentifier'])) {
            $entAliasMappingIdentifier = $entity_array[$entPhysicalIndex][$i]['entAliasMappingIdentifier'];
            break;
        }
    }
    if (isset($entAliasMappingIdentifier) && str_contains_array($entAliasMappingIdentifier, 'fIndex')) {
        $ifIndex = explode('.', $entAliasMappingIdentifier)[1];
        $entry['ifIndex'] = $ifIndex;
    }

    // Some vendor specific inventory expander's
    if ($vendor_mib === 'ELTEX-MES-PHYSICAL-DESCRIPTION-MIB' && isset($vendor_oids[$entry['ifIndex']])) {
        //$entry = array_merge($entry, $vendor_oids[$entry['ifIndex']]);
        print_debug_vars($vendor_oids[$entry['ifIndex']]);
        /*
          [entPhysicalDescr]                       => string(20) "gigabitethernet1/0/2"
          [entPhysicalVendorType]                  => string(17) "cevPortDCUATMPort"
          [entPhysicalContainedIn]                 => string(8) "68424704"
          [entPhysicalClass]                       => string(4) "port"
          [entPhysicalParentRelPos]                => string(1) "4"
          [entPhysicalName]                        => string(7) "gi1/0/2"
          [entPhysicalHardwareRev]                 => string(0) ""
          [entPhysicalFirmwareRev]                 => string(0) ""
          [entPhysicalSoftwareRev]                 => string(0) ""
          [entPhysicalSerialNum]                   => string(0) ""
          [entPhysicalMfgName]                     => string(0) ""
          [entPhysicalModelName]                   => string(0) ""
          [entPhysicalAlias]                       => string(0) ""
          [entPhysicalAssetID]                     => string(0) ""
          [entPhysicalIsFRU]                       => string(5) "false"
         */
        /*
          [eltPhdTransceiverInfoConnectorType]     => string(2) "sc"
          [eltPhdTransceiverInfoType]              => string(11) "sfp-sfpplus"
          [eltPhdTransceiverInfoComplianceCode]    => string(11) "1000BASE-LX"
          [eltPhdTransceiverInfoWaveLength]        => string(4) "1550"
          [eltPhdTransceiverInfoVendorName]        => string(16) "OptiCin         "
          [eltPhdTransceiverInfoSerialNumber]      => string(16) "PF4D870547      "
          [eltPhdTransceiverInfoFiberDiameterType] => string(6) "fiber9"
          [eltPhdTransceiverInfoTransferDistance]  => string(4) "3000"
          [eltPhdTransceiverInfoDiagnostic]        => string(5) "false"
          [eltPhdTransceiverInfoPartNumber]        => string(16) "SFP-WDM5.03     "
          [eltPhdTransceiverInfoVendorRev]         => string(4) "    "
         */
        // entPhysicalVendorType  -> eltPhdTransceiverInfoType
        // entPhysicalHardwareRev -> eltPhdTransceiverInfoPartNumber
        // entPhysicalFirmwareRev -> eltPhdTransceiverInfoVendorRev
        // entPhysicalSoftwareRev -> ??
        // entPhysicalSerialNum   -> eltPhdTransceiverInfoSerialNumber
        // entPhysicalMfgName     -> eltPhdTransceiverInfoVendorName
        // entPhysicalModelName   -> eltPhdTransceiverInfoComplianceCode
        // entPhysicalAlias       -> ??
        // entPhysicalAssetID     -> ??
        $entry['entPhysicalVendorType']  = trim($vendor_oids[$entry['ifIndex']]['eltPhdTransceiverInfoType']);
        $entry['entPhysicalHardwareRev'] = trim($vendor_oids[$entry['ifIndex']]['eltPhdTransceiverInfoPartNumber']);
        $entry['entPhysicalFirmwareRev'] = trim($vendor_oids[$entry['ifIndex']]['eltPhdTransceiverInfoVendorRev']);
        $entry['entPhysicalSerialNum']   = trim($vendor_oids[$entry['ifIndex']]['eltPhdTransceiverInfoSerialNumber']);
        $entry['entPhysicalMfgName']     = trim($vendor_oids[$entry['ifIndex']]['eltPhdTransceiverInfoVendorName']);
        $entry['entPhysicalModelName']   = trim($vendor_oids[$entry['ifIndex']]['eltPhdTransceiverInfoComplianceCode']);
    } elseif ($vendor_mib === 'S5-CHASSIS-MIB' && isset($vendor_oids[$entry['ifIndex']])) {
        // S5-CHASSIS-MIB::s5ChasGbicInfoIfIndex.49 = INTEGER: 49
        // S5-CHASSIS-MIB::s5ChasGbicInfoGbicType.49 = STRING: 10GB-LR
        // S5-CHASSIS-MIB::s5ChasGbicInfoWavelength.49 = INTEGER: 1310
        // S5-CHASSIS-MIB::s5ChasGbicInfoVendorName.49 = STRING: Extreme Networks
        // S5-CHASSIS-MIB::s5ChasGbicInfoVendorOui.49 = Hex-STRING: 00 90 65
        // S5-CHASSIS-MIB::s5ChasGbicInfoVendorPartNo.49 = STRING: FTLX1471D3BCL-N2
        // S5-CHASSIS-MIB::s5ChasGbicInfoVendorRevision.49 = STRING: A
        // S5-CHASSIS-MIB::s5ChasGbicInfoVendorSerial.49 = STRING: FNSRMYAYP02SB
        // S5-CHASSIS-MIB::s5ChasGbicInfoHwOptions.49 = BITS: E0 rxLoss(0) txFault(1) txDisable(2)
        // S5-CHASSIS-MIB::s5ChasGbicInfoDateCode.49 = STRING: 08/20/2010
        // S5-CHASSIS-MIB::s5ChasGbicInfoCleiCode.49 = STRING: IPU3ADXFAA
        // S5-CHASSIS-MIB::s5ChasGbicInfoProductCode.49 = STRING: AA1403011-E6
        $entry['entPhysicalVendorType']  = trim($vendor_oids[$entry['ifIndex']]['s5ChasGbicInfoGbicType']);
        $entry['entPhysicalHardwareRev'] = trim($vendor_oids[$entry['ifIndex']]['s5ChasGbicInfoVendorRevision']);
        //$entry['entPhysicalFirmwareRev'] = trim($vendor_oids[$entry['ifIndex']]['s5ChasGbicInfoProductCode']);
        $entry['entPhysicalSerialNum']   = trim($vendor_oids[$entry['ifIndex']]['s5ChasGbicInfoVendorSerial']);
        $entry['entPhysicalMfgName']     = trim($vendor_oids[$entry['ifIndex']]['s5ChasGbicInfoVendorName']);
        $entry['entPhysicalModelName']   = trim($vendor_oids[$entry['ifIndex']]['s5ChasGbicInfoVendorPartNo']);
    } elseif (str_starts($vendor_mib, 'DGS') && $entry['entPhysicalModelName'] === 'Fiber Port' &&
              isset($vendor_oids[$entry['entPhysicalParentRelPos']]) &&
              is_numeric($vendor_oids[$entry['entPhysicalParentRelPos']]['swL2PortSfpInfoDateCode'])) {
        //$entry = array_merge($entry, $vendor_oids[$entry['ifIndex']]);
        print_debug_vars($vendor_oids[$entry['entPhysicalParentRelPos']]);
        /*
      [13] => array(
                [entPhysicalDescr]        => string(29) "1000_TXGBIC_COMBO Copper Port"
                [entPhysicalVendorType]   => string(11) "zeroDotZero"
                [entPhysicalContainedIn]  => string(1) "2"
                [entPhysicalClass]        => string(4) "port"
                [entPhysicalParentRelPos] => string(2) "21"
                [entPhysicalName]         => string(7) "Port 21"
                [entPhysicalHardwareRev]  => string(0) ""
                [entPhysicalFirmwareRev]  => string(0) ""
                [entPhysicalSoftwareRev]  => string(0) ""
                [entPhysicalSerialNum]    => string(0) ""
                [entPhysicalMfgName]      => string(0) ""
                [entPhysicalModelName]    => string(11) "Copper Port"
                [entPhysicalAlias]        => string(0) ""
                [entPhysicalAssetID]      => string(0) ""
                [entPhysicalIsFRU]        => string(5) "false"
              )
    ...
      [37] => array(
                [entPhysicalDescr]        => string(28) "1000_TXGBIC_COMBO Fiber Port"
                [entPhysicalVendorType]   => string(11) "zeroDotZero"
                [entPhysicalContainedIn]  => string(1) "2"
                [entPhysicalClass]        => string(4) "port"
                [entPhysicalParentRelPos] => string(2) "21"
                [entPhysicalName]         => string(7) "Port 21"
                [entPhysicalHardwareRev]  => string(0) ""
                [entPhysicalFirmwareRev]  => string(0) ""
                [entPhysicalSoftwareRev]  => string(0) ""
                [entPhysicalSerialNum]    => string(0) ""
                [entPhysicalMfgName]      => string(0) ""
                [entPhysicalModelName]    => string(10) "Fiber Port"
                [entPhysicalAlias]        => string(0) ""
                [entPhysicalAssetID]      => string(0) ""
                [entPhysicalIsFRU]        => string(5) "false"
              )
         */
        /*
      [21] => array(
                [swL2PortSfpInfoPortIndex]   => string(2) "21"
                [swL2PortSfpInfoConnectType] => string(6) "SFP LC"
                [swL2PortSfpInfoVendorName]  => string(3) "OEM"
                [swL2PortSfpInfoVendorPN]    => string(10) "SFP-BX-U31"
                [swL2PortSfpInfoVendorSN]    => string(8) "F10GU046"
                [swL2PortSfpInfoVendorOUI]   => string(6) "0:0:0."
                [swL2PortSfpInfoVendorRev]   => string(3) "1.0"
                [swL2PortSfpInfoDateCode]    => string(6) "090701"
                [swL2PortSfpInfoFiberType]   => string(16) "Single Mode (SM)"
                [swL2PortSfpInfoBaudRate]    => string(4) "1300"
                [swL2PortSfpInfoWavelength]  => string(4) "1310"
              )
         */
        $entry['entPhysicalVendorType']  = trim($vendor_oids[$entry['entPhysicalParentRelPos']]['swL2PortSfpInfoConnectType']);
        $entry['entPhysicalHardwareRev'] = trim($vendor_oids[$entry['entPhysicalParentRelPos']]['swL2PortSfpInfoVendorPN']);
        $entry['entPhysicalFirmwareRev'] = trim($vendor_oids[$entry['entPhysicalParentRelPos']]['swL2PortSfpInfoVendorRev']);
        $entry['entPhysicalSerialNum']   = trim($vendor_oids[$entry['entPhysicalParentRelPos']]['swL2PortSfpInfoVendorSN']);
        $entry['entPhysicalMfgName']     = trim($vendor_oids[$entry['entPhysicalParentRelPos']]['swL2PortSfpInfoVendorName']);
        $entry['entPhysicalModelName']   = trim($vendor_oids[$entry['entPhysicalParentRelPos']]['swL2PortSfpInfoFiberType']);
    } elseif ($vendor_mib === 'HUAWEI-ENTITY-EXTENT-MIB' && isset($vendor_oids[$entPhysicalIndex]) &&
              $vendor_oids[$entPhysicalIndex]['hwEntityOpticalType'] !== 'unknown') {
        //$entry = array_merge($entry, $vendor_oids[$entry['ifIndex']]);
        print_debug_vars($vendor_oids[$entPhysicalIndex]);

        // entPhysicalVendorType  -> hwEntityOpticalType
        // entPhysicalHardwareRev -> ??
        // entPhysicalFirmwareRev -> ??
        // entPhysicalSoftwareRev -> ??
        // entPhysicalSerialNum   -> hwEntityOpticalVendorSn
        // entPhysicalMfgName     -> hwEntityOpticalVenderName
        // entPhysicalModelName   -> hwEntityOpticalVenderPn
        // entPhysicalAlias       -> hwEntityOpticalTransType
        // entPhysicalAssetID     -> ??
        $entry['entPhysicalVendorType'] = trim($vendor_oids[$entPhysicalIndex]['hwEntityOpticalType']);
        //$entry['entPhysicalHardwareRev'] = trim($vendor_oids[$entPhysicalIndex]['']);
        //$entry['entPhysicalFirmwareRev'] = trim($vendor_oids[$entPhysicalIndex]['']);
        $entry['entPhysicalSerialNum'] = trim($vendor_oids[$entPhysicalIndex]['hwEntityOpticalVendorSn']);
        $entry['entPhysicalMfgName']   = trim($vendor_oids[$entPhysicalIndex]['hwEntityOpticalVenderName']);
        $entry['entPhysicalModelName'] = trim($vendor_oids[$entPhysicalIndex]['hwEntityOpticalVenderPn']);
        $entry['entPhysicalAlias']     = trim($vendor_oids[$entPhysicalIndex]['hwEntityOpticalTransType']);
    }

    if ($entry['entPhysicalDescr'] || $entry['entPhysicalName']) {
        discover_inventory($device, $entPhysicalIndex, $entry, $mib);
    }
}

// EOF
