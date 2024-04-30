<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

/*
DCP-LINKVIEW-MIB::dcpLinkviewLocalHostname.100111 = STRING: DCP-M40-HEID
DCP-LINKVIEW-MIB::dcpLinkviewLocalName.100111 = STRING: if-1/line-tx
DCP-LINKVIEW-MIB::dcpLinkviewLocalStatus.100111 = INTEGER: up(3)
DCP-LINKVIEW-MIB::dcpLinkviewLocalPower.100111 = INTEGER: 7.5
DCP-LINKVIEW-MIB::dcpLinkviewFiberLoss.100111 = Gauge32: 16.7
DCP-LINKVIEW-MIB::dcpLinkviewFiberAttenuation.100111 = Gauge32: .29
DCP-LINKVIEW-MIB::dcpLinkviewFiberLength.100111 = Gauge32: 55.4
DCP-LINKVIEW-MIB::dcpLinkviewFiberDispersion.100111 = Gauge32: 924
DCP-LINKVIEW-MIB::dcpLinkviewFiberType.100111 = STRING: G.652
DCP-LINKVIEW-MIB::dcpLinkviewFiberDispComp.100111 = INTEGER: -918
DCP-LINKVIEW-MIB::dcpLinkviewFiberDispFinal.100111 = INTEGER: 0
DCP-LINKVIEW-MIB::dcpLinkviewFiberUtilization.100111 = Gauge32: 5
DCP-LINKVIEW-MIB::dcpLinkviewRemotePower.100111 = INTEGER: -9.2
DCP-LINKVIEW-MIB::dcpLinkviewRemoteName.100111 = STRING: if-1/line-rx
DCP-LINKVIEW-MIB::dcpLinkviewRemoteHostname.100111 = STRING: DCP-M40-W379
 */

$dcp_array = snmp_cache_table($device, 'dcpLinkviewTable', [], $mib);
print_debug_vars($dcp_array);

foreach ($dcp_array as $ifIndex => $dcp) {
    if ($dcp['dcpLinkviewLocalStatus'] !== 'up') {
        continue;
    }

    $port = get_port_by_index_cache($device, $ifIndex);

    $remote_hostname = $dcp['dcpLinkviewRemoteHostname'];
    // Try find a remote device and check if already cached
    $remote_device_id = get_autodiscovery_device_id($device, $remote_hostname);
    if (is_null($remote_device_id) &&            // NULL - never cached in other rounds
        check_autodiscovery($remote_hostname)) { // Check all previous autodiscovery rounds
        // Neighbour never checked, try autodiscovery
        $remote_device_id = autodiscovery_device($remote_hostname, NULL, 'DCP', '', $device, $port);
    }

    $remote_port_id = NULL;
    if ($remote_device_id) {
        $if             = $dcp['dcpLinkviewRemoteName'];
        $query          = 'SELECT `port_id` FROM `ports` WHERE (`ifName` = ? OR `ifDescr` = ? OR `port_label_short` = ?) AND `device_id` = ? AND `deleted` = ?';
        $remote_port_id = dbFetchCell($query, [$if, $if, $if, $remote_device_id, 0]);
    }

    $neighbour = [
        'remote_device_id' => $remote_device_id,
        'remote_port_id'   => $remote_port_id,
        'remote_hostname'  => $remote_hostname,
        'remote_port'      => $dcp['dcpLinkviewRemoteName'],
        'remote_platform'  => '',
        'remote_version'   => '',
    ];
    discover_neighbour($port, 'dcp', $neighbour);
}

// EOF
