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

// this is eth100g only, note that fc8gTable, fc16gTable, eth10gTable, eth40gTable, eth400gtable all exist too!

/*
.1.3.6.1.4.1.42229.1.2.4.1.9 - eth100gTable for a single port (1/1/6)

CORIANT-GROOVE-MIB::eth100gEthFecType.1.1.0.6.0 = INTEGER: auto(3)
CORIANT-GROOVE-MIB::eth100gEthFecTypeState.1.1.0.6.0 = INTEGER: disabled(2)
CORIANT-GROOVE-MIB::eth100gTransmitInterpacketgap.1.1.0.6.0 = Gauge32: 8
CORIANT-GROOVE-MIB::eth100gGfpPayloadFcs.1.1.0.6.0 = INTEGER: disabled(2)
CORIANT-GROOVE-MIB::eth100gMappingMode.1.1.0.6.0 = INTEGER: gmp(1)
CORIANT-GROOVE-MIB::eth100gAdminStatus.1.1.0.6.0 = INTEGER: up(1)
CORIANT-GROOVE-MIB::eth100gOperStatus.1.1.0.6.0 = INTEGER: down(2)
CORIANT-GROOVE-MIB::eth100gAvailStatus.1.1.0.6.0 = BITS: 10 00 00 00 lowerLayerDown(3)
CORIANT-GROOVE-MIB::eth100gAliasName.1.1.0.6.0 = STRING: "100gbe-1/1/6"
CORIANT-GROOVE-MIB::eth100gClientShutdown.1.1.0.6.0 = INTEGER: no(2)
CORIANT-GROOVE-MIB::eth100gClientShutdownHoldoffTimer.1.1.0.6.0 = Gauge32: 0
CORIANT-GROOVE-MIB::eth100gNearEndAls.1.1.0.6.0 = INTEGER: no(2)
CORIANT-GROOVE-MIB::eth100gAlsDegradeMode.1.1.0.6.0 = INTEGER: disabled(2)
CORIANT-GROOVE-MIB::eth100gLoopbackEnable.1.1.0.6.0 = INTEGER: disabled(2)
CORIANT-GROOVE-MIB::eth100gLoopbackType.1.1.0.6.0 = INTEGER: none(0)
CORIANT-GROOVE-MIB::eth100gTestSignalType.1.1.0.6.0 = INTEGER: none(0)
CORIANT-GROOVE-MIB::eth100gTestSignalEnable.1.1.0.6.0 = INTEGER: none(0)
CORIANT-GROOVE-MIB::eth100gServiceLabel.1.1.0.6.0 = ""
CORIANT-GROOVE-MIB::eth100gLldpStatusIf.1.1.0.6.0 = INTEGER: disabled(4)
CORIANT-GROOVE-MIB::eth100gHoldoffSignal.1.1.0.6.0 = INTEGER: no(2)
CORIANT-GROOVE-MIB::eth100gManagedBy.1.1.0.6.0 = INTEGER: system(1)

.1.3.6.1.4.1.42229.1.2.4.1.9 - eth100gStatisticsTable for a single port (1/1/6)

CORIANT-GROOVE-MIB::eth100gStatisticsEntryLastClear.1.1.0.6.0 = STRING: "0000-01-01T00:00:00.000Z"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryLossOfSignalSeconds.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryBitErrorFec.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryUncorrectedBlockErrorFec.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryInSymbolErrors.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryInDropEvents.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryInOctets.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryInPackets.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryInBroadcastPackets.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryInMulticastPackets.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryInCrcAlignErrors.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryInUndersizePackets.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryInOversizePackets.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryInFragments.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryInJabbers.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryInPackets64octets.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryInPackets65to127octets.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryInPackets128to255octets.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryInPackets256to511octets.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryInPackets512to1023octets.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryInPackets1024to1518octets.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryOutSymbolErrors.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryOutDropEvents.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryOutOctets.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryOutPackets.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryOutBroadcastPackets.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryOutMulticastPackets.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryOutCrcAlignErrors.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryOutUndersizePackets.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryOutOversizePackets.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryOutFragments.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryOutJabbers.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryOutPackets64octets.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryOutPackets65to127octets.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryOutPackets128to255octets.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryOutPackets256to511octets.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryOutPackets512to1023octets.1.1.0.6.0 = STRING: "0"
CORIANT-GROOVE-MIB::eth100gStatisticsEntryOutPackets1024to1518octets.1.1.0.6.0 = STRING: "0"

*/

$mib = 'CORIANT-GROOVE-MIB';

// eth100gTable
$entries = [];
$entries = snmpwalk_cache_oid($device, 'eth100gTable', $entries, $mib);
$entries = snmpwalk_cache_oid($device, 'eth100gStatisticsTable', $entries, $mib);

print_debug_vars($entries);

