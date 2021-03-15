<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2020 Observium Limited
 *
 */

$snmpdata = snmp_get_multi_oid($device, 'sysUpTime.0 sysLocation.0 sysContact.0 sysName.0', array(), 'SNMPv2-MIB', NULL, OBS_SNMP_ALL_UTF8);
$polled   = round($GLOBALS['exec_status']['endtime']);
if (is_array($snmpdata[0]))
{
  $poll_device = array_merge($poll_device, $snmpdata[0]);

  if (isset($snmpdata[0]['sysUpTime']))
  {
    // SNMPv2-MIB::sysUpTime.0 = Timeticks: (2542831) 7:03:48.31
    $poll_device['sysUpTime'] = timeticks_to_sec($snmpdata[0]['sysUpTime']);
  }

  $poll_device['sysName_SNMPv2'] = $poll_device['sysName']; // Store original sysName for devices who store hardware in this Oid
}

$sysDescr = snmp_get_oid($device, 'sysDescr.0', 'SNMPv2-MIB');
if ($GLOBALS['snmp_status'] || $GLOBALS['snmp_error_code'] === 1) // Allow empty response for sysDescr (not timeouts)
{
  $poll_device['sysDescr']   = $sysDescr;
}

$poll_device['sysObjectID']  = snmp_cache_sysObjectID($device);
$poll_device['snmpEngineID'] = snmp_cache_snmpEngineID($device);

unset($snmpdata, $sysDescr);

//EOF
