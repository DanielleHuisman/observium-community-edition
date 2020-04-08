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

//Sentry4-MIB::st4SystemProductName.0 = STRING: Sentry Smart PDU
//Sentry4-MIB::st4SystemFirmwareVersion.0 = STRING: Version 8.0f
//Sentry4-MIB::st4SystemFirmwareBuildInfo.0 = STRING: Rev 1693, Aug 25 2016, 12:23:13
//Sentry4-MIB::st4UnitID.2 = STRING: B
//Sentry4-MIB::st4UnitName.2 = STRING: Link1
//Sentry4-MIB::st4UnitProductSN.2 = STRING: AFXY0000005
//Sentry4-MIB::st4UnitModel.2 = STRING: SEV-4501C
//Sentry4-MIB::st4UnitAssetTag.2 = STRING:
//Sentry4-MIB::st4UnitType.2 = INTEGER: linkPdu(1)

$data = snmp_get_multi_oid($device, 'st4SystemFirmwareVersion.0', array(), 'Sentry4-MIB');
if (is_array($data[0]))
{
  list(, $version) = explode('Version ', $data[0]['st4SystemFirmwareVersion']);

  $data = snmpwalk_cache_oid($device, 'st4UnitProductSN', array(), 'Sentry4-MIB');
  $data = snmpwalk_cache_oid($device, 'st4UnitModel',       $data, 'Sentry4-MIB');
  $data = current($data);

  $hardware = $data['st4UnitModel'];
  $serial   = $data['st4UnitProductSN'];
}

// EOF