foreach ($entries as $port_oid_suffix => $port) {
    $ifIndex = $port_oid_suffix;
    $entry   = isset($entries[$ifIndex]) ? $entries[$ifIndex] : []; // FIXME. WTF?

    // basics
    $port_stats[$ifIndex]['ifDescr']       = $entry['eth100gAliasName'];
    $port_stats[$ifIndex]['ifName']        = $entry['eth100gAliasName'];
    $port_stats[$ifIndex]['ifSpeed']       = '100000000000';
    $port_stats[$ifIndex]['ifAlias']       = $entry['eth100gServiceLabel'];
    $port_stats[$ifIndex]['ifOperStatus']  = $entry['eth100gOperStatus'];
    $port_stats[$ifIndex]['ifAdminStatus'] = $entry['eth100gAdminStatus'];
    $port_stats[$ifIndex]['ifType']        = 'ethernetCsmacd';        // can we do better than hard coding?

    // stats
    $port_stats[$ifIndex]['ifInOctets']  = $entry['eth100gStatisticsEntryInOctets'];
    $port_stats[$ifIndex]['ifOutOctets'] = $entry['eth100gStatisticsEntryOutOctets'];

    $port_stats[$ifIndex]['ifInUcastPkts']  = $entry['eth100gStatisticsEntryInPackets'];
    $port_stats[$ifIndex]['ifOutUcastPkts'] = $entry['eth100gStatisticsEntryOutPackets'];

    $port_stats[$ifIndex]['ifInBroadcastPkts']  = $entry['eth100gStatisticsEntryInBroadcastPackets'];
    $port_stats[$ifIndex]['ifOutBroadcastPkts'] = $entry['eth100gStatisticsEntryOutBroadcastPackets'];

    $port_stats[$ifIndex]['ifInMulticastPkts']  = $entry['eth100gStatisticsEntryInMulticastPackets'];
    $port_stats[$ifIndex]['ifOutMulticastPkts'] = $entry['eth100gStatisticsEntryOutMulticastPackets'];

    $port_stats[$ifIndex]['ifInDiscards']  = $entry['eth100gStatisticsEntryInDropEvents'];
    $port_stats[$ifIndex]['ifOutDiscards'] = $entry['eth100gStatisticsEntryOutDropEvents'];

    $port_stats[$ifIndex]['ifInErrors']  = $entry['eth100gStatisticsEntryInCrcAlignErrors'];
    $port_stats[$ifIndex]['ifOutErrors'] = $entry['eth100gStatisticsEntryOutCrcAlignErrors'];

}


// eth400gTable
$entries = [];
$entries = snmpwalk_cache_oid($device, 'eth400gTable', $entries, $mib);
$entries = snmpwalk_cache_oid($device, 'eth400gStatisticsTable', $entries, $mib);

print_debug_vars($entries);

foreach ($entries as $port_oid_suffix => $port) {
    $ifIndex = $port_oid_suffix;
    $entry   = isset($entries[$ifIndex]) ? $entries[$ifIndex] : []; // FIXME. WTF?

    // basics
    $port_stats[$ifIndex]['ifDescr']       = $entry['eth400gAliasName'];
    $port_stats[$ifIndex]['ifName']        = $entry['eth400gAliasName'];
    $port_stats[$ifIndex]['ifSpeed']       = '400000000000';
    $port_stats[$ifIndex]['ifAlias']       = $entry['eth400gServiceLabel'];
    $port_stats[$ifIndex]['ifOperStatus']  = $entry['eth400gOperStatus'];
    $port_stats[$ifIndex]['ifAdminStatus'] = $entry['eth400gAdminStatus'];
    $port_stats[$ifIndex]['ifType']        = 'ethernetCsmacd';        // can we do better than hard coding?

    // stats
    $port_stats[$ifIndex]['ifInOctets']  = $entry['eth400gStatisticsEntryInOctets'];
    $port_stats[$ifIndex]['ifOutOctets'] = $entry['eth400gStatisticsEntryOutOctets'];

    $port_stats[$ifIndex]['ifInUcastPkts']  = $entry['eth400gStatisticsEntryInPackets'];
    $port_stats[$ifIndex]['ifOutUcastPkts'] = $entry['eth400gStatisticsEntryOutPackets'];

    $port_stats[$ifIndex]['ifInBroadcastPkts']  = $entry['eth400gStatisticsEntryInBroadcastPackets'];
    $port_stats[$ifIndex]['ifOutBroadcastPkts'] = $entry['eth400gStatisticsEntryOutBroadcastPackets'];

    $port_stats[$ifIndex]['ifInMulticastPkts']  = $entry['eth400gStatisticsEntryInMulticastPackets'];
    $port_stats[$ifIndex]['ifOutMulticastPkts'] = $entry['eth400gStatisticsEntryOutMulticastPackets'];

    $port_stats[$ifIndex]['ifInDiscards']  = $entry['eth400gStatisticsEntryInDropEvents'];
    $port_stats[$ifIndex]['ifOutDiscards'] = $entry['eth400gStatisticsEntryOutDropEvents'];

    $port_stats[$ifIndex]['ifInErrors']  = $entry['eth400gStatisticsEntryInCrcAlignErrors'];
    $port_stats[$ifIndex]['ifOutErrors'] = $entry['eth400gStatisticsEntryOutCrcAlignErrors'];

}

// EOF
