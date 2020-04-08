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

// APC Web/SNMP Management Card (MB:v3.9.2 PF:v3.7.4 PN:apc_hw02_aos_374.bin AF1:v3.7.4 AN1:apc_hw02_rpdu_374.bin MN:AP7920 HR:B2 SN: ZA0619025106 MD:05/08/2006)
// APC Web/SNMP Management Card (MB:v3.8.6 PF:v3.5.7 PN:apc_hw03_aos_357.bin AF1:v3.5.6 AN1:apc_hw03_mem_356.bin MN:AP9340 HR:05 SN: ZA0723023958 MD:06/11/2007)
// APC Environmental Manager (MB:v3.8.0 PF:v3.0.3 PN:apc_hw03_aos_303.bin AF1:v3.0.4 AN1:apc_hw03_mem_304.bin MN:AP9340 HR:05 SN: IA0711004586 MD:03/16/2007)
// APC Web/SNMP Management Card (MB:v3.9.2 PF:v3.7.3 PN:apc_hw02_aos_373.bin AF1:v3.7.2 AN1:apc_hw02_sumx_372.bin MN:AP9617 HR:A10 SN: JA0412054242 MD:03/21/2004) (Embedded PowerNet SNMP Agent SW v2.2 compatible)
// APC Embedded PowerNet SNMP Agent (FW v3.0.0 SW v2.2.0.a, HW B2, MOD: AP9605, Mfg: 09/10/1997, SN: WA0711004586)
// APC Web/SNMP Management Card (MB:v4.0.6 PF:v3.7.5 PN:apc_hw03_aos_375.bin AF1:v3.7.5 AN1:apc_hw03_nb200_375.bin MN:NBRK0200 HR:05 SN: ZA1220018095 MD:05/11/2012)

if (str_starts($device['sysObjectID'], '.1.3.6.1.4.1.5528'))
{
  // Exclude old netbotz
  return;
}

$apc_pattern = '/^APC .*\(MB:.* PF:(.*) PN:.* AF1:(.*) AN1:.* MN:(.*) HR:(.*) SN:(.*) MD:.*/';
if (preg_match($apc_pattern, $poll_device['sysDescr'], $matches) && !str_icontains($poll_device['sysDescr'], 'Embedded'))
{
  $version  = $matches[1];
  $features = 'App ' . $matches[2];
  $hardware = $matches[3] . ' ' . $matches[4];
  $serial   = trim($matches[5]);
} else {
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
    $APPrev   = snmp_get_oid($device, '.1.3.6.1.4.1.318.1.4.2.4.1.4.2', 'PowerNet-MIB');
    $version  = $AOSrev;
    $features = "App $APPrev";
  }

  foreach ($apc_oids as $oid_list)
  {
    if (!$hardware)
    {
      $hardware = trim(snmp_getnext_oid($device, $oid_list['model'], 'PowerNet-MIB') . ' ' .
                       snmp_getnext_oid($device, $oid_list['hwrev'], 'PowerNet-MIB'));
      if ($hardware == ' ') { unset($hardware); }

      if (!$AOSrev) { $version = snmp_getnext_oid($device, $oid_list['fwrev'], 'PowerNet-MIB'); }

      break;
    }
  }
}

// v3.7.4 -> 3.7.4
if (strlen($version))
{
  $version = ltrim($version, 'v');
}

// EOF
