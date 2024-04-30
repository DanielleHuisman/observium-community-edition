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

print_cli_data_field($mib . ' Processes', 2);

/*
OSPF-MIB::ospfRouterId.0 = IpAddress: 1.185.1.113
OSPF-MIB::ospfAdminStat.0 = INTEGER: enabled(1)
OSPF-MIB::ospfVersionNumber.0 = INTEGER: version2(2)
OSPF-MIB::ospfAreaBdrRtrStatus.0 = INTEGER: false(2)
OSPF-MIB::ospfASBdrRtrStatus.0 = INTEGER: true(1)
OSPF-MIB::ospfExternLsaCount.0 = Gauge32: 104
OSPF-MIB::ospfExternLsaCksumSum.0 = INTEGER: 3322892
OSPF-MIB::ospfTOSSupport.0 = INTEGER: false(2)
OSPF-MIB::ospfOriginateNewLsas.0 = Counter32: 11993
OSPF-MIB::ospfRxNewLsas.0 = Counter32: 553365
OSPF-MIB::ospfExtLsdbLimit.0 = INTEGER: -1
OSPF-MIB::ospfMulticastExtensions.0 = INTEGER: 0
OSPF-MIB::ospfExitOverflowInterval.0 = INTEGER: 0
OSPF-MIB::ospfDemandExtensions.0 = INTEGER: false(2)
*/

// Pull data from device
if ($ospf_instance_poll = snmpwalk_cache_oid($device, 'ospfGeneralGroup', [], 'OSPF-MIB')) {
    $ospf_instance_poll = array_shift($ospf_instance_poll);

    // Don't bother polling everything if we have no enabled or non-defaulted router ids.
    if ($ospf_instance_poll['ospfRouterId'] !== '0.0.0.0' ||
        $ospf_instance_poll['ospfAdminStat'] === 'enabled') {
        $ospf_enabled = TRUE;
    }
}

// Build array of existing entries
$ospf_instance_db = dbFetchRow('SELECT * FROM `ospf_instances` WHERE `device_id` = ? AND `ospfVersionNumber` = ?', [$device['device_id'], 'version2']);

if ($ospf_enabled && empty($ospf_instance_db)) {
    $ospf_instance_id = dbInsert(['device_id' => $device['device_id'], 'ospfVersionNumber' => 'version2'], 'ospf_instances');
    $ospf_instance_db = dbFetchRow('SELECT * FROM `ospf_instances` WHERE `ospf_instance_id` = ?', [$ospf_instance_id]);
    echo('+');
} elseif (!$ospf_enabled && !empty($ospf_instance_db)) {
    dbDelete('ospf_instances', '`ospf_instance_id` = ?', [$ospf_instance_db['instance_id']]);
    echo('-');
}

if (!$ospf_enabled) {
    // OSPF instance not enabled
    return;
} elseif (OBS_DEBUG) {
    echo("\nPolled: ");
    print_vars($ospf_instance_poll);
    echo('Database: ');
    print_vars($ospf_instance_db);
    echo("\n");
}

$ospf_stats = [
  'instances'  => 0,
  'ports'      => 0,
  'areas'      => 0,
  'neighbours' => 0
];

$ospf_oids_db = ['ospfRouterId', 'ospfAdminStat', 'ospfVersionNumber', 'ospfAreaBdrRtrStatus', 'ospfASBdrRtrStatus',
                 'ospfExternLsaCount', 'ospfExternLsaCksumSum', 'ospfTOSSupport', 'ospfOriginateNewLsas', 'ospfRxNewLsas',
                 'ospfExtLsdbLimit', 'ospfMulticastExtensions', 'ospfExitOverflowInterval', 'ospfDemandExtensions'];

// Loop array of entries and update
$ospf_instance_id = $ospf_instance_db['ospf_instance_id'];

if ($ospf_instance_poll['ospfAdminStat'] !== 'enabled') {
    $ospf_instance_poll['ospfAdminStat'] = 'disabled';
}
if ($ospf_instance_poll['ospfVersionNumber'] !== 'version2') {
    $ospf_instance_poll['ospfVersionNumber'] = 'version2';
}

