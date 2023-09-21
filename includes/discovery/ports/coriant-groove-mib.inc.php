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

// this is eth100g only, note that fc8gTable, fc16gTable, eth10gTable, eth40gTable, eth400gtable all exist too!

//eth100gTable
$entries = [];
$entries = snmpwalk_cache_oid($device, 'eth100gTable', $entries, $mib);

print_debug_vars($entries);

foreach ($entries as $port_oid_suffix => $port) {
    $ifIndex = $port_oid_suffix;

    // basics
    $port_stats[$ifIndex]['ifDescr']       = $port['eth100gAliasName'];
    $port_stats[$ifIndex]['ifName']        = $port['eth100gAliasName'];
    $port_stats[$ifIndex]['ifAlias']       = $port['eth100gServiceLabel'];
    $port_stats[$ifIndex]['ifOperStatus']  = $port['eth100gOperStatus'];
    $port_stats[$ifIndex]['ifAdminStatus'] = $port['eth100gAdminStatus'];
    $port_stats[$ifIndex]['ifType']        = 'ethernetCsmacd';        // can we do better than hard coding?

}

// eth400gtable
$entries = [];
$entries = snmpwalk_cache_oid($device, 'eth400gTable', $entries, $mib);

print_debug_vars($entries);

foreach ($entries as $port_oid_suffix => $port) {
    $ifIndex = $port_oid_suffix;

    // basics
    $port_stats[$ifIndex]['ifDescr']       = $port['eth400gAliasName'];
    $port_stats[$ifIndex]['ifName']        = $port['eth400gAliasName'];
    $port_stats[$ifIndex]['ifAlias']       = $port['eth400gServiceLabel'];
    $port_stats[$ifIndex]['ifOperStatus']  = $port['eth400gOperStatus'];
    $port_stats[$ifIndex]['ifAdminStatus'] = $port['eth400gAdminStatus'];
    $port_stats[$ifIndex]['ifType']        = 'ethernetCsmacd';        // can we do better than hard coding?

}


print_debug_vars($port_stats);


// EOF
