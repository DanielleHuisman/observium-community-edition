<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

if (str_starts($device['sysObjectID'], '.1.3.6.1.4.1.5528'))
{
  // Exclude old netbotz
  return;
}

// Mostly hardware/version detected by sysDescr definitions
if (empty($hardware))
{
  $apc_oids = array(
    'ups'     => array('model' => 'upsBasicIdentModel',            'hwrev' => 'upsAdvIdentFirmwareRevision',       'fwrev' => 'upsAdvIdentFirmwareRevision'),      # UPS
    'ats'     => array('model' => 'atsIdentModelNumber',           'hwrev' => 'atsIdentHardwareRev',               'fwrev' => 'atsIdentFirmwareRev'),              # ATS
    'rPDU'    => array('model' => 'rPDUIdentModelNumber',          'hwrev' => 'rPDUIdentHardwareRev',              'fwrev' => 'rPDUIdentFirmwareRev'),             # PDU
    'rPDU2'   => array('model' => 'rPDU2IdentModelNumber',         'hwrev' => 'rPDU2IdentHardwareRev',             'fwrev' => 'rPDU2IdentFirmwareRev'),            # PDU
    'sPDU'    => array('model' => 'sPDUIdentModelNumber',          'hwrev' => 'sPDUIdentHardwareRev',              'fwrev' => 'sPDUIdentFirmwareRev'),             # Masterswitch/AP9606
    'ems'     => array('model' => 'emsIdentProductNumber',         'hwrev' => 'emsIdentHardwareRev',               'fwrev' => 'emsIdentFirmwareRev'),              # NetBotz 200
    'airIRRC' => array('model' => 'airIRRCUnitIdentModelNumber',   'hwrev' => 'airIRRCUnitIdentHardwareRevision',  'fwrev' => 'airIRRCUnitIdentFirmwareRevision'), # In-Row Chiller
    'airPA'   => array('model' => 'airPAModelNumber',              'hwrev' => 'airPAHardwareRevision',             'fwrev' => 'airPAFirmwareRevision'),            # A/C
    'xPDU'    => array('model' => 'xPDUIdentModelNumber',          'hwrev' => 'xPDUIdentHardwareRev',              'fwrev' => 'xPDUIdentFirmwareAppRev'),          # PDU
    'xATS'    => array('model' => 'xATSIdentModelNumber',          'hwrev' => 'xATSIdentHardwareRev',              'fwrev' => 'xATSIdentFirmwareAppRev'),          # ATS
    'isx'     => array('model' => 'isxModularPduIdentModelNumber', 'hwrev' => 'isxModularPduIdentMonitorCardHardwareRev', 'fwrev' => 'isxModularPduIdentMonitorCardFirmwareAppRev'), # Modular PDU
  );

  // These oids are in APC's "experimental" tree, but there is no "real" UPS equivalent for the firmware versions.
  $AOSrev = snmp_get_oid($device, '.1.3.6.1.4.1.318.1.4.2.4.1.4.1', 'PowerNet-MIB');
  if ($AOSrev)
  {
    $version  = $AOSrev;
    $features = snmp_get_oid($device, '.1.3.6.1.4.1.318.1.4.2.4.1.4.2', 'PowerNet-MIB');
  }

  foreach ($apc_oids as $oid_list)
  {
    if (!$hardware)
    {
      $model = snmp_getnext_oid($device, $oid_list['model'], 'PowerNet-MIB');
      if (empty($model)) { continue; }

      $hardware = trim($model . ' ' . snmp_getnext_oid($device, $oid_list['hwrev'], 'PowerNet-MIB'));

      if (!$AOSrev)
      {
        $version = snmp_getnext_oid($device, $oid_list['fwrev'], 'PowerNet-MIB');
      }

      break;
    }
  }
}

// v3.7.4 -> 3.7.4
if (strlen($version))
{
  $version = ltrim($version, 'v');
}

if (strlen($features) && preg_match('/^v?\d/', $features))
{
  $features = 'App ' . ltrim($features, 'v');
}

// EOF
