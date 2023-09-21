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

// Derp devices....... exist ifXEntry (without ifEntry)

$index = array_key_first($port_stats);
if (!isset($port_stats[$index]['ifType'])) {
    // Ethernet ports:

    // IPE-SYSTEM-MIB::ipeCfgPortEtherEnable.31 = INTEGER: enabled(1)
    // IPE-SYSTEM-MIB::ipeCfgPortEtherAutoNeg.31 = INTEGER: enabled(1)
    // IPE-SYSTEM-MIB::ipeCfgPortEtherSpecialFilter.31 = INTEGER: false(2)
    // IPE-SYSTEM-MIB::ipeCfgPortEtherLldpMode.31 = INTEGER: standardMode(1)
    // IPE-SYSTEM-MIB::ipeCfgPortEtherEntry.7.31 = INTEGER: 1

    // IPE-SYSTEM-MIB::ipeStsPortEtherLinkUp.31 = INTEGER: 1
    // IPE-SYSTEM-MIB::ipeStsPortEtherSpeed.31 = INTEGER: 100
    // IPE-SYSTEM-MIB::ipeStsPortEtherDuplex.31 = INTEGER: 2
    // IPE-SYSTEM-MIB::ipeStsPortEtherFlowControl.31 = INTEGER: 0

    // IPE-COMMON-MIB::invMacAddress.31 = STRING: 74:3a:65:5d:b6:c0

    // ODU ports:
    // IPE-COMMON-MIB::lof.16842752 = INTEGER: cleared(1)
    // IPE-COMMON-MIB::frameID.16842752 = INTEGER: cleared(1)
    // IPE-COMMON-MIB::highBERAlarm.16842752 = INTEGER: cleared(1)
    // IPE-COMMON-MIB::lowBERAlarm.16842752 = INTEGER: cleared(1)
    // IPE-COMMON-MIB::earlyWarningAlarm.16842752 = INTEGER: cleared(1)
    // IPE-COMMON-MIB::modAlarm.16842752 = INTEGER: cleared(1)
    // IPE-COMMON-MIB::ifCableShortAlarm.16842752 = INTEGER: cleared(1)
    // IPE-COMMON-MIB::atpcPowerMode.16842752 = INTEGER: active(2)
    // IPE-COMMON-MIB::inPhaseStatus.16842752 = INTEGER: outphase(2)
    // IPE-COMMON-MIB::amrRangeMismatch.16842752 = INTEGER: cleared(1)
    // IPE-COMMON-MIB::txModulation.16842752 = INTEGER: qpsk(1)
    // IPE-COMMON-MIB::rxModulation.16842752 = INTEGER: qpsk(1)
    // IPE-COMMON-MIB::l2SyncLossAlarm.16842752 = INTEGER: cleared(1)
    // IPE-COMMON-MIB::rdiAlarm.16842752 = INTEGER: cleared(1)
    // IPE-COMMON-MIB::uaeAlarm.16842752 = INTEGER: cleared(1)
    // IPE-COMMON-MIB::unlocked.16842752 = INTEGER: cleared(1)
    // IPE-COMMON-MIB::tempAlarm.16842752 = INTEGER: cleared(1)
    // IPE-COMMON-MIB::tdmRangeMismatch.16842752 = INTEGER: cleared(1)
    // IPE-COMMON-MIB::modemPsOff.16842752 = INTEGER: on(2)

    // SFP ports
    // IPE-COMMON-MIB::asETHPortInterfaceType.142802944 = INTEGER: invalid(0)
    // IPE-COMMON-MIB::asETHPortSpeedDuplex.142802944 = INTEGER: invalid(0)
    // IPE-COMMON-MIB::asETHPortFlowControl.142802944 = INTEGER: disable(1)
    // IPE-COMMON-MIB::asETHPortMDIMDIX.142802944 = INTEGER: invalid(0)
    // IPE-COMMON-MIB::asETHPortLinkStatus.142802944 = INTEGER: cleared(1)
    // IPE-COMMON-MIB::asETHPortAdminStatus.142802944 = INTEGER: normal(1)
    // IPE-COMMON-MIB::asETHPortSFPEquip.142802944 = INTEGER: cleared(1)
    // IPE-COMMON-MIB::asETHPortSFPLos.142802944 = INTEGER: cleared(1)
    // IPE-COMMON-MIB::asETHPortSFPTxError.142802944 = INTEGER: cleared(1)
    // IPE-COMMON-MIB::asETHPortSFPTypeMismatch.142802944 = INTEGER: cleared(1)
    // IPE-COMMON-MIB::asETHPortOperStatus.142802944 = INTEGER: linkDown(1)
    // IPE-COMMON-MIB::asETHPortLlfOamReceived.142802944 = INTEGER: normal(1)

    $entries = [];
    //$entries = snmpwalk_cache_oid($device, 'ipeCfgPortEtherEnable',  $entries, 'IPE-SYSTEM-MIB');
    $entries = snmpwalk_cache_oid($device, 'ipeCfgPortModemEnable', $entries, 'IPE-SYSTEM-MIB');
    $entries = snmpwalk_cache_oid($device, 'ipeStsPortEtherLinkUp', $entries, 'IPE-SYSTEM-MIB');
    //$entries = snmpwalk_cache_oid($device, 'ipeStsPortEtherDuplex',  $entries, 'IPE-SYSTEM-MIB');
    $entries = snmpwalk_cache_oid($device, 'invMacAddress', $entries, 'IPE-COMMON-MIB');

    $entries = snmpwalk_cache_oid($device, 'atpcPowerMode', $entries, 'IPE-COMMON-MIB');

    $entries = snmpwalk_cache_oid($device, 'asETHPortInterfaceType', $entries, 'IPE-COMMON-MIB');
    $entries = snmpwalk_cache_oid($device, 'asETHPortOperStatus', $entries, 'IPE-COMMON-MIB');
    print_debug_vars($entries);

    foreach ($port_stats as $ifIndex => $port) {
        $entry = isset($entries[$ifIndex]) ? $entries[$ifIndex] : [];

        // ifType
        if (isset($entry['ipeStsPortEtherLinkUp'], $entry['invMacAddress']) ||
            str_starts($port['ifName'], ['eth', 'bcm'])) {
            $port_stats[$ifIndex]['ifType'] = 'ethernetCsmacd';
        } elseif (isset($entry['asETHPortInterfaceType'])) {
            switch ($entry['ipeStsPortEtherDuplex']) {
                case 'fiber':
                    $port_stats[$ifIndex]['ifType'] = 'opticalChannel'; // ??
                    break;

                case 'copper':
                    $port_stats[$ifIndex]['ifType'] = 'ethernetCsmacd';
                    break;

                default:
                    $port_stats[$ifIndex]['ifType'] = 'other';
            }
        } elseif (str_starts($port['ifName'], 'lo')) {
            $port_stats[$ifIndex]['ifType'] = 'softwareLoopback';
        } elseif (isset($entry['atpcPowerMode'])) {
            $port_stats[$ifIndex]['ifType'] = 'otnOdu'; // ??
        } elseif (!str_starts($port['ifName'], 'lldp')) {
            $port_stats[$ifIndex]['ifType'] = 'other';
        } else {
            continue;
        }

        // ifOperStatus
        if (isset($entry['ipeStsPortEtherLinkUp'])) {
            switch ($entry['ipeStsPortEtherLinkUp']) {
                case '1':
                    $port_stats[$ifIndex]['ifOperStatus'] = 'up';
                    break;

                case '2':
                    $port_stats[$ifIndex]['ifOperStatus'] = 'down';
                    break;

                default:
                    $port_stats[$ifIndex]['ifOperStatus'] = 'unknown';
            }
        } elseif (isset($entry['asETHPortOperStatus'])) {
            switch ($entry['asETHPortOperStatus']) {
                case 'linkDown':
                case '1':
                    $port_stats[$ifIndex]['ifOperStatus'] = 'down';
                    break;

                case 'linkUp':
                case '2':
                    $port_stats[$ifIndex]['ifOperStatus'] = 'up';
                    break;

                default:
                    $port_stats[$ifIndex]['ifOperStatus'] = 'unknown';
            }
        } elseif (isset($entry['ipeCfgPortModemEnable'])) {
            $port_stats[$ifIndex]['ifOperStatus'] = $entry['ipeCfgPortModemEnable'] === 'enabled' ? 'up' : 'down';
        } elseif (str_starts($port['ifName'], 'lo')) {
            $port_stats[$ifIndex]['ifOperStatus'] = 'up';
        } elseif (isset($entry['atpcPowerMode'])) {
            $port_stats[$ifIndex]['ifOperStatus'] = $entry['atpcPowerMode'] === 'active' ? 'up' : 'down';
        } else {
            // Force unknown
            $port_stats[$ifIndex]['ifOperStatus'] = 'unknown';
        }

    }
}

// EOF