$ospf_instance_update = [];
foreach ($ospf_oids_db as $oid) {
    if (in_array($oid, ['ospfAreaBdrRtrStatus', 'ospfASBdrRtrStatus', 'ospfTOSSupport', 'ospfDemandExtensions']) &&
        !in_array($ospf_instance_poll[$oid], ['true', 'false'])) {
        // FIX invalid values
        $ospf_instance_poll[$oid] = 'false';
    }
    // Loop the OIDs
    if ($ospf_instance_db[$oid] != $ospf_instance_poll[$oid]) {
        // If data has changed, build a query
        $ospf_instance_update[$oid] = $ospf_instance_poll[$oid];
        // log_event("$oid -> ".$this_port[$oid], $device, 'ospf', $port['port_id']); // FIXME
    }
}

if ($ospf_instance_update) {
    dbUpdate($ospf_instance_update, 'ospf_instances', '`ospf_instance_id` = ?', [$ospf_instance_id]);
    echo('U');
} else {
    echo('.');
}
$ospf_stats['instances']++; // Really in OSPF-MIB always only 1 instance (process)

unset($ospf_instance_poll, $ospf_instance_db, $ospf_instance_update);

echo(PHP_EOL);

print_cli_data_field($mib . ' Areas', 2);

/*
OSPF-MIB::ospfAreaId.0.0.0.0 = IpAddress: 0.0.0.0
OSPF-MIB::ospfAuthType.0.0.0.0 = INTEGER: none(0)
OSPF-MIB::ospfImportAsExtern.0.0.0.0 = INTEGER: importExternal(1)
OSPF-MIB::ospfSpfRuns.0.0.0.0 = Counter32: 5
OSPF-MIB::ospfAreaBdrRtrCount.0.0.0.0 = Gauge32: 0
OSPF-MIB::ospfAsBdrRtrCount.0.0.0.0 = Gauge32: 3
OSPF-MIB::ospfAreaLsaCount.0.0.0.0 = Gauge32: 8
OSPF-MIB::ospfAreaLsaCksumSum.0.0.0.0 = INTEGER: 0
OSPF-MIB::ospfAreaSummary.0.0.0.0 = INTEGER: sendAreaSummary(2)
OSPF-MIB::ospfAreaStatus.0.0.0.0 = INTEGER: active(1)
 */
$ospf_areas_poll = snmpwalk_cache_oid($device, 'ospfAreaEntry', [], 'OSPF-MIB');

$ospf_area_oids = ['ospfAuthType', 'ospfImportAsExtern', 'ospfSpfRuns', 'ospfAreaBdrRtrCount', 'ospfAsBdrRtrCount',
                   'ospfAreaLsaCount', 'ospfAreaLsaCksumSum', 'ospfAreaSummary', 'ospfAreaStatus'];

// Build array of existing entries
$ospf_areas_db = [];
if (get_db_version() < 477) {
    // CLEANME. Remove after CE 23.xx
    $sql    = 'SELECT * FROM `ospf_areas` WHERE `device_id` = ?';
    $params = [$device['device_id']];
} else {
    $sql    = 'SELECT * FROM `ospf_areas` WHERE `device_id` = ? AND `ospfVersionNumber` = ?';
    $params = [$device['device_id'], 'version2'];
}

foreach (dbFetchRows($sql, $params) as $entry) {
    // Skip OSPFV3-MIB entries
    if (isset($entry['ospfVersionNumber']) && $entry['ospfVersionNumber'] === 'version3') {
        continue;
    }

    $ospf_areas_db[$entry['ospfAreaId']] = $entry;
}

if (OBS_DEBUG) {
    echo("\nPolled: ");
    print_vars($ospf_areas_poll);
    echo('Database: ');
    print_vars($ospf_areas_db);
    echo("\n");
}

