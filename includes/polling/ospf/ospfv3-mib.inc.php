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

// CLEANME. Remove after CE 23.xx
if (get_db_version() < 477) {
    print_warning("DB schema not updated. OSPFV3-MIB not polled until update.");
    return;
}

print_cli_data_field($mib . ' Processes', 2);

/*
OSPFV3-MIB::ospfv3RouterId.0 = Gauge32: 1602414732
OSPFV3-MIB::ospfv3AdminStatus.0 = INTEGER: enabled(1)
OSPFV3-MIB::ospfv3VersionNumber.0 = INTEGER: version3(3)
OSPFV3-MIB::ospfv3AreaBdrRtrStatus.0 = INTEGER: false(2)
OSPFV3-MIB::ospfv3ASBdrRtrStatus.0 = INTEGER: false(2)
OSPFV3-MIB::ospfv3AsScopeLsaCount.0 = Gauge32: 2
OSPFV3-MIB::ospfv3AsScopeLsaCksumSum.0 = Gauge32: 11269
OSPFV3-MIB::ospfv3OriginateNewLsas.0 = Counter32: 0
OSPFV3-MIB::ospfv3RxNewLsas.0 = Counter32: 0
OSPFV3-MIB::ospfv3ExtLsaCount.0 = Gauge32: 2
OSPFV3-MIB::ospfv3ExtAreaLsdbLimit.0 = INTEGER: -1
OSPFV3-MIB::ospfv3ExitOverflowInterval.0 = Gauge32: 0 seconds
OSPFV3-MIB::ospfv3DemandExtensions.0 = INTEGER: 0
*/

// Pull data from device
if ($ospf_instance_poll = snmpwalk_cache_oid($device, 'ospfv3GeneralGroup', [], 'OSPFV3-MIB')) {
    $ospf_instance_poll = $ospf_instance_poll[0];

    // Don't bother polling everything if we have no enabled or non-defaulted router ids.
    if ($ospf_instance_poll['ospfv3RouterId'] !== '0' ||
        $ospf_instance_poll['ospfv3AdminStatus'] === 'enabled') {
        $ospf_enabled = TRUE;
    }
}

// Build array of existing entries
$ospf_instance_db = dbFetchRow('SELECT * FROM `ospf_instances` WHERE `device_id` = ? AND `ospfVersionNumber` = ?', [$device['device_id'], 'version3']);

