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

// First case, only port status
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisMemberRole.0 = INTEGER: master(1)
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisMemberMacAddBase.0 = STRING: 88:a2:5e:df:1b:0
//
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortAdminStatus.0."vcp-0.32768" = INTEGER: up(1)
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortAdminStatus.0."vcp-1.32768" = INTEGER: up(1)
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortOperStatus.0."vcp-0.32768" = INTEGER: down(2)
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortOperStatus.0."vcp-1.32768" = INTEGER: down(2)

// Second case, full ports stats
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisMemberRole.0 = INTEGER: master(1)
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisMemberRole.1 = INTEGER: backup(2)
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisMemberMacAddBase.0 = STRING: ec:3e:f7:90:85:e0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisMemberMacAddBase.1 = STRING: ec:3e:f7:90:51:60
//
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortAdminStatus.0."vcp-255/1/0.32768" = INTEGER: up(1)
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortAdminStatus.0."vcp-255/1/1.32768" = INTEGER: up(1)
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortAdminStatus.1."vcp-255/1/0.32768" = INTEGER: up(1)
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortAdminStatus.1."vcp-255/1/1.32768" = INTEGER: up(1)
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortOperStatus.0."vcp-255/1/0.32768" = INTEGER: up(1)
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortOperStatus.0."vcp-255/1/1.32768" = INTEGER: up(1)
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortOperStatus.1."vcp-255/1/0.32768" = INTEGER: up(1)
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortOperStatus.1."vcp-255/1/1.32768" = INTEGER: up(1)
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortInPkts.0."vcp-255/1/0.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortInPkts.0."vcp-255/1/1.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortInPkts.1."vcp-255/1/0.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortInPkts.1."vcp-255/1/1.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortOutPkts.0."vcp-255/1/0.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortOutPkts.0."vcp-255/1/1.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortOutPkts.1."vcp-255/1/0.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortOutPkts.1."vcp-255/1/1.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortInOctets.0."vcp-255/1/0.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortInOctets.0."vcp-255/1/1.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortInOctets.1."vcp-255/1/0.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortInOctets.1."vcp-255/1/1.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortOutOctets.0."vcp-255/1/0.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortOutOctets.0."vcp-255/1/1.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortOutOctets.1."vcp-255/1/0.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortOutOctets.1."vcp-255/1/1.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortInMcasts.0."vcp-255/1/0.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortInMcasts.0."vcp-255/1/1.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortInMcasts.1."vcp-255/1/0.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortInMcasts.1."vcp-255/1/1.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortOutMcasts.0."vcp-255/1/0.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortOutMcasts.0."vcp-255/1/1.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortOutMcasts.1."vcp-255/1/0.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortOutMcasts.1."vcp-255/1/1.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortInPkts1secRate.0."vcp-255/1/0.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortInPkts1secRate.0."vcp-255/1/1.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortInPkts1secRate.1."vcp-255/1/0.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortInPkts1secRate.1."vcp-255/1/1.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortOutPkts1secRate.0."vcp-255/1/0.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortOutPkts1secRate.0."vcp-255/1/1.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortOutPkts1secRate.1."vcp-255/1/0.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortOutPkts1secRate.1."vcp-255/1/1.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortInOctets1secRate.0."vcp-255/1/0.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortInOctets1secRate.0."vcp-255/1/1.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortInOctets1secRate.1."vcp-255/1/0.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortInOctets1secRate.1."vcp-255/1/1.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortOutOctets1secRate.0."vcp-255/1/0.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortOutOctets1secRate.0."vcp-255/1/1.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortOutOctets1secRate.1."vcp-255/1/0.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortOutOctets1secRate.1."vcp-255/1/1.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortCarrierTrans.0."vcp-255/1/0.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortCarrierTrans.0."vcp-255/1/1.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortCarrierTrans.1."vcp-255/1/0.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortCarrierTrans.1."vcp-255/1/1.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortInCRCAlignErrors.0."vcp-255/1/0.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortInCRCAlignErrors.0."vcp-255/1/1.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortInCRCAlignErrors.1."vcp-255/1/0.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortInCRCAlignErrors.1."vcp-255/1/1.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortUndersizePkts.0."vcp-255/1/0.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortUndersizePkts.0."vcp-255/1/1.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortUndersizePkts.1."vcp-255/1/0.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortUndersizePkts.1."vcp-255/1/1.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortCollisions.0."vcp-255/1/0.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortCollisions.0."vcp-255/1/1.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortCollisions.1."vcp-255/1/0.32768" = Counter64: 0
// JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortCollisions.1."vcp-255/1/1.32768" = Counter64: 0

$jnxVirtualChassisMember = snmp_cache_table($device, 'jnxVirtualChassisMemberTable', [], 'JUNIPER-VIRTUALCHASSIS-MIB');
print_debug_vars($jnxVirtualChassisMember);
if (!snmp_status() || count($jnxVirtualChassisMember) < 1) {
    return;
}

//$jnxVirtualChassisPort = snmpwalk_cache_twopart_oid($device, 'jnxVirtualChassisPortTable', [], 'JUNIPER-VIRTUALCHASSIS-MIB', NULL, OBS_SNMP_ALL_MULTILINE);
//print_debug_vars($jnxVirtualChassisPort);

//$mib_config = &$config['mibs'][$mib]['ports']['oids']; // Attach MIB options/translations
//print_debug_vars($mib_config);

foreach ($jnxVirtualChassisMember as $member => $chassis) {
    if ($chassis['jnxVirtualChassisMemberRole'] === 'master') {
        // Skip master chassis (which already polled by IF-MIB)
        print_debug("Skip JUNIPER-VIRTUALCHASSIS-MIB::jnxVirtualChassisPortTable for master");
        continue;
    }

    $jnxVirtualChassisPort = snmpwalk_cache_twopart_oid($device, 'jnxVirtualChassisPortOperStatus.' . $member, [], 'JUNIPER-VIRTUALCHASSIS-MIB', NULL, OBS_SNMP_ALL_MULTILINE);
    print_debug_vars($jnxVirtualChassisPort);

    foreach ($jnxVirtualChassisPort[$member] as $jnxVirtualChassisPortName => $port) {
        $ifDescr = $jnxVirtualChassisPortName . ":vc$member";
        // Generate numeric ifIndex based on port name
        $ifIndex = string_to_id($ifDescr);

        // Append member options
        $port = array_merge($port, $chassis);

        $port_stats[$ifIndex]['ifDescr']      = $ifDescr;
        $port_stats[$ifIndex]['ifName']       = $ifDescr;
        $port_stats[$ifIndex]['ifOperStatus'] = $port['jnxVirtualChassisPortOperStatus'];
        $port_stats[$ifIndex]['ifType']       = str_contains($ifDescr, '.') ? 'propVirtual' : 'other';
    }
}

// EOF