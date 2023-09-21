<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

if (!$config['enable_pseudowires']) {
    return;
} // Pseudowires disabled

$pseudowires_db = [];
$pseudowires_db_count   = 0;
$pseudowires_snmp_count = 0;

// WARNING. Discovered all Pseudowires, but polled only 'active'
$sql = "SELECT * FROM `pseudowires` WHERE `device_id` = ?;"; // AND `pwRowStatus` = 'active';";
//$sql = "SELECT * FROM `pseudowires` WHERE `device_id` = ? AND `pwRowStatus` = 'active';";

foreach (dbFetchRows($sql, [$device['device_id']]) as $entry) {
    $pseudowires_db_count++; // Fetch all entries for correct counting, but skip inactive/deleted
    if ($entry['pwRowStatus'] !== 'active') {
        continue;
    }

    $index = $entry['pwIndex'];

    $pseudowires_db[$entry['mib']][$index] = $entry;
}

if (safe_empty($pseudowires_db)) {
    return;
} // Pseudowires not exist, exit

$table_rows = [];

print_cli_data_field("MIBs", 2);

foreach (array_keys($pseudowires_db) as $mib) {
    // NOTE. Multiple pseudoware MIBs on single device theoretically impossible, but keep this common logic
    echo(" $mib ");
    $mib_lower = strtolower($mib);
    $oids      = $config['mibs'][$mib]['pseudowire']['oids'];

    // Cache SNMP data
    $cache_pseudowires[$mib_lower] = [];
    foreach ($oids as $oid_type => $oid_entry) {
        $cache_pseudowires[$mib_lower] = snmpwalk_cache_oid($device, $oid_entry['oid'], $cache_pseudowires[$mib_lower], $mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
        if ($oid_type === 'OperStatus' && !snmp_status()) {
            break;
        }
    }
    $pseudowire_polled_time = time(); // Store polled time for current MIB

    print_debug_vars($cache_pseudowires[$mib_lower]);

    //pseudowires_db_count   += count(pseudowires_db[$mib_lower]);
    $pseudowires_snmp_count += count($cache_pseudowires[$mib_lower]);

    foreach ($pseudowires_db[$mib] as $index => $pw) {
        $rrd_filename = "pseudowire-" . $mib_lower . '-' . $index . ".rrd";
        $rrd_uptime   = "pseudowire-" . $mib_lower . '-uptime-' . $index . ".rrd";
        $rrd_ds       = '';

        if (isset($cache_pseudowires[$mib_lower][$index])) {
            $pw_poll = &$cache_pseudowires[$mib_lower][$index];

            // Uptime graph
            $pw_uptime = timeticks_to_sec($pw_poll[$oids['Uptime']['oid']]); // Convert uptime to sec
            rrdtool_create($device, $rrd_uptime, "DS:Uptime:GAUGE:600:0:U ");
            rrdtool_update($device, $rrd_uptime, "N:" . $pw_uptime);
            //$graphs['pseudowire_uptime'] = TRUE; // not a device graph

            // Bits & Packets graphs
            $pw_values = [];
            foreach (['InOctets', 'OutOctets', 'InPkts', 'OutPkts'] as $oid_type) {
                if (!isset($oids[$oid_type])) {
                    break;
                }

                $rrd_ds      .= 'DS:' . $oid_type . ':DERIVE:600:0:' . $config['max_port_speed'] . ' ';
                $pw_values[] = $pw_poll[$oids[$oid_type]['oid']];
            }
            if (count($pw_values)) {
                rrdtool_create($device, $rrd_filename, $rrd_ds);
                rrdtool_update($device, $rrd_filename, $pw_values);
                //$graphs['pseudowire_bits'] = TRUE; // not a device graph
                //$graphs['pseudowire_pkts'] = TRUE; // not a device graph
            }

            // Event
            $pw_operstatus    = $pw_poll[$oids['OperStatus']['oid']];
            $pw_poll['event'] = $config['mibs'][$mib]['pseudowire']['states'][$pw_operstatus]['event'];

            // Last changed
            if (empty($pw['last_change'])) {
                // If last change never set, use current time
                $pw_poll['last_change'] = $pseudowire_polled_time - $pw_uptime;
            } elseif ($pw['pwOperStatus'] != $pw_operstatus) {
                // Pseudowire changed, log and set last_change
                $pw_poll['last_change'] = $pseudowire_polled_time; // - $pw_uptime;
                if ($pw['pwOperStatus']) // Log only if old status not empty
                {
                    log_event('Pseudowire changed: [#' . $pw['pwID'] . '] ' . $pw['pwOperStatus'] . ' -> ' . $pw_operstatus, $device, 'pseudowire', $pw['pseudowire_id'], 'warning');
                }
            } elseif ($pw['pwUptime'] > $pw_uptime) {
                // Pseudowire flapped, log and set last_change
                $pw_poll['last_change'] = $pseudowire_polled_time; // - $pw_uptime;
                if ($pw['pwOperStatus']) // Log only if old status not empty
                {
                    log_event('Pseudowire flapped: [#' . $pw['pwID'] . '] time ' . format_uptime($pw_uptime) . ' ago', $device, 'pseudowire', $pw['pseudowire_id']);
                }
            } else {
                // If status not changed, leave old last_change
                $pw_poll['last_change'] = $pw['last_change'];
            }

            // Metrics
            $metrics                   = [];
            $metrics['pwOperStatus']   = $pw_poll[$oids['OperStatus']['oid']];
            $metrics['pwRemoteStatus'] = $pw_poll[$oids['RemoteStatus']['oid']];
            $metrics['pwLocalStatus']  = $pw_poll[$oids['LocalStatus']['oid']];
            $metrics['event']          = $pw_poll['event'];
            $metrics['pwUptime']       = $pw_uptime;
            $metrics['last_change']    = $pw_poll['last_change'];

            // Check entity
            check_entity('pseudowire', $pw, $metrics);

            // Update SQL State
            //dbUpdate($metrics, 'pseudowires', '`pseudowire_id` = ?', [$pw['pseudowire_id']]);
            dbUpdateRowMulti(array_merge([ 'pseudowire_id' => $pw['pseudowire_id'] ], $metrics), 'pseudowires', 'pseudowire_id');

            // Add table row
            $table_row    = [];
            $table_row[]  = $pw['pwID'];
            $table_row[]  = $pw['mib'];
            $table_row[]  = $pw['pwType'];
            $table_row[]  = $pw['pwPsnType'];
            $table_row[]  = $pw['peer_addr'];
            $table_row[]  = $metrics['pwOperStatus'];
            $table_row[]  = format_uptime($pw_uptime);
            $table_rows[] = $table_row;
            unset($table_row);

        }
    }
}

dbProcessMulti('pseudowires');

echo(PHP_EOL);

$headers = ['%WpwID%n', '%WMIB%n', '%WType%n', '%WPsnType%n', '%WPeer%n', '%WOperStatus%n', '%WUptime%n'];
print_cli_table($table_rows, $headers);

if ($pseudowires_db_count != $pseudowires_snmp_count) {
    // Force rediscover Pseudowires
    print_debug("Pseudowires total count for this device changed (DB: $pseudowires_db_count, SNMP: $pseudowires_snmp_count), force rediscover Pseudowires.");
    force_discovery($device, 'pseudowires');
}

unset($pseudowires_db, $cache_pseudowires, $pseudowire_polled_time, $pw, $pw_poll, $pw_values, $pw_uptime, $oids, $metrics, $table_rows);

// EOF