foreach ($ospf_areas_poll as $ospf_area_index => $ospf_area_poll) {
    // Fortigate have Extra index part!
    $index_parts = explode('.', $ospf_area_index);
    if (count($index_parts) > 4) {
        // OSPF-MIB::ospfAreaId.0.0.0.0.1 = IpAddress: 0.0.0.0
        array_pop($index_parts);
        $ospf_area_index = implode('.', $index_parts);
    }

    // If the entry doesn't already exist in the prebuilt array, insert into the database and put into the array
    $insert = [];
    if (!isset($ospf_areas_db[$ospf_area_index])) {
        $insert['device_id']         = $device['device_id'];
        $insert['ospfAreaId']        = $ospf_area_index;
        $insert['ospfVersionNumber'] = 'version2';

        foreach ($ospf_area_oids as $oid) {
            // Loop the OIDs
            $insert[$oid] = $ospf_area_poll[$oid];
        }

        dbInsertRowMulti($insert, 'ospf_areas');
        // $ospf_area_id = dbInsert($insert, 'ospf_areas');
        echo('+');
        // $ospf_areas_db[$ospf_area_index] = dbFetchRow('SELECT * FROM `ospf_areas` WHERE `device_id` = ? AND `ospfAreaId` = ?', [ $device['device_id'], $ospf_area_index ]);
    } else {
        $ospf_area_db = $ospf_areas_db[$ospf_area_index];
        $db_update    = FALSE;

        $insert['ospf_area_id'] = $ospf_area_db['ospf_area_id'];
        foreach ($ospf_area_oids as $oid) {
            // Loop the OIDs
            $insert[$oid] = $ospf_area_poll[$oid];
            if ($ospf_area_db[$oid] != $ospf_area_poll[$oid]) {
                $db_update = TRUE;
            }
        }
        if ($db_update) {
            $insert['ospfVersionNumber'] = 'version2';
            dbUpdateRowMulti($insert, 'ospf_areas', 'ospf_area_id');
            echo('U');
        } else {
            echo('.');
        }
        unset($ospf_areas_db[$ospf_area_index]);
    }
    $ospf_stats['areas']++;
    unset($insert);
}

// Multi Insert/Update
dbProcessMulti('ospf_areas');

// Clean
$db_delete = [];
foreach ($ospf_areas_db as $ospf_area_db) {
    $db_delete[] = $ospf_area_db['ospf_area_id'];
    echo '-';
}
// Multi Delete
if (!safe_empty($db_delete)) {
    dbDelete('ospf_areas', generate_query_values($db_delete, 'ospf_area_id'));
}

unset($ospf_areas_db, $ospf_areas_poll, $db_delete);

echo PHP_EOL;

print_cli_data_field($mib . ' Ports', 2);

/*
OSPF-MIB::ospfIfIpAddress.95.130.232.140.0 = IpAddress: 95.130.232.140
OSPF-MIB::ospfAddressLessIf.95.130.232.140.0 = INTEGER: 0
OSPF-MIB::ospfIfAreaId.95.130.232.140.0 = IpAddress: 0.0.0.0
OSPF-MIB::ospfIfType.95.130.232.140.0 = INTEGER: broadcast(1)
OSPF-MIB::ospfIfAdminStat.95.130.232.140.0 = INTEGER: enabled(1)
OSPF-MIB::ospfIfRtrPriority.95.130.232.140.0 = INTEGER: 10
OSPF-MIB::ospfIfTransitDelay.95.130.232.140.0 = INTEGER: 1 seconds
OSPF-MIB::ospfIfRetransInterval.95.130.232.140.0 = INTEGER: 5 seconds
OSPF-MIB::ospfIfHelloInterval.95.130.232.140.0 = INTEGER: 10 seconds
OSPF-MIB::ospfIfRtrDeadInterval.95.130.232.140.0 = INTEGER: 40 seconds
OSPF-MIB::ospfIfPollInterval.95.130.232.140.0 = INTEGER: 60 seconds
OSPF-MIB::ospfIfState.95.130.232.140.0 = INTEGER: otherDesignatedRouter(7)
OSPF-MIB::ospfIfDesignatedRouter.95.130.232.140.0 = IpAddress: 95.130.232.130
OSPF-MIB::ospfIfBackupDesignatedRouter.95.130.232.140.0 = IpAddress: 95.130.232.190
OSPF-MIB::ospfIfEvents.95.130.232.140.0 = Counter32: 2
OSPF-MIB::ospfIfAuthKey.95.130.232.140.0 = ""
OSPF-MIB::ospfIfStatus.95.130.232.140.0 = INTEGER: active(1)
OSPF-MIB::ospfIfMulticastForwarding.95.130.232.140.0 = INTEGER: blocked(1)
OSPF-MIB::ospfIfDemand.95.130.232.140.0 = INTEGER: false(2)
OSPF-MIB::ospfIfAuthType.95.130.232.140.0 = INTEGER: none(0)
 */
