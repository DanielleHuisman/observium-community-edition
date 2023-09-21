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

if (!$port_stats_count) {
    return;
}

echo("JUNIPER-VIRTUALCHASSIS-MIB ");

$jnxVirtualChassisMember = snmp_cache_table($device, 'jnxVirtualChassisMemberRole', [], 'JUNIPER-VIRTUALCHASSIS-MIB');
if (!snmp_status() ||
    (count($jnxVirtualChassisMember) === 1 && $jnxVirtualChassisMember[0]['jnxVirtualChassisMemberRole'] === 'master')) // Skip only master
{
    return;
}
$jnxVirtualChassisMember = snmpwalk_cache_oid($device, 'jnxVirtualChassisMemberMacAddBase', $jnxVirtualChassisMember, 'JUNIPER-VIRTUALCHASSIS-MIB');
print_debug_vars($jnxVirtualChassisMember);

$jnx_oids = [
  'jnxVirtualChassisPortAdminStatus', 'jnxVirtualChassisPortOperStatus',
  'jnxVirtualChassisPortInOctets', 'jnxVirtualChassisPortOutOctets',
  'jnxVirtualChassisPortInPkts', 'jnxVirtualChassisPortOutPkts',
  'jnxVirtualChassisPortInMcasts', 'jnxVirtualChassisPortOutMcasts',
  // ifErrors
  'jnxVirtualChassisPortCarrierTrans', 'jnxVirtualChassisPortInCRCAlignErrors',
  'jnxVirtualChassisPortUndersizePkts', 'jnxVirtualChassisPortCollisions',
];

foreach ($jnxVirtualChassisMember as $member => $chassis) {
    if ($chassis['jnxVirtualChassisMemberRole'] === 'master') {
        // Skip master chassis (which already polled by IF-MIB)
        print_debug("Skip JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortTable for master");
        continue;
    }

    $jnxVirtualChassisPort = [];
    foreach ($jnx_oids as $oid) {
        $jnxVirtualChassisPort = snmpwalk_cache_twopart_oid($device, $oid . '.' . $member, $jnxVirtualChassisPort, 'JUNIPER-VIRTUALCHASSIS-MIB', NULL, OBS_SNMP_ALL_MULTILINE);
    }
    print_debug_vars($jnxVirtualChassisPort[$member]);

    foreach ($jnxVirtualChassisPort[$member] as $jnxVirtualChassisPortName => $port) {
        $ifDescr = $jnxVirtualChassisPortName . ":vc$member";
        // Generate numeric ifIndex based on port name
        $ifIndex = string_to_id($ifDescr);

        // Append member options
        $port = array_merge($port, $chassis);

        $port_stats[$ifIndex]['ifDescr']       = $ifDescr;
        $port_stats[$ifIndex]['ifName']        = $ifDescr;
        $port_stats[$ifIndex]['ifAdminStatus'] = $port['jnxVirtualChassisPortAdminStatus'];
        $port_stats[$ifIndex]['ifOperStatus']  = $port['jnxVirtualChassisPortOperStatus'];
        $port_stats[$ifIndex]['ifPhysAddress'] = $port['jnxVirtualChassisMemberMacAddBase'];
        $port_stats[$ifIndex]['ifType']        = str_contains($ifDescr, '.') ? 'propVirtual' : 'other';

        // Stats
        $port_stats[$ifIndex]['ifHCInOctets']         = $port['jnxVirtualChassisPortInOctets'];
        $port_stats[$ifIndex]['ifHCOutOctets']        = $port['jnxVirtualChassisPortOutOctets'];
        $port_stats[$ifIndex]['ifHCInUcastPkts']      = $port['jnxVirtualChassisPortInPkts'];
        $port_stats[$ifIndex]['ifHCOutUcastPkts']     = $port['jnxVirtualChassisPortOutPkts'];
        $port_stats[$ifIndex]['ifHCInMulticastPkts']  = $port['jnxVirtualChassisPortInMcasts'];
        $port_stats[$ifIndex]['ifHCOutMulticastPkts'] = $port['jnxVirtualChassisPortOutMcasts'];
        /// FIXME. I not sure about error Oids
        $port_stats[$ifIndex]['ifInErrors']  = $port['jnxVirtualChassisPortInCRCAlignErrors'];
        $port_stats[$ifIndex]['ifInErrors']  = int_add($port_stats[$ifIndex]['ifInErrors'], $port['jnxVirtualChassisPortUndersizePkts']);
        $port_stats[$ifIndex]['ifInErrors']  = int_add($port_stats[$ifIndex]['ifInErrors'], $port['jnxVirtualChassisPortCollisions']);
        $port_stats[$ifIndex]['ifOutErrors'] = $port['jnxVirtualChassisPortCarrierTrans'];
    }
}

// EOF
