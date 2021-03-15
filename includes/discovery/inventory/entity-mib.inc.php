<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2020 Observium Limited
 *
 */

echo("ENTITY-MIB ");

$entity_array = snmpwalk_cache_oid($device, "entPhysicalEntry", array(), snmp_mib_entity_vendortype($device, 'ENTITY-MIB'));
if (snmp_status())
{
  $entity_array = snmpwalk_cache_twopart_oid($device, "entAliasMappingIdentifier", $entity_array, 'ENTITY-MIB:IF-MIB');

  $GLOBALS['cache']['snmp']['ENTITY-MIB'][$device['device_id']] = $entity_array; // Cache this array for sensors discovery (see in cisco-entity-sensor-mib or entity-sensor-mib)

  // Some vendor specific inventory expander's
  $vendor_oids = [];
  if (is_device_mib($device, 'ELTEX-MES-PHYSICAL-DESCRIPTION-MIB'))
  {
    $vendor_oids = snmpwalk_cache_oid($device, "eltPhdTransceiverInfoTable", $vendor_oids, 'ELTEX-MES-PHYSICAL-DESCRIPTION-MIB');
    //print_vars($entity_array);
    //print_debug_vars($vendor_oids);
  }

  foreach ($entity_array as $entPhysicalIndex => $entry)
  {
    unset($entAliasMappingIdentifier);
    foreach (array(0, 1, 2) as $i)
    {
      if (isset($entity_array[$entPhysicalIndex][$i]['entAliasMappingIdentifier']))
      {
        $entAliasMappingIdentifier = $entity_array[$entPhysicalIndex][$i]['entAliasMappingIdentifier'];
        break;
      }
    }
    if (isset($entAliasMappingIdentifier) && str_exists($entAliasMappingIdentifier, 'fIndex'))
    {
      list(, $ifIndex) = explode('.', $entAliasMappingIdentifier);
      $entry['ifIndex'] = $ifIndex;
    }

    // Some vendor specific inventory expander's
    if (is_device_mib($device, 'ELTEX-MES-PHYSICAL-DESCRIPTION-MIB') && isset($vendor_oids[$entry['ifIndex']]))
    {
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
    }

    if (isset($config['rewrites']['entPhysicalVendorTypes'][$entry['entPhysicalVendorType']]) && !$entry['entPhysicalModelName'])
    {
      $entry['entPhysicalModelName'] = $config['rewrites']['entPhysicalVendorTypes'][$entry['entPhysicalVendorType']];
    }

    if ($entry['entPhysicalDescr'] || $entry['entPhysicalName'])
    {
      discover_inventory($device, $entPhysicalIndex, $entry, $mib);
    }
  }
}

// EOF