$ospf_ports_poll = snmpwalk_cache_oid($device, 'ospfIfEntry', [], 'OSPF-MIB');

$ospf_port_oids = ['ospfIfIpAddress', 'ospfAddressLessIf', 'ospfIfAreaId', 'ospfIfType',
                   'ospfIfAdminStat', 'ospfIfRtrPriority', 'ospfIfTransitDelay', 'ospfIfRetransInterval',
                   'ospfIfHelloInterval', 'ospfIfRtrDeadInterval', 'ospfIfPollInterval', 'ospfIfState',
                   'ospfIfDesignatedRouter', 'ospfIfBackupDesignatedRouter', 'ospfIfEvents', 'ospfIfAuthKey',
                   'ospfIfStatus', 'ospfIfMulticastForwarding', 'ospfIfDemand', 'ospfIfAuthType'];

// Build array of existing entries
if (get_db_version() < 493) {
    // CLEANME. Remove after CE 24.xx
    // V2 always have 5 part index, ie: 95.130.232.140.0
    $sql    = 'SELECT * FROM `ospf_ports` WHERE `device_id` = ? AND `ospf_port_id` REGEXP ?';
    $params = [ $device['device_id'], '^[[:digit:]]+(\.[[:digit:]]+){4}$' ];
} else {
    $sql    = 'SELECT * FROM `ospf_ports` WHERE `device_id` = ? AND `ospfVersionNumber` = ?';
    $params = [ $device['device_id'], 'version2' ];
}
$ospf_ports_db = [];
foreach (dbFetchRows($sql, $params) as $entry) {
    $ospf_ports_db[$entry['ospf_port_id']] = $entry;
}

if (OBS_DEBUG) {
    echo("\nPolled: ");
    print_vars($ospf_ports_poll);
    echo('Database: ');
    print_vars($ospf_ports_db);
    echo("\n");
}

foreach ($ospf_ports_poll as $ospf_port_index => $ospf_port_poll) {
    // Fortigate have Extra index part!
    $index_parts = explode('.', $ospf_port_index);
    if (count($index_parts) > 5) {
        // OSPF-MIB::ospfIfIpAddress.10.255.14.1.0.1 = IpAddress: 10.255.14.1
        array_pop($index_parts);
        $ospf_port_index = implode('.', $index_parts);
    }

    $insert = [];

    // Get associated port
    $insert['port_id'] = 0; // Unknown
    if ($ospf_port_poll['ospfAddressLessIf']) {
        //$ospf_port_poll['port_id'] = @dbFetchCell('SELECT `port_id` FROM `ports` WHERE `device_id` = ? AND `ifIndex` = ?', [ $device['device_id'], $ospf_port_poll['ospfAddressLessIf'] ]);
        if ($port = get_port_by_index_cache($device, $ospf_port_poll['ospfAddressLessIf'])) {
            $insert['port_id'] = $port['port_id'];
        }
    } else {
        $ids = get_entity_ids_ip_by_network('port', $ospf_port_poll['ospfIfIpAddress'], generate_query_values_and($device['device_id'], 'device_id'));
        if (safe_count($ids)) {
            $insert['port_id'] = current($ids);
        }
    }

    // If the entry doesn't already exist in the prebuilt array, insert into the database and put into the array
    if (!isset($ospf_ports_db[$ospf_port_index])) {
        $insert['device_id']    = $device['device_id'];
        $insert['ospf_port_id'] = $ospf_port_index;
        $insert['ospfVersionNumber'] = 'version2';

        foreach ($ospf_port_oids as $oid) {
            // Loop the OIDs
            $insert[$oid] = $ospf_port_poll[$oid];
        }

        dbInsertRowMulti($insert, 'ospf_ports');
        echo('+');
    } else {
        $ospf_port_db = $ospf_ports_db[$ospf_port_index];
        $db_update    = FALSE;

        $insert['ospf_ports_id'] = $ospf_port_db['ospf_ports_id'];
        foreach ($ospf_port_oids as $oid) {
            // Loop the OIDs
            $insert[$oid] = $ospf_port_poll[$oid];
            if ($ospf_port_db[$oid] != $ospf_port_poll[$oid]) {
                $db_update = TRUE;
            }
        }
        if ($db_update) {
            $insert['ospfVersionNumber'] = 'version2';
            dbUpdateRowMulti($insert, 'ospf_ports', 'ospf_ports_id');
            echo('U');
        } else {
            echo('.');
        }
        unset($ospf_ports_db[$ospf_port_index]);
    }
    $ospf_stats['ports']++;
    unset($insert);
}