if ($ospf_enabled && empty($ospf_instance_db)) {
    $ospf_instance_id = dbInsert(['device_id' => $device['device_id'], 'ospfVersionNumber' => 'version3'], 'ospf_instances');
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

$ospf_oids_db = ['ospfRouterId'             => 'ospfv3RouterId',
                 'ospfAdminStat'            => 'ospfv3AdminStatus',
                 'ospfVersionNumber'        => 'ospfv3VersionNumber',
                 'ospfAreaBdrRtrStatus'     => 'ospfv3AreaBdrRtrStatus',
                 'ospfASBdrRtrStatus'       => 'ospfv3ASBdrRtrStatus',
                 'ospfExternLsaCount'       => 'ospfv3ExtLsaCount',
                 'ospfExternLsaCksumSum'    => '',
                 'ospfTOSSupport'           => '',
                 'ospfOriginateNewLsas'     => 'ospfv3OriginateNewLsas',
                 'ospfRxNewLsas'            => 'ospfv3RxNewLsas',
                 'ospfExtLsdbLimit'         => 'ospfv3ExtAreaLsdbLimit',
                 'ospfMulticastExtensions'  => '',
                 'ospfExitOverflowInterval' => 'ospfv3ExitOverflowInterval',
                 'ospfDemandExtensions'     => 'ospfv3DemandExtensions'];

// Loop array of entries and update
$ospf_instance_id = $ospf_instance_db['ospf_instance_id'];

if ($ospf_instance_poll['ospfv3AdminStatus'] !== 'enabled') {
    $ospf_instance_poll['ospfv3AdminStatus'] = 'disabled';
}
if ($ospf_instance_poll['ospfv3VersionNumber'] !== 'version3') {
    $ospf_instance_poll['ospfv3VersionNumber'] = 'version3';
}
// Not exist in version3:
$ospf_instance_poll['ospfExternLsaCksumSum']   = '0';
$ospf_instance_poll['ospfTOSSupport']          = 'false';
$ospf_instance_poll['ospfMulticastExtensions'] = '0';

$ospf_instance_update = [];
foreach ($ospf_oids_db as $field => $oid) {
    if (in_array($field, ['ospfAreaBdrRtrStatus', 'ospfASBdrRtrStatus', 'ospfDemandExtensions']) &&
        !in_array($ospf_instance_poll[$oid], ['true', 'false'])) {
        // FIX invalid values
        $ospf_instance_poll[$oid] = 'false';
    }
    // Loop the OIDs
    if (empty($oid)) {
        // not exist oids in version3
        if ($ospf_instance_db[$field] != $ospf_instance_poll[$field]) {
            $ospf_instance_update[$field] = $ospf_instance_poll[$field];
        }
    } elseif ($ospf_instance_db[$field] != $ospf_instance_poll[$oid]) {
        // If data has changed, build a query
        $ospf_instance_update[$field] = $ospf_instance_poll[$oid];
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
OSPFV3-MIB::ospfv3AreaImportAsExtern.0 = INTEGER: importExternal(1)
OSPFV3-MIB::ospfv3AreaSpfRuns.0 = Counter32: 5
OSPFV3-MIB::ospfv3AreaBdrRtrCount.0 = Gauge32: 0
OSPFV3-MIB::ospfv3AreaAsBdrRtrCount.0 = Gauge32: 2
OSPFV3-MIB::ospfv3AreaScopeLsaCount.0 = Gauge32: 16
OSPFV3-MIB::ospfv3AreaScopeLsaCksumSum.0 = Gauge32: 92
OSPFV3-MIB::ospfv3AreaSummary.0 = INTEGER: sendAreaSummary(2)
OSPFV3-MIB::ospfv3AreaRowStatus.0 = INTEGER: active(1)
 */
$ospf_areas_poll = snmpwalk_cache_oid($device, 'ospfv3AreaEntry', [], 'OSPFV3-MIB');

$ospf_area_oids = [ /* 'ospfAuthType' => '', */
                    'ospfImportAsExtern'  => 'ospfv3AreaImportAsExtern',
                    'ospfSpfRuns'         => 'ospfv3AreaSpfRuns',
                    'ospfAreaBdrRtrCount' => 'ospfv3AreaBdrRtrCount',
                    'ospfAsBdrRtrCount'   => 'ospfv3AreaAsBdrRtrCount',
                    'ospfAreaLsaCount'    => 'ospfv3AreaScopeLsaCount',
                    'ospfAreaLsaCksumSum' => 'ospfv3AreaScopeLsaCksumSum',
                    'ospfAreaSummary'     => 'ospfv3AreaSummary',
                    'ospfAreaStatus'      => 'ospfv3AreaRowStatus'];

// Build array of existing entries
$ospf_areas_db = [];
foreach (dbFetchRows('SELECT * FROM `ospf_areas` WHERE `device_id` = ? AND `ospfVersionNumber` = ?', [$device['device_id'], 'version3']) as $entry) {
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
    // If the entry doesn't already exist in the prebuilt array, insert into the database and put into the array
    $insert = [];
    if (!isset($ospf_areas_db[$ospf_area_index])) {
        $insert['device_id']         = $device['device_id'];
        $insert['ospfAreaId']        = $ospf_area_index;
        $insert['ospfVersionNumber'] = 'version3';

        foreach ($ospf_area_oids as $field => $oid) {
            // Loop the OIDs
            $insert[$field] = $ospf_area_poll[$oid];
        }

        dbInsertRowMulti($insert, 'ospf_areas');
        echo('+');
    } else {
        $ospf_area_db = $ospf_areas_db[$ospf_area_index];
        $db_update    = FALSE;

        $insert['ospf_area_id'] = $ospf_area_db['ospf_area_id'];
        foreach ($ospf_area_oids as $field => $oid) {
            // Loop the OIDs
            $insert[$field] = $ospf_area_poll[$oid];
            if ($ospf_area_db[$field] != $ospf_area_poll[$oid]) {
                $db_update = TRUE;
            }
        }
        if ($db_update) {
            $insert['ospfVersionNumber'] = 'version3';
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
OSPFV3-MIB::ospfv3IfAreaId.6.0 = Gauge32: 0
OSPFV3-MIB::ospfv3IfType.6.0 = INTEGER: broadcast(1)
OSPFV3-MIB::ospfv3IfAdminStatus.6.0 = INTEGER: enabled(1)
OSPFV3-MIB::ospfv3IfRtrPriority.6.0 = INTEGER: 1
OSPFV3-MIB::ospfv3IfTransitDelay.6.0 = Gauge32: 1 seconds
OSPFV3-MIB::ospfv3IfRetransInterval.6.0 = Gauge32: 5 seconds
OSPFV3-MIB::ospfv3IfHelloInterval.6.0 = INTEGER: 10 seconds
OSPFV3-MIB::ospfv3IfRtrDeadInterval.6.0 = Gauge32: 40 seconds
OSPFV3-MIB::ospfv3IfState.6.0 = INTEGER: otherDesignatedRouter(7)
OSPFV3-MIB::ospfv3IfDesignatedRouter.6.0 = Gauge32: 1602414732
OSPFV3-MIB::ospfv3IfBackupDesignatedRouter.6.0 = Gauge32: 0
OSPFV3-MIB::ospfv3IfEvents.6.0 = Counter32: 4
OSPFV3-MIB::ospfv3IfRowStatus.6.0 = INTEGER: active(1)
OSPFV3-MIB::ospfv3IfDemand.6.0 = INTEGER: false(2)
OSPFV3-MIB::ospfv3IfMetricValue.6.0 = INTEGER: 200
OSPFV3-MIB::ospfv3IfLinkScopeLsaCount.6.0 = Gauge32: 1
OSPFV3-MIB::ospfv3IfLinkLsaCksumSum.6.0 = Gauge32: 40843
 */
$ospf_ports_poll = snmpwalk_cache_oid($device, 'ospfv3IfEntry', [], 'OSPFV3-MIB');

$ospf_port_oids = ['ospfIfIpAddress'              => 'ospfIfIpAddress',
                   'ospfAddressLessIf'            => 'ospfAddressLessIf', // not really exist
                   'ospfIfAreaId'                 => 'ospfv3IfAreaId',
                   'ospfIfType'                   => 'ospfv3IfType',
                   'ospfIfAdminStat'              => 'ospfv3IfAdminStatus',
                   'ospfIfRtrPriority'            => 'ospfv3IfRtrPriority',
                   'ospfIfTransitDelay'           => 'ospfv3IfTransitDelay',
                   'ospfIfRetransInterval'        => 'ospfv3IfRetransInterval',
                   'ospfIfHelloInterval'          => 'ospfv3IfHelloInterval',
                   'ospfIfRtrDeadInterval'        => 'ospfv3IfRtrDeadInterval',
                   'ospfIfState'                  => 'ospfv3IfState',
                   'ospfIfDesignatedRouter'       => 'ospfv3IfDesignatedRouter',
                   'ospfIfBackupDesignatedRouter' => 'ospfv3IfBackupDesignatedRouter',
                   'ospfIfEvents'                 => 'ospfv3IfEvents',
                   'ospfIfStatus'                 => 'ospfv3IfRowStatus',
                   'ospfIfDemand'                 => 'ospfv3IfDemand',
                   /* 'ospfIfPollInterval', 'ospfIfAuthKey', 'ospfIfMulticastForwarding', 'ospfIfAuthType' */];

// Build array of existing entries
if (get_db_version() < 493) {
    // CLEANME. Remove after CE 24.xx
    // V3 always have 2 part index, ie: 6.0
    $sql    = 'SELECT * FROM `ospf_ports` WHERE `device_id` = ? AND `ospf_port_id` REGEXP ?';
    $params = [ $device['device_id'], '^[[:digit:]]+\.[[:digit:]]+$' ];
} else {
    $sql    = 'SELECT * FROM `ospf_ports` WHERE `device_id` = ? AND `ospfVersionNumber` = ?';
    $params = [ $device['device_id'], 'version3' ];
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
    $insert = [];

    // Get associated port
    [$ospf_ifIndex, $ospf_inst] = explode('.', $ospf_port_index);
    $ospf_port_poll['ospfAddressLessIf'] = $ospf_ifIndex;
    $ospf_port_poll['ospfIfIpAddress']   = '::';
    $insert['port_id']                   = 0; // Unknown
    if ($ospf_port_poll['ospfAddressLessIf'] &&
        $port = get_port_by_index_cache($device, $ospf_port_poll['ospfAddressLessIf'])) {
        $insert['port_id'] = $port['port_id'];

        // Get IPv6 address when possible
        if ($ospf_ip = dbFetchCell('SELECT `ipv6_address` FROM `ipv6_addresses` WHERE `port_id` = ?', [$port['port_id']])) {
            $ospf_port_poll['ospfIfIpAddress'] = $ospf_ip;
        }
    }

    // If the entry doesn't already exist in the prebuilt array, insert into the database and put into the array
    if (!isset($ospf_ports_db[$ospf_port_index])) {
        $insert['device_id']    = $device['device_id'];
        $insert['ospf_port_id'] = $ospf_port_index;
        $insert['ospfVersionNumber'] = 'version3';

        foreach ($ospf_port_oids as $field => $oid) {
            // Loop the OIDs
            $insert[$field] = $ospf_port_poll[$oid];
        }

        dbInsertRowMulti($insert, 'ospf_ports');
        echo('+');
    } else {
        $ospf_port_db = $ospf_ports_db[$ospf_port_index];
        $db_update    = FALSE;

        $insert['ospf_ports_id'] = $ospf_port_db['ospf_ports_id'];
        foreach ($ospf_port_oids as $field => $oid) {
            // Loop the OIDs
            $insert[$field] = $ospf_port_poll[$oid];
            if ($ospf_port_db[$field] != $ospf_port_poll[$oid]) {
                $db_update = TRUE;
            }
        }
        if ($db_update) {
            $insert['ospfVersionNumber'] = 'version3';
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
OSPFV3-MIB::ospfv3NbrAddressType.4.0.1602414725 = INTEGER: ipv6(2)
OSPFV3-MIB::ospfv3NbrAddress.4.0.1602414725 = Hex-STRING: FE 80 00 00 00 00 00 00 0E C4 7A FF FE BC 32 9B
OSPFV3-MIB::ospfv3NbrOptions.4.0.1602414725 = INTEGER: 0
OSPFV3-MIB::ospfv3NbrPriority.4.0.1602414725 = INTEGER: 10
OSPFV3-MIB::ospfv3NbrState.4.0.1602414725 = INTEGER: twoWay(4)
OSPFV3-MIB::ospfv3NbrEvents.4.0.1602414725 = Counter32: 2
OSPFV3-MIB::ospfv3NbrLsRetransQLen.4.0.1602414725 = Gauge32: 0
OSPFV3-MIB::ospfv3NbrHelloSuppressed.4.0.1602414725 = INTEGER: false(2)
OSPFV3-MIB::ospfv3NbrIfId.4.0.1602414725 = INTEGER: 4
 */
$ospf_nbrs_poll = snmpwalk_cache_oid($device, 'ospfv3NbrEntry', [], 'OSPFV3-MIB');

$ospf_nbr_oids = ['ospfNbrIpAddr'          => 'ospfv3NbrAddress',
                  'ospfNbrAddressLessIndex' => 'ospfv3NbrIfId',
                  'ospfNbrRtrId'           => 'ospfv3NbrRtrId',
                  'ospfNbrOptions'         => 'ospfv3NbrOptions',
                  'ospfNbrPriority'        => 'ospfv3NbrPriority',
                  'ospfNbrState'           => 'ospfv3NbrState',
                  'ospfNbrEvents'          => 'ospfv3NbrEvents',
                  'ospfNbrLsRetransQLen'   => 'ospfv3NbrLsRetransQLen',
                  /* 'ospfNbmaNbrStatus', 'ospfNbmaNbrPermanence', */
                  'ospfNbrHelloSuppressed' => 'ospfv3NbrHelloSuppressed'];

// Build array of existing entries
if (get_db_version() < 493) {
    // CLEANME. Remove after CE 24.xx
    // V3 always have 3 part index, ie: .4.0.1602414725
    $sql    = 'SELECT * FROM `ospf_nbrs` WHERE `device_id` = ? AND `ospf_nbr_id` REGEXP ?';
    $params = [ $device['device_id'], '^[[:digit:]]+(\.[[:digit:]]+){2}$' ];
} else {
    $sql    = 'SELECT * FROM `ospf_nbrs` WHERE `device_id` = ? AND `ospfVersionNumber` = ?';
    $params = [ $device['device_id'], 'version3' ];
}
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
    // If the entry doesn't already exist in the prebuilt array, insert into the database and put into the array
    $insert = [];

    // Get associated port
    [$ospf_ifIndex, $ospf_inst, $ospf_rtr] = explode('.', $ospf_nbr_index);
    $ospf_nbr_poll['ospfv3NbrRtrId']   = $ospf_rtr;
    $ospf_nbr_poll['ospfv3NbrAddress'] = hex2ip($ospf_nbr_poll['ospfv3NbrAddress']);

    $insert['port_id'] = NULL; // Unknown
    if ($ospf_nbr_poll['ospfv3NbrIfId']) {
        if ($port = get_port_by_index_cache($device, $ospf_nbr_poll['ospfv3NbrIfId'])) {
            $insert['port_id'] = $port['port_id'];
        }
    } elseif ($ospf_ifIndex) {
        if ($port = get_port_by_index_cache($device, $ospf_ifIndex)) {
            $insert['port_id'] = $port['port_id'];
        }
    } else {
        $ids = get_entity_ids_ip_by_network('port', $ospf_nbr_poll['ospfv3NbrAddress'], generate_query_values_and($device['device_id'], 'device_id'));
        if (safe_count($ids)) {
            $insert['port_id'] = current($ids);
        }
    }

    if (!isset($ospf_nbrs_db[$ospf_nbr_index])) {
        $insert['device_id']   = $device['device_id'];
        $insert['ospf_nbr_id'] = $ospf_nbr_index;
        $insert['ospfVersionNumber'] = 'version3';

        if (is_null($insert['port_id'])) {
            $insert['port_id'] = 0; // keep compat with old db
        }
        foreach ($ospf_nbr_oids as $field => $oid) {
            // Loop the OIDs
            $insert[$field] = $ospf_nbr_poll[$oid];
        }

        dbInsertRowMulti($insert, 'ospf_nbrs');
        echo('+');
    } else {
        $ospf_nbr_db = $ospf_nbrs_db[$ospf_nbr_index];
        $db_update   = FALSE;

        $insert['ospf_nbrs_id'] = $ospf_nbr_db['ospf_nbrs_id'];
        foreach ($ospf_nbr_oids as $field => $oid) {
            // Loop the OIDs
            $insert[$field] = $ospf_nbr_poll[$oid];
            if ($ospf_nbr_db[$field] != $ospf_nbr_poll[$oid]) {
                $db_update = TRUE;
            }
        }
        if ($ospf_nbr_db['port_id'] != $insert['port_id']) {
            $db_update = TRUE;
        }
        if ($db_update) {
            $insert['ospfVersionNumber'] = 'version3';
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
rrdtool_update_ng($device, 'ospfv3-statistics', [
  'instances'  => $ospf_stats['instances'],
  'areas'      => $ospf_stats['areas'],
  'ports'      => $ospf_stats['ports'],
  'neighbours' => $ospf_stats['neighbours'],
]);

$graphs['ospfv3_neighbours'] = TRUE;
$graphs['ospfv3_areas']      = TRUE;
$graphs['ospfv3_ports']      = TRUE;

echo PHP_EOL;

// EOF
