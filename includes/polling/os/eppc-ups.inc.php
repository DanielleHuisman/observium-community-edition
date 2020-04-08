<?php

/**
 * Observium
 *
 * This file is part of Observium.
 *
 * @package observium
 * @subpackage poller
 * @copyright (C) 2006-2015 Adam Armstrong
 *
 */

# PowerWalker/BlueWalker UPS (Tested with BlueWalked VFI 2000 LCD (EPPC-MIB) sysDescr.0 = STRING: Network Management Card for UPS

if ($poll_device['sysObjectID'] == '.1.3.6.1.4.1.935.10.1') // BlueWalker
{
  if ($poll_device['sysDescr'] == 'Network Management Card for UPS')
  {
    $hardware = snmp_get($device, 'upsEIdentityManufacturer.0', '-OQv', 'EPPC-MIB') . ' (' .
                snmp_get($device, 'upsESystemConfigOutputVA.0', '-OQv', 'EPPC-MIB') . 'VA ' . snmp_get($device, 'upsEIdentityModel.0', '-OQv', 'EPPC-MIB') . ')';
    $upsEIdentityDescription = snmp_get($device, 'upsEIdentityDescription.0', '-OQv', 'EPPC-MIB');
    $version = $upsEIdentityDescription + ' UPS: ' .
               snmp_get($device, 'upsEIdentityUPSFirmwareVerison.0', '-OQv', 'EPPC-MIB') . ' Firmware: ' . snmp_get($device, 'upsIdentAgentSoftwareVersion.0', '-OQv', 'EPPC-MIB');
    $status = snmp_get($device, 'upsESystemStatus.0', '-OQv', 'EPPC-MIB') . ' ' . snmp_get($device, 'upsEBatteryTestResult.0', '-OQv', 'EPPC-MIB');

    $features = 'Status: ' . strtoupper($status);
  }
  else {
    $hardware = 'EPPC - Unknown NMC Card';
  }
}

// EOF