// Multi Insert/Update
dbProcessMulti('ospf_ports');

// Clean
$db_delete = [];
foreach ($ospf_ports_db as $ospf_port_db) {
    $db_delete[] = $ospf_port_db['ospf_ports_id'];
    echo '-';
}
// Multi Delete
if (!safe_empty($db_delete)) {
    dbDelete('ospf_ports', generate_query_values($db_delete, 'ospf_ports_id'));
}

unset($ospf_ports_db, $ospf_ports_poll, $db_delete);

echo PHP_EOL;

print_cli_data_field($mib . ' Neighbours', 2);

/*
OSPF-MIB::ospfNbrIpAddr.95.130.232.130.0 = IpAddress: 95.130.232.130
OSPF-MIB::ospfNbrAddressLessIndex.95.130.232.130.0 = INTEGER: 0
OSPF-MIB::ospfNbrRtrId.95.130.232.130.0 = IpAddress: 185.100.140.1
OSPF-MIB::ospfNbrOptions.95.130.232.130.0 = INTEGER: 2
OSPF-MIB::ospfNbrPriority.95.130.232.130.0 = INTEGER: 10
OSPF-MIB::ospfNbrState.95.130.232.130.0 = INTEGER: full(8)
OSPF-MIB::ospfNbrEvents.95.130.232.130.0 = Counter32: 6
OSPF-MIB::ospfNbrLsRetransQLen.95.130.232.130.0 = Gauge32: 0
OSPF-MIB::ospfNbmaNbrStatus.95.130.232.130.0 = INTEGER: active(1)
OSPF-MIB::ospfNbmaNbrPermanence.95.130.232.130.0 = INTEGER: permanent(2)
OSPF-MIB::ospfNbrHelloSuppressed.95.130.232.130.0 = INTEGER: false(2)
 */
$ospf_nbrs_poll = snmpwalk_cache_oid($device, 'ospfNbrEntry', [], 'OSPF-MIB');

$ospf_nbr_oids = ['ospfNbrIpAddr', 'ospfNbrAddressLessIndex', 'ospfNbrRtrId', 'ospfNbrOptions', 'ospfNbrPriority',
                  'ospfNbrState', 'ospfNbrEvents', 'ospfNbrLsRetransQLen', 'ospfNbmaNbrStatus',
                  'ospfNbmaNbrPermanence', 'ospfNbrHelloSuppressed'];

// Build array of existing entries
if (get_db_version() < 493) {
    // CLEANME. Remove after CE 24.xx
    // V2 always have 5 part index, ie: 95.130.232.140.0
    $sql    = 'SELECT * FROM `ospf_nbrs` WHERE `device_id` = ? AND `ospf_nbr_id` REGEXP ?';
    $params = [ $device['device_id'], '^[[:digit:]]+(\.[[:digit:]]+){4}$' ];
} else {
    $sql    = 'SELECT * FROM `ospf_nbrs` WHERE `device_id` = ? AND `ospfVersionNumber` = ?';
    $params = [ $device['device_id'], 'version2' ];
}
$ospf_nbrs_db = [];
foreach (dbFetchRows($sql, $params) as $entry) {
    $ospf_nbrs_db[$entry['ospf_nbr_id']] = $entry;
}

if (OBS_DEBUG) {
    echo("\nPolled: ");
    print_vars($ospf_nbrs_poll);
    echo('Database: ');
    print_vars($ospf_nbrs_db);
    echo("\n");
}

foreach ($ospf_nbrs_poll as $ospf_nbr_index => $ospf_nbr_poll) {
    // Fortigate have Extra index part!
    $index_parts = explode('.', $ospf_nbr_index);
    if (count($index_parts) > 5) {
        // OSPF-MIB::ospfNbrIpAddr.10.255.14.2.0.1 = IpAddress: 10.255.14.2
        array_pop($index_parts);
        $ospf_nbr_index = implode('.', $index_parts);
    }

    // If the entry doesn't already exist in the prebuilt array, insert into the database and put into the array
    $insert = [];

    // Get associated port
    $insert['port_id'] = NULL; // Unknown
    if ($ospf_nbr_poll['ospfNbrAddressLessIndex']) {
        if ($port = get_port_by_index_cache($device, $ospf_nbr_poll['ospfNbrAddressLessIndex'])) {
            $insert['port_id'] = $port['port_id'];
        }
    } else {
        // FIXME. This is incorrect, need search port by network range:
        // 95.130.232.130 -> 95.130.232.140/26 -> port
        //$ids = get_entity_ids_ip_by_network('port', $ospf_nbr_poll['ospfNbrIpAddr'], generate_query_values_and($device['device_id'], 'device_id'));
        //if (safe_count($ids)) {
        //  $insert['port_id'] = current($ids);
        //}

        if ($nbr_port_id = port_id_by_subnet_ipv4($device['device_id'], $ospf_nbr_poll['ospfNbrIpAddr'])) {
            $insert['port_id'] = $nbr_port_id;
        }

    }

    if (!isset($ospf_nbrs_db[$ospf_nbr_index])) {
        $insert['device_id']   = $device['device_id'];
        $insert['ospf_nbr_id'] = $ospf_nbr_index;
        $insert['ospfVersionNumber'] = 'version2';

        if (is_null($insert['port_id'])) {
            $insert['port_id'] = 0; // keep compat with old db
        }
        foreach ($ospf_nbr_oids as $oid) {
            // Loop the OIDs
            $insert[$oid] = $ospf_nbr_poll[$oid];
        }

        dbInsertRowMulti($insert, 'ospf_nbrs');
        echo('+');
    } else {
        $ospf_nbr_db = $ospf_nbrs_db[$ospf_nbr_index];
        $db_update   = FALSE;

        $insert['ospf_nbrs_id'] = $ospf_nbr_db['ospf_nbrs_id'];
        foreach ($ospf_nbr_oids as $oid) {
            // Loop the OIDs
            $insert[$oid] = $ospf_nbr_poll[$oid];
            if ($ospf_nbr_db[$oid] != $ospf_nbr_poll[$oid]) {
                $db_update = TRUE;
            }
        }
        if ($ospf_nbr_db['port_id'] != $insert['port_id']) {
            $db_update = TRUE;
        }
        if ($db_update) {
            $insert['ospfVersionNumber'] = 'version2';
            if (is_null($insert['port_id'])) {
                $insert['port_id'] = ['NULL'];
            }
            dbUpdateRowMulti($insert, 'ospf_nbrs', 'ospf_nbrs_id');
            echo('U');
        } else {
            echo('.');
        }
        unset($ospf_nbrs_db[$ospf_nbr_index]);
    }
    $ospf_stats['neighbours']++;
    unset($insert);
}

// Multi Insert/Update
dbProcessMulti('ospf_nbrs');

// Clean
$db_delete = [];
foreach ($ospf_nbrs_db as $ospf_nbr_db) {
    $db_delete[] = $ospf_nbr_db['ospf_nbrs_id'];
    echo '-';
}
// Multi Delete
if (!safe_empty($db_delete)) {
    dbDelete('ospf_nbrs', generate_query_values($db_delete, 'ospf_nbrs_id'));
}

unset($ospf_nbrs_db, $ospf_nbrs_poll, $db_delete);

// Create device-wide statistics RRD
rrdtool_update_ng($device, 'ospf-statistics', [
  'instances'  => $ospf_stats['instances'],
  'areas'      => $ospf_stats['areas'],
  'ports'      => $ospf_stats['ports'],
  'neighbours' => $ospf_stats['neighbours'],
]);

$graphs['ospf_neighbours'] = TRUE;
$graphs['ospf_areas']      = TRUE;
$graphs['ospf_ports']      = TRUE;

echo PHP_EOL;

// EOF
