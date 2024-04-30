<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

// Include description parser (usually ifAlias) if config option set
$custom_port_parser = FALSE;
if (isset($config['port_descr_parser']) && $config['port_descr_parser'] != FALSE &&
    is_file($config['install_dir'] . "/" . $config['port_descr_parser'])) {
    include_once($config['install_dir'] . "/" . $config['port_descr_parser']);

    if (function_exists('custom_port_parser')) {
        $custom_port_attribs = ['type', 'descr', 'circuit', 'speed', 'notes'];
        $custom_port_parser  = TRUE;
    } else {
        print_error("ERROR: Rewrite your custom ports parser in file [" . $config['install_dir'] . "/" . $config['port_descr_parser'] . "], using a function custom_port_parser().");
        $custom_port_parser = 'old';
    }
}

// Cache ports table from DB
$ports = [];

// FIXME -- this stuff is a little messy, looping the array to make an array just seems wrong. :>
//       -- i can make it a function, so that you don't know what it's doing.
//       -- $ports = adamasMagicFunction($ports_db); ?

$query = 'SELECT * FROM `ports` WHERE `device_id` = ?';

//$ports_db = dbFetchRows($query, [ $device['device_id'] ]); // Termporary var, no need to assign
$ports_attribs = get_device_entities_attribs($device['device_id'], 'port'); // Get all attribs
//print_vars($ports_attribs);
foreach (dbFetchRows($query, [$device['device_id']]) as $port) {
    if (isset($ports_attribs['port'][$port['port_id']])) {
        $port = array_merge($port, $ports_attribs['port'][$port['port_id']]);
    }
    $ports[$port['ifIndex']] = $port;
}
$ports_ignored_count_db = (int)get_entity_attrib('device', $device, 'ports_ignored_count'); // Cache last ports ignored count

// Ports module options
$ports_modules = [];                                                                        // Ports sub-modules enabled/disabled
foreach (array_keys($config) as $ports_module) {
    if (!str_starts($ports_module, 'enable_ports_')) {
        continue;
    } // Filter only enable_ports_* config entries
    $ports_module = str_replace('enable_ports_', '', $ports_module);

    //$ports_modules[$ports_module] = isset($attribs['enable_ports_' . $ports_module]) ? (bool)$attribs['enable_ports_' . $ports_module] : $config['enable_ports_' . $ports_module];
    $ports_modules[$ports_module] = is_module_enabled($device, 'ports_' . $ports_module);
}
// Additionally, force enable separate walk feature for some device oses, but only if ports total count >10
$ports_module = 'separate_walk';
// Model definition can override os definition
$model_separate_walk = isset($model['ports_' . $ports_module]) && $model['ports_' . $ports_module];
if (!$ports_modules[$ports_module] && $model_separate_walk) {
    if (isset($attribs['enable_ports_' . $ports_module]) && !$attribs['enable_ports_' . $ports_module]) {
    } // forcing disabled in device config
    else {
        $ports_total_count            = $ports_ignored_count_db + dbFetchCell('SELECT COUNT(*) FROM `ports` WHERE `device_id` = ? AND `deleted` = 0', [$device['device_id']]);
        $ports_modules[$ports_module] = $ports_total_count > 10;
        if (OBS_DEBUG && $ports_modules[$ports_module]) {
            print_debug('Forced ports separate walk feature!');
        }
    }
}
print_debug_vars($ports_modules);

$table_rows = [];

// Build SNMP Cache Array

// IF-MIB OIDs that go into the ports table

$data_oids_ifEntry  = [
    // ifEntry
    'ifDescr', 'ifType', 'ifMtu', 'ifSpeed', 'ifPhysAddress',
    'ifAdminStatus', 'ifOperStatus', 'ifLastChange',
];
$data_oids_ifXEntry = [
    // ifXEntry
    'ifName', 'ifAlias', 'ifHighSpeed', 'ifPromiscuousMode', 'ifConnectorPresent',
];
$data_oids          = array_merge($data_oids_ifEntry,
                                  $data_oids_ifXEntry,
                                  [ 'ifDuplex', 'ifTrunk', 'ifVlan' ]); // Add additional oids

// IF-MIB statistics OIDs that go into RRD

$stat_oids_ifEntry = [
    // ifEntry
    'ifInOctets', 'ifOutOctets',
    'ifInUcastPkts', 'ifOutUcastPkts',
    'ifInNUcastPkts', 'ifOutNUcastPkts', // Note, (In|Out)NUcastPkts deprecated, for HC counters use Broadcast+Multicast instead
    'ifInDiscards', 'ifOutDiscards',
    'ifInErrors', 'ifOutErrors',
    'ifInUnknownProtos',
];

$stat_oids_ifXEntry = [
    // ifXEntry
    'ifInMulticastPkts', 'ifOutMulticastPkts',
    'ifInBroadcastPkts', 'ifOutBroadcastPkts',
    // HC counters
    'ifHCInOctets', 'ifHCOutOctets',
    'ifHCInUcastPkts', 'ifHCOutUcastPkts',
    'ifHCInMulticastPkts', 'ifHCOutMulticastPkts',
    'ifHCInBroadcastPkts', 'ifHCOutBroadcastPkts',
];

// This oids definitions used only for Upstream/Downstream interface types
$upstream_oids   = [
    // ifEntry
    'ifInOctets', 'ifInUcastPkts', 'ifInNUcastPkts', 'ifInDiscards', 'ifInErrors',
    // ifXEntry
    'ifInMulticastPkts', 'ifInBroadcastPkts', 'ifHCInOctets', 'ifHCInUcastPkts', 'ifHCInMulticastPkts', 'ifHCInBroadcastPkts',
];
$downstream_oids = [
    // ifEntry
    'ifOutOctets', 'ifOutUcastPkts', 'ifOutNUcastPkts', 'ifOutDiscards', 'ifOutErrors',
    // ifXEntry
    'ifOutMulticastPkts', 'ifOutBroadcastPkts', 'ifHCOutOctets', 'ifHCOutUcastPkts', 'ifHCOutMulticastPkts', 'ifHCOutBroadcastPkts',
];

// Remove HC oids from stat arrays for SNMP v1 or 64bit module disabled
if ($device['snmp_version'] === 'v1' || !$ports_modules['64bit']) {
    $hc_oids            = [
        // HC counters
        'ifHCInOctets', 'ifHCOutOctets',
        'ifHCInUcastPkts', 'ifHCOutUcastPkts',
        'ifHCInMulticastPkts', 'ifHCOutMulticastPkts',
        'ifHCInBroadcastPkts', 'ifHCOutBroadcastPkts',
    ];
    $stat_oids_ifXEntry = array_diff($stat_oids_ifXEntry, $hc_oids);
    $upstream_oids      = array_diff($upstream_oids, $hc_oids);
    $downstream_oids    = array_diff($downstream_oids, $hc_oids);
}

// Merge stat oids
$stat_oids = array_merge($stat_oids_ifEntry, $stat_oids_ifXEntry);

// PAgP OIDs

// PAgP disabled since r7987, while not moved to new polling style
//$pagp_oids = array('pagpOperationMode', 'pagpPortState', 'pagpPartnerDeviceId', 'pagpPartnerLearnMethod', 'pagpPartnerIfIndex', 'pagpPartnerGroupIfIndex',
//                   'pagpPartnerDeviceName', 'pagpEthcOperationMode', 'pagpDeviceId', 'pagpGroupIfIndex');

//$ifmib_oids = array_merge($data_oids, $stat_oids);

print_cli_data_field("Caching Oids");
$port_stats    = [];
$include_stats = [];
if (is_device_mib($device, "IF-MIB")) {
    $inc_start = microtime(TRUE); // MIB timing start

    if (!$ports_modules['separate_walk']) {
        print_debug("Used full table ifEntry/ifXEntry snmpwalk.");
        $ifmib_oids = [ 'ifEntry', 'ifXEntry' ];
        foreach ($ifmib_oids as $oid) {
            $has_name = 'has_' . $oid;
            echo("$oid ");

            // End walk before Oid have effect only for nobulk!
            if ($oid === 'ifEntry') {
                // stop walk after ifOutErrors (next ifOutQLen)
                snmpwalk_oid_end('ifOutQLen');
            } else {
                // stop walk after ifAlias (next ifCounterDiscontinuityTime)
                snmpwalk_oid_end('ifCounterDiscontinuityTime');
            }

            $port_stats = snmpwalk_cache_oid($device, $oid, $port_stats, "IF-MIB");
            $$has_name  = snmp_status() || snmp_error_code() === OBS_SNMP_ERROR_REQUEST_NOT_COMPLETED; // $has_ifEntry, $has_ifXEntry
            //print_vars($$has_name);
            if ($oid === 'ifEntry') {
                // Store error_code, 1000 == not exist table, 2 and 3 - not complete request
                $has_ifEntry_error_code = snmp_error_code();
            }
        }

    } else {

        print_debug("Used separate data tables snmpwalk and per port snmpget.");

        $has_ifEntry = FALSE;
        // Data fields
        // ifDescr, ifAlias, ifName, ifType, ifOperStatus
        foreach ([ 'ifDescr', 'ifType', 'ifOperStatus', 'ifAdminStatus' ] as $oid) {
            echo("$oid ");
            $port_stats             = snmpwalk_cache_oid($device, $oid, $port_stats, "IF-MIB");
            $has_ifEntry            = $has_ifEntry || snmp_status();
            $has_ifEntry_error_code = snmp_error_code();
        }
        $has_ifXEntry = FALSE;
        foreach ([ 'ifAlias', 'ifName', 'ifHighSpeed' ] as $oid) {
            echo("$oid ");
            $port_stats   = snmpwalk_cache_oid($device, $oid, $port_stats, "IF-MIB");
            $has_ifXEntry = $has_ifXEntry || snmp_status();
        }

        // Per port snmpget
        if ($port_stats) {
            // Collect oids for per port snmpget
            if ($has_ifXEntry) {
                $port_oids = array_merge($stat_oids_ifXEntry, $stat_oids_ifEntry);
                $port_oids = array_merge($port_oids, array_diff($data_oids_ifEntry, [ 'ifDescr', 'ifType', 'ifOperStatus', 'ifAdminStatus' ]));
                $port_oids = array_merge($port_oids, array_diff($data_oids_ifXEntry, [ 'ifAlias', 'ifName', 'ifHighSpeed' ]));
            } else {
                $port_oids = array_merge($stat_oids_ifEntry,
                                         array_diff($data_oids_ifEntry, [ 'ifDescr', 'ifType', 'ifOperStatus' ]));
            }

            // Use snmpget for each (not ignored) port
            // NOTE. This method reduce polling time when too many ports (>100)
            echo(implode(' ', $port_oids) . ", ifIndex: ");
            foreach ($port_stats as $ifIndex => $port) {
                $port_disabled = isset($ports[$ifIndex]['disabled']) && $ports[$ifIndex]['disabled']; // Port polling disabled from WUI
                // On some Brocade NOS
                if ($port['ifOperStatus'] === '-1') {
                    $port['ifOperStatus'] = 'unknown';
                }

                if (!$port_disabled && is_port_valid($device, $port)) {
                    echo("\nifIndex $ifIndex (ifAdminStatus = {$port['ifAdminStatus']}, ifOperStatus = {$port['ifOperStatus']}). ");

                    // Skip get snmp data when port down and not changed from previous
                    $port_skip_data = $ports[$ifIndex]['ifOperStatus'] === $port['ifOperStatus'] &&
                                      $ports[$ifIndex]['ifAdminStatus'] === $port['ifAdminStatus'] &&
                                      ($port['ifOperStatus'] === 'down' || $port['ifOperStatus'] === 'lowerLayerDown' ||
                                       $port['ifOperStatus'] === 'notPresent' || $port['ifAdminStatus'] === 'down');

                    if ($port_skip_data) {
                        print_debug("Skipped snmpget for ifOperStatus or ifAdminStatus down");

                        $port_has_64bit = $device['snmp_version'] !== 'v1' && $ports_modules['64bit'] && $ports[$ifIndex]['port_64bit'];
                        foreach ($port_oids as $port_oid) {
                            // Copy previous values
                            if ($port_has_64bit && str_starts_with($port_oid, 'ifHC')) {
                                // Copy HC counter
                                $port_oid_old                    = str_replace('ifHC', 'if', $port_oid);
                                $port_stats[$ifIndex][$port_oid] = $ports[$ifIndex][$port_oid_old];
                            } else {
                                $port_stats[$ifIndex][$port_oid] = $ports[$ifIndex][$port_oid] ?? '0'; // ifInUnknownProtos not stored in db
                            }
                        }
                        print_debug_vars($port_stats[$ifIndex]);
                    } else {
                        $port_oid   = implode(".$ifIndex ", $port_oids) . ".$ifIndex";
                        $port_stats = snmp_get_multi_oid($device, $port_oid, $port_stats, "IF-MIB");
                    }
                }
            }
        }
    }
    // MIB timing
    $include_stats['IF-MIB'] = elapsed_time($inc_start);
    // End IF-MIB permitted
}
//else {
// This part for devices who not have IF-MIB stats, but have own vendor tree with ports
//}
echo PHP_EOL;                                                         // End IF-MIB section

//////////
$private_stats = [];
foreach (get_device_mibs_permitted($device) as $mib) {
    $private_stats[] = merge_private_mib($device, 'ports', $mib, $port_stats, NULL);
}
$include_stats = array_merge($include_stats, ...$private_stats);
unset($private_stats);
print_debug_vars($include_stats);
////////

// Prevent mark ports as DELETED when ifEntry snmpwalk return not complete data!
$allow_delete_ports = $has_ifEntry_error_code !== OBS_SNMP_ERROR_REQUEST_NOT_COMPLETED && $has_ifEntry_error_code !== OBS_SNMP_ERROR_TOO_LONG_RESPONSE;

// Store polled time exactly after walk IF-MIB, for more correct port speed calculate!
$polled = time();

// Device uptime and polled time (required for ifLastChange)
if (isset($cache['devices']['uptime'][$device['device_id']])) {
    $device_uptime = &$cache['devices']['uptime'][$device['device_id']];
} else {
    print_error("Device uptime not cached, ifLastChange will incorrect. Check polling system module.");
}

// Subset of IF-MIB OIDs that we put into the state table
$stat_oids_db = [ 'ifInOctets', 'ifOutOctets', 'ifInErrors', 'ifOutErrors', 'ifInUcastPkts', 'ifOutUcastPkts',
                  'ifInNUcastPkts', 'ifOutNUcastPkts', 'ifInBroadcastPkts', 'ifOutBroadcastPkts',
                  'ifInMulticastPkts', 'ifOutMulticastPkts', 'ifInDiscards', 'ifOutDiscards' ];

// Subset of IF-MIB OIDs that we put into the ports table
$data_oids_db = array_diff($data_oids, ['ifLastChange']);             // remove ifLastChange, because it added separate
$data_oids_db = array_merge($data_oids_db, ['port_label', 'port_label_short', 'port_label_base', 'port_label_num']);

// Additional MIBS and modules
$process_port_functions = [];                                         // collect processing functions
$process_port_db        = [];                                         // collect processing db fields

// Additionally include per MIB functions and snmpwalks (uses include_once)
$port_stats_count = safe_count($port_stats);
$include_lib      = TRUE;
$include_dir      = "includes/polling/ports/";
include("includes/include-dir-mib.inc.php");

if (!safe_empty($include_stats)) {
    // Set per mib polling times
    $device_state['poller_ports_perf'] = $include_stats;
}
print_debug_vars($include_stats);
unset($include_stats);

if (safe_count($port_stats)) {

    // If the device is cisco, pull a few cisco-specific MIBs and try to get vlan data from CISCO-VTP-MIB
    /* PAgP disabled since r7987, while not moved to new polling style
    if ($device['os_group'] === "cisco") {
      //FIXME. All PAGP operations should be moved to separate "stacks" module and separate table (not in ports table)
      foreach ($pagp_oids as $oid)
      {
        $port_stats = snmpwalk_cache_oid($device, $oid, $port_stats, "CISCO-PAGP-MIB");
        // Break if no PAGP tables on device
        if ($oid == 'pagpOperationMode' && $GLOBALS['snmp_status'] === FALSE) { break; }
      }
    }
    */

    // End Building SNMP Cache Array

    $graphs['bits'] = TRUE; // Create global device_bits graph, since we have ports.

    print_debug_vars($port_stats);
}

// New interface detection
$ports_ignored_count = 0;                                             // Counting ignored ports
$ports_deleted_count = 0;                                             // Counting deleted ports
$ports_db_deleted    = [];                                            // MultiUpdate deleted ports db
$ports_db_state      = [];                                            // MultiUpdate state ports db
foreach ($port_stats as $ifIndex => $port) {
    // Always use ifIndex from index part (not from Oid!),
    // Oid can have incorrect ifIndex, ie:
    // ifIndex.0 = 1
    $port['ifIndex'] = $ifIndex;

    // On some Brocade NOS
    if ($port['ifOperStatus'] === '-1') {
        $port['ifOperStatus'] = 'unknown';
    }

    if (is_port_valid($device, $port)) {
        if (!is_array($ports[$port['ifIndex']])) {
            $port_insert             = ['device_id' => $device['device_id'],
                                        'ifIndex'   => $ifIndex,
                                        'ignore'    => isset($port['ignore']) ? $port['ignore'] : '0',
                                        'disabled'  => isset($port['disabled']) ? $port['disabled'] : '0'];
            $port_id                 = dbInsert(['device_id' => $device['device_id'], 'ifIndex' => $ifIndex], 'ports');
            $ports[$port['ifIndex']] = dbFetchRow("SELECT * FROM `ports` WHERE `port_id` = ?", [$port_id]);
            print_message("Adding: " . $port['ifDescr'] . "(" . $ifIndex . ")(" . $ports[$port['ifIndex']]['port_id'] . ")");
        } elseif ($ports[$ifIndex]['deleted'] == "1") {
            $ports_db_deleted[] = [
                // UNIQUE fields
                'port_id' => $ports[$ifIndex]['port_id'], 'ifIndex' => $ifIndex, 'device_id' => $device['device_id'],
                // Update this fields
                'deleted' => '0', 'ifLastChange' => date('Y-m-d H:i:s', $polled)
            ];
            log_event("Interface DELETED mark removed", $device, 'port', $ports[$ifIndex]);
            $ports[$ifIndex]['deleted'] = "0";
        }
    } else {
        if (isset($ports[$port['ifIndex']]) && $ports[$port['ifIndex']]['deleted'] != '1' && $allow_delete_ports) {
            $ports_db_deleted[] = [
                // UNIQUE fields
                'port_id' => $ports[$ifIndex]['port_id'], 'ifIndex' => $ifIndex, 'device_id' => $device['device_id'],
                // Update this fields
                'deleted' => '1', 'ifLastChange' => date('Y-m-d H:i:s', $polled)
            ];
            //log_event("Interface was marked as DELETED", $device, 'port', $ports[$ifIndex]);
            $ports[$ifIndex]['deleted'] = "1";
            $ports_deleted_count++;
        }
        $ports_ignored_count++; // Counting ignored ports
    }
}

if (!$allow_delete_ports) {
    log_event("WARNING! Ports snmpwalk did not complete. Try to increase SNMP timeout on the device properties page.", $device, 'device', $device['device_id'], 7);
} elseif ($ports_deleted_count > 0) {
    // Recheck device is snmpable, can be case when device down while polling
    $device['snmpable'] = is_snmpable($device);
    if (!$device['snmpable']) {
        // When device down, prevent other ports updates
        log_event("WARNING! Poll ports ended prematurely because the device became unavailable. Try to increase SNMP timeout on the device properties page.", $device, 'device', $device['device_id'], 7);
        $device['status'] = 0;
        return;
    }

    foreach ($ports_db_deleted as $entry) {
        if ($entry['deleted'] == '1') {
            log_event("Interface was marked as DELETED", $device, 'port', $entry['port_id']);
        }
    }
}

if ($ports_ignored_count !== $ports_ignored_count_db) {
    set_entity_attrib('device', $device, 'ports_ignored_count', $ports_ignored_count);
}
// End New interface detection

echo(PHP_EOL . PHP_EOL);

// Loop ports in the DB and update where necessary
foreach ($ports as $port) {
    // Notes:
    // $port_stats - array of ports from snmpwalks
    // $this_port  - link to port array from snmpwalk
    // $ports      - array of ports based on current db entries
    // $port       - current port array from db
    if ($port['deleted']) {
        continue;
    } // Skip updating RRDs and DB if interface marked as DELETED (also skipped bad_if's)

    if ($port_stats[$port['ifIndex']] && $port['disabled'] != "1") {
        // Check to make sure Port data is cached.
        $this_port = &$port_stats[$port['ifIndex']];

        $port['update'] = [];
        $port['state']  = []; // State field for update

        // Poll time and period
        if (isset($this_port['polled']) && is_intnum($this_port['polled']) &&
            ($this_port['polled'] > OBS_MIN_UNIXTIME)) {
            // See SPECTRA-LOGIC-STRATA-MIB definition
        } else {
            $this_port['polled'] = $polled;
        }
        $polled_period                = $this_port['polled'] - $port['poll_time'];
        $port['state']['poll_time']   = $this_port['polled'];
        $port['state']['poll_period'] = $polled_period;
        // $polled_period = $polled - $port['poll_time'];
        // $port['state']['poll_time'] = $polled;
        // $port['state']['poll_period'] = $polled_period;

        $this_port['port_id'] = $port['port_id'];
        $this_port['ifIndex'] = $port['ifIndex'];
        $this_port_indexes    = ['port_id' => $ports[$port['ifIndex']]['port_id'], 'ifIndex' => $port['ifIndex'], 'device_id' => $device['device_id']]; // UNIQUE port indexes

        // Store original port walked OIDs for debugging later
        if ($config['debug_port']['spikes'] || $config['debug_port'][$port['port_id']]) {
            $debug_port = $this_port; // DEBUG
        }

        //print_vars($process_port_functions);
        foreach ($process_port_functions as $func => $ok) {
            if ($ok && function_exists('process_port_' . $func)) {
                if (OBS_DEBUG > 1) {
                    print_debug("Processing port ifIndex " . $this_port['ifIndex'] . " with function process_port_{$func}() ");
                }
                // Note, used call by array, because parameters for call_user_func() are not passed by reference
                call_user_func_array('process_port_' . $func, [&$this_port, $device, $port]);
            }
        }

        /* Start process_port_label() */
        // Before process_port_label()
        // Fix ord (UTF-8) chars, ie:
        // ifAlias.3 = Conexi<F3>n de <E1>rea local* 3
        foreach ([ 'ifAlias', 'ifDescr', 'ifName' ] as $oid_fix) {
            if (!isset($this_port[$oid_fix]) ||
                ($oid_fix !== 'ifAlias' && is_hex_string($this_port[$oid_fix]))) {
                // In cases, when device have memory leak, they return hex string instead UTF, rewritten in process_port_label()
                continue;
            }

            $this_port[$oid_fix] = snmp_fix_string($this_port[$oid_fix]);
        }
        process_port_label($this_port, $device);
        /* End process_port_label() */
        //print_vars($this_port);

#    // Copy ifHC[In|Out] values to non-HC if they exist
#    // Check if they're greater than zero to work around stupid devices which expose HC counters, but don't populate them. HERPDERP. - adama
#    if ($device['os'] == "netapp") { $hc_prefixes = array('HC', '64'); } else { $hc_prefixes = array('HC'); }
#    foreach ($hc_prefixes as $hc_prefix)
#    {
#      foreach (array('Octets', 'UcastPkts', 'BroadcastPkts', 'MulticastPkts') as $hc)
#      {
#        $hcin = 'if'.$hc_prefix.'In'.$hc;
#        $hcout = 'if'.$hc_prefix.'Out'.$hc;
#        if (is_numeric($this_port[$hcin]) && $this_port[$hcin] > 0 && is_numeric($this_port[$hcout]) && $this_port[$hcout] > 0)
#        {
#          // echo(" ".$hc_prefix." $hc, ");
#          $this_port['ifIn'.$hc]  = $this_port[$hcin];
#          $this_port['ifOut'.$hc] = $this_port[$hcout];
#        }
#      }
#    }

        // Here special checks for Upstream/Downstream ports, because it have only In or only Out counters
        if (str_contains($this_port['ifType'], 'Upstream')) {
            // Upstream has only In counters
            foreach ($upstream_oids as $oid_in) {
                $oid_out = str_replace('In', 'Out', $oid_in);
                if (is_numeric($this_port[$oid_in]) && !is_numeric($this_port[$oid_out])) {
                    $this_port[$oid_out] = 0; // Set it all to zero
                }
            }
        } elseif (str_contains($this_port['ifType'], 'Downstream')) {
            // Downstream has only Out counters
            foreach ($downstream_oids as $oid_out) {
                $oid_in = str_replace('Out', 'In', $oid_out);
                if (is_numeric($this_port[$oid_out]) && !is_numeric($this_port[$oid_in])) {
                    $this_port[$oid_in] = 0; // Set it all to zero
                }
            }
        }

        // If we're not using SNMPv1, assume there are 64-bit values and overwrite the 32-bit OIDs.
        if ($device['snmp_version'] !== 'v1' && $ports_modules['64bit']) {
            $hc_prefix      = 'HC';
            $port_has_64bit = is_numeric($this_port['if' . $hc_prefix . 'InOctets']) && is_numeric($this_port['if' . $hc_prefix . 'OutOctets']);

            // We've never tested for 64bit. Lets do it now. Lots of devices seem to not support 64bit counters for all ports.
            if (safe_empty($port['port_64bit'])) {
                // We have 64-bit traffic counters. Lets set port_64bit
                if ($port_has_64bit) {
                    $port['port_64bit']           = 1;
                    $port['update']['port_64bit'] = 1;
                } else {
                    $port['port_64bit']           = 0;
                    $port['update']['port_64bit'] = 0;
                }
            } elseif ($has_ifXEntry && $port_has_64bit && !$port['port_64bit']) {
                // Port changed to 64-bit
                $port['port_64bit']           = 1;
                $port['update']['port_64bit'] = 1;
                log_event('Interface changed: [HC] 64bit counters enabled (may cause disposable spike)', $device, 'port', $port);
            }

            $port_has_mcbc = is_numeric($this_port['ifInBroadcastPkts']) && is_numeric($this_port['ifOutBroadcastPkts']) &&
                             is_numeric($this_port['ifInMulticastPkts']) && is_numeric($this_port['ifOutMulticastPkts']);

            if ($port['port_mcbc'] == NULL) {
                // We have Broadcast/Multicast traffic counters. Let's set port_mcbc
                if ($port_has_mcbc) {
                    $port['port_mcbc']           = 1;
                    $port['update']['port_mcbc'] = 1;
                } else {
                    $port['port_mcbc']           = 0;
                    $port['update']['port_mcbc'] = 0;
                }
            } elseif ($has_ifXEntry && $port_has_mcbc && !$port['port_mcbc']) {
                // Port acquired multicast/broadcast!
                $port['port_mcbc']           = 1;
                $port['update']['port_mcbc'] = 1;
                log_event('Interface changed: Separated Multicast/Broadcast statistics appeared.', $device, 'port', $port);
            }

            //elseif (!$port_has_64bit && $port['port_64bit'])
            //{
            //  // Port changed to 32-bit
            //  $port['port_64bit'] = 0;
            //  $port['update']['port_64bit'] = 0;
            //  log_event('Interface changed: [HC] -> Counter32', $device, 'port', $port);
            //}

            if ($port['port_64bit']) {
                print_debug("64-bit, ");
                foreach ([ 'Octets', 'UcastPkts', 'BroadcastPkts', 'MulticastPkts' ] as $hc) {
                    $hcin                     = 'if' . $hc_prefix . 'In' . $hc;
                    $hcout                    = 'if' . $hc_prefix . 'Out' . $hc;
                    $this_port['ifIn' . $hc]  = $this_port[$hcin];
                    $this_port['ifOut' . $hc] = $this_port[$hcout];
                }
                // Additionally override (In|Out)NUcastPkts
                // see: http://jira.observium.org/browse/OBSERVIUM-1749
                $this_port['ifInNUcastPkts']  = int_add($this_port['ifInBroadcastPkts'], $this_port['ifInMulticastPkts']);
                $this_port['ifOutNUcastPkts'] = int_add($this_port['ifOutBroadcastPkts'], $this_port['ifOutMulticastPkts']);
            }
        } elseif ($port['port_64bit']) {
            $port['port_64bit']           = 0;
            $port['update']['port_64bit'] = 0;
            if ($port['port_mcbc']) {
                $port['port_mcbc']           = 0;
                $port['update']['port_mcbc'] = 0;
            }
            log_event('Interface changed: [HC] 64bit counters disabled (may cause disposable spike)', $device, 'port', $port);
        }

        // rewrite the ifPhysAddress
        // IF-MIB::ifPhysAddress.2 = STRING: 66:c:9b:1b:62:7e
        // IF-MIB::ifPhysAddress.2 = Hex-STRING: 00 02 99 09 E9 84
        $this_port['ifPhysAddress'] = mac_zeropad($this_port['ifPhysAddress']);

        // ifSpeed processing
        process_port_speed($this_port, $device, $port);
        $port['alert_array']['ifSpeed'] = is_numeric($this_port['ifSpeed']) ? $this_port['ifSpeed'] : 0;
        if (is_numeric($this_port['ifHighSpeed'])) {
            $port['alert_array']['ifHighSpeed'] = $this_port['ifHighSpeed'];
        }

        // Simple override of ifAlias -- mostly for testing
        if (isset($config['ports']['ifAlias_map']['ifIndex'][$device['hostname']][$port['ifIndex']])) {
            $this_port['ifAlias'] = $config['ports']['ifAlias_map']['ifIndex'][$device['hostname']][$port['ifIndex']];
        }
        if (isset($config['ports']['ifAlias_map']['ifName'][$device['hostname']][$port['ifName']])) {
            $this_port['ifAlias'] = $config['ports']['ifAlias_map']['ifName'][$device['hostname']][$port['ifName']];
        }

        // Update TrustSec
        if ($this_port['encrypted']) {
            if ($port['encrypted'] === '0') {
                log_event("Interface is now encrypted", $device, 'port', $port);
                $port['update']['encrypted'] = '1';
            }
        } elseif ($port['encrypted'] === '1') {
            log_event("Interface is no longer encrypted", $device, 'port', $port);
            $port['update']['encrypted'] = '0';
        }

        // Make sure ifOperStatus is valid (FIXME. not exist statuses already "filtered" in is_port_valid())
        if (isset($this_port['ifOperStatus']) &&
            !in_array($this_port['ifOperStatus'], [ 'testing', 'notPresent', 'dormant', 'down', 'lowerLayerDown', 'unknown', 'up', 'monitoring' ])) {
            $this_port['ifOperStatus'] = 'unknown';
        }

        if (isset($this_port['ifAdminStatus']) &&
            !in_array($this_port['ifAdminStatus'], [ 'up', 'down', 'testing' ])) {
            $this_port['ifAdminStatus'] = ''; // or NULL?
        }

        if (isset($this_port['ifConnectorPresent']) &&
            !in_array($this_port['ifConnectorPresent'], [ 'true', 'false' ])) {
            $this_port['ifConnectorPresent'] = NULL;
        }

        // Update IF-MIB data

        $log_event = [];
        foreach ($data_oids_db as $oid) {
            if ($port[$oid] != $this_port[$oid]) {
                if (isset($this_port[$oid])) {
                    $port['update'][$oid] = $this_port[$oid];
                    $msg                  = "[$oid] '" . $port[$oid] . "' -> '" . $this_port[$oid] . "'";
                } else {
                    $port['update'][$oid] = ['NULL'];
                    $msg                  = "[$oid] '" . $port[$oid] . "' -> NULL";
                }
                if ($oid === 'ifOperStatus' && ($port[$oid] === 'up' || $port[$oid] === 'down') && isset($this_port[$oid])) {
                    // Specific log_event for port Up/Down
                    log_event('Interface ' . ucfirst($this_port[$oid]) . ": [$oid] '" . $port[$oid] . "' -> '" . $this_port[$oid] . "'", $device, 'port', $port, 'warning');
                } else {
                    $log_event[] = $msg;
                }
                if (OBS_DEBUG) {
                    echo($msg . " ");
                } // else { echo($oid . " "); }
            }
        }

        // ifLastChange
        if (isset($this_port['ifLastChange']) && $this_port['ifLastChange'] != '') {
            // Convert ifLastChange from timetick to timestamp
            /**
             * The value of sysUpTime at the time the interface entered
             * its current operational state. If the current state was
             * entered prior to the last re-initialization of the local
             * network management subsystem, then this object contains a
             * zero value.
             *
             * NOTE, observium uses last change timestamp.
             */
            $if_lastchange_uptime = timeticks_to_sec($this_port['ifLastChange']);
            if (preg_match(OBS_PATTERN_TIMESTAMP, $this_port['ifLastChange'])) {
                // This ifLastChange copied from previous
                $if_lastchange_uptime = TRUE;
            } elseif (($device_uptime['sysUpTime'] - $if_lastchange_uptime) > 90) {
                $if_lastchange = $device_uptime['polled'] - $device_uptime['sysUpTime'] + $if_lastchange_uptime;
                print_debug('IFLASTCHANGE = ' . $device_uptime['polled'] . 's - ' . $device_uptime['sysUpTime'] . 's + ' . $if_lastchange_uptime . 's');
                if (abs($if_lastchange - strtotime($port['ifLastChange'])) > 90) {
                    // Compare lastchange with previous, update only if more than 60 sec (for exclude random dispersion)
                    $port['update']['ifLastChange'] = date('Y-m-d H:i:s', $if_lastchange); // Convert to timestamp
                }
            } else {
                // Device sysUpTime more than if uptime or too small difference.. impossible, seems as bug on device
                $if_lastchange_uptime = FALSE;
            }
        } else {
            // ifLastChange not exist
            $if_lastchange_uptime = FALSE;
        }

        if ($if_lastchange_uptime === FALSE) {
            if (empty($port['ifLastChange']) || $port['ifLastChange'] === '0000-00-00 00:00:00' || // Newer set (first time)
                isset($port['update']['ifOperStatus']) || isset($port['update']['ifAdminStatus']) ||
                isset($port['update']['ifSpeed']) || isset($port['update']['ifDuplex'])) {
                $port['update']['ifLastChange'] = date('Y-m-d H:i:s', $this_port['polled']);
            }
            print_debug("IFLASTCHANGE unknown/false, used system times.");
        }
        if (isset($port['update']['ifLastChange'])) {
            print_debug("IFLASTCHANGE (" . $port['ifIndex'] . "): " . $port['update']['ifLastChange']);
            if ($port['ifLastChange'] && $port['ifLastChange'] !== '0000-00-00 00:00:00' && safe_count($log_event)) {
                $log_event[] = "[ifLastChange] '" . $port['ifLastChange'] . "' -> '" . $port['update']['ifLastChange'] . "'";
            }
        }
        if ((bool)$log_event) {
            log_event('Interface changed: ' . implode('; ', $log_event), $device, 'port', $port);
        }

        // Parse description (usually ifAlias) if config option set
        if ($custom_port_parser) {
            $log_event = [];
            if ($custom_port_parser !== 'old') {
                $port_ifAlias = custom_port_parser($this_port);
            } else {
                $custom_port_attribs = ['type', 'descr', 'circuit', 'speed', 'notes'];

                include($config['install_dir'] . "/" . $config['port_descr_parser']);
            }

            foreach ($custom_port_attribs as $attrib) {
                $attrib_key = "port_descr_" . $attrib;
                if ($port_ifAlias[$attrib] != $port[$attrib_key]) {
                    if (isset($port_ifAlias[$attrib])) {
                        $port['update'][$attrib_key] = $port_ifAlias[$attrib];
                        $msg                         = "[$attrib] " . $port[$attrib_key] . " -> " . $port_ifAlias[$attrib];
                    } else {
                        $port['update'][$attrib_key] = ['NULL'];
                        $msg                         = "[$attrib] " . $port[$attrib_key] . " -> NULL";
                    }
                    $log_event[] = $msg;
                }
            }
            if ((bool)$log_event) {
                log_event('Interface changed (attrib): ' . implode('; ', $log_event), $device, 'port', $port);
            }
        }
        // End parse ifAlias

        // Update IF-MIB metrics
        foreach ($stat_oids_db as $oid) {
            calculate_port_oid_stats($this_port, $port, $oid, $polled_period);
        }

        if ($config['statsd']['enable']) {
            // Update StatsD/Carbon
            foreach ($stat_oids as $oid) {
                if (!str_contains($oid, "HC") && is_numeric($this_port[$oid])) {
                    StatsD::gauge(str_replace(".", "_", $device['hostname']) . '.' . 'port' . '.' . $port['ifIndex'] . '.' . $oid, $this_port[$oid]);
                }
            }
        }

        // If we have been told to debug this port, output the counters we collected earlier, with the rates stuck on the end.
        debug_port($device, $this_port, $debug_port, $port, $hc_prefix, $polled_period);

        // Put States into alert array
        foreach ([ 'ifOperStatus', 'ifAdminStatus', 'ifMtu', 'ifDuplex', 'ifVlan' ] as $oid) {
            if (isset($this_port[$oid])) {
                $port['alert_array'][$oid] = $this_port[$oid];
            }
        }

        // Store average in/out packets size
        if ($in_pkts_delta = int_add($port['alert_array']['ifInUcastPkts_delta'], $port['alert_array']['ifInNUcastPkts_delta'])) {
            $port['alert_array']['rx_ave_pktsize'] = $port['alert_array']['ifInOctets_delta'] / $in_pkts_delta;
        } else {
            $port['alert_array']['rx_ave_pktsize'] = 0;
        }
        if ($out_pkts_delta = int_add($port['alert_array']['ifOutUcastPkts_delta'], $port['alert_array']['ifOutNUcastPkts_delta'])) {
            $port['alert_array']['tx_ave_pktsize'] = $port['alert_array']['ifOutOctets_delta'] / $out_pkts_delta;
        } else {
            $port['alert_array']['tx_ave_pktsize'] = 0;
        }

        // Store aggregate in/out state
        $port['state']['ifOctets_rate']    = $port['stats']['ifOutOctets_rate']    + $port['stats']['ifInOctets_rate'];
        $port['state']['ifUcastPkts_rate'] = $port['stats']['ifOutUcastPkts_rate'] + $port['stats']['ifInUcastPkts_rate'];
        $port['state']['ifErrors_rate']    = $port['stats']['ifOutErrors_rate']    + $port['stats']['ifInErrors_rate'];
        $port['state']['ifDiscards_rate']  = $port['stats']['ifOutDiscards_rate']  + $port['stats']['ifInDiscards_rate'];

        // Send aggregate data to alerter too
        $port['alert_array']['ifOctets_rate']        = $port['state']['ifOctets_rate'];
        $port['alert_array']['ifUcastPkts_rate']     = $port['state']['ifUcastPkts_rate'];
        $port['alert_array']['ifNUcastPkts_rate']    = $port['stats']['ifOutNUcastPkts_rate']    + $port['stats']['ifInNUcastPkts_rate'];
        $port['alert_array']['ifErrors_rate']        = $port['state']['ifErrors_rate'];
        $port['alert_array']['ifBroadcastPkts_rate'] = $port['stats']['ifOutBroadcastPkts_rate'] + $port['stats']['ifInBroadcastPkts_rate'];
        $port['alert_array']['ifMulticastPkts_rate'] = $port['stats']['ifOutMulticastPkts_rate'] + $port['stats']['ifInMulticastPkts_rate'];
        $port['alert_array']['ifDiscards_rate']      = $port['state']['ifDiscards_rate'];
        print_debug_vars($port['alert_array']);

        // Set per port RRD options
        $rrd_options = [ 'speed' => $this_port['ifSpeed'] ];
        // Grow max for ports with 40G+ ifSpeed
        if (isset($port['update']['ifSpeed']) && $port['update']['ifSpeed'] > 40000000000) {
            //$rrd_options['speed'] += 20000000000; // DEBUG
            $rrd_options['update_max'] = TRUE;
        }

        // Update RRDs
        rrdtool_update_ng($device, 'port', [
          'INOCTETS'         => $this_port['ifInOctets'],
          'OUTOCTETS'        => $this_port['ifOutOctets'],
          'INERRORS'         => $this_port['ifInErrors'],
          'OUTERRORS'        => $this_port['ifOutErrors'],
          'INUCASTPKTS'      => $this_port['ifInUcastPkts'],
          'OUTUCASTPKTS'     => $this_port['ifOutUcastPkts'],
          'INNUCASTPKTS'     => $this_port['ifInNUcastPkts'],
          'OUTNUCASTPKTS'    => $this_port['ifOutNUcastPkts'],
          'INDISCARDS'       => $this_port['ifInDiscards'],
          'OUTDISCARDS'      => $this_port['ifOutDiscards'],
          'INUNKNOWNPROTOS'  => $this_port['ifInUnknownProtos'],
          'INBROADCASTPKTS'  => $this_port['ifInBroadcastPkts'],
          'OUTBROADCASTPKTS' => $this_port['ifOutBroadcastPkts'],
          'INMULTICASTPKTS'  => $this_port['ifInMulticastPkts'],
          'OUTMULTICASTPKTS' => $this_port['ifOutMulticastPkts'],
        ],                get_port_rrdindex($port), TRUE, $rrd_options);

        // End Update IF-MIB

        // Update additional MIBS and modules
        foreach ($process_port_db as $port_module => $oids) {
            $log_event = [];
            foreach ($oids as $oid) {
                if ($port[$oid] != $this_port[$oid]) {
                    if (isset($this_port[$oid])) {
                        // Changed Oid
                        $port['update'][$oid] = $this_port[$oid];
                        $msg                  = "[$oid] '" . $port[$oid] . "' -> '" . $this_port[$oid] . "'";
                    } else {
                        // Removed/empty Oid
                        $port['update'][$oid] = ['NULL'];
                        $msg                  = "[$oid] '" . $port[$oid] . "' -> NULL";
                    }
                    $log_event[] = $msg;
                    if (OBS_DEBUG) {
                        echo($msg . " ");
                    }
                }
            }
            if ((bool)$log_event) {
                log_event('Interface changed (' . $port_module . '): ' . implode('; ', $log_event), $device, 'port', $port);
            }
        }
        // End update additional MIBS

        /* PAgP disabled since r7987, while not moved to new polling style
        // Update PAgP
        if ($this_port['pagpOperationMode'] || $port['pagpOperationMode'])
        {
          $log_event = [];
          foreach ($pagp_oids as $oid)
          { // Loop the OIDs
            if ($this_port[$oid] != $port[$oid])
            { // If data has changed, build a query
              $port['update'][$oid] = $this_port[$oid];
              $log_event[] = "[$oid] " . $port[$oid] . " -> " . $this_port[$oid];
            }
          }
          if ((bool)$log_event) { log_event('Interface changed (pagp): ' . implode('; ', $log_event), $device, 'port', $port); }
        }
        // End Update PAgP
        */

#    if (OBS_DEBUG > 1) { print_vars($port['alert_array']); echo(PHP_EOL); print_vars($this_port);}

        check_entity('port', $port, $port['alert_array']);

        // Send statistics array via AMQP/JSON if AMQP is enabled globally and for the ports module
        if ($config['amqp']['enable'] == TRUE && $config['amqp']['modules']['ports']) {
            $json_data = array_merge($this_port, $port['state']);
            unset($json_data['rrd_update']); // FIXME unset no longer needed when switched to rrdtool_update_ng() !
            messagebus_send(['attribs' => ['t'         => $this_port['polled'], 'device' => $device['hostname'],
                                           'device_id' => $device['device_id'], 'e_type' => 'port',
                                           'e_index'   => $port['ifIndex']], 'data' => $json_data]);
            unset($json_data);
        }

        // Unified state update

        //$port['update'] = array_merge($port['state'], $port['update']);
        //$updated = dbUpdate($port['update'], 'ports', '`port_id` = ?', array($port['port_id']));

        // Add to MultiUpdate ports state as single query
        $ports_db_state[] = array_merge($this_port_indexes, $port['state']);

        // Update Database
        if (!safe_empty($port['update'])) {
            $updated = dbUpdate($port['update'], 'ports', '`port_id` = ?', [$port['port_id']]);
            //print_debug("PORT updated rows=$updated");
        }

        // Add table row

        $table_row    = [];
        $table_row[]  = $port['ifIndex'];
        $table_row[]  = $port['port_label_short'];
        $table_row[]  = rewrite_iftype($port['ifType']);
        $table_row[]  = format_bps($port['ifSpeed']);
        $table_row[]  = format_bps($port['stats']['ifInBits_rate']);
        $table_row[]  = format_bps($port['stats']['ifOutBits_rate']);
        $table_row[]  = format_bytes($port['stats']['ifInOctets_diff']);
        $table_row[]  = format_bytes($port['stats']['ifOutOctets_diff']);
        $table_row[]  = format_si($port['stats']['ifInUcastPkts_rate']);
        $table_row[]  = format_si($port['stats']['ifOutUcastPkts_rate']);
        $table_row[]  = ($port['port_64bit'] ? "%gY%w" : "%rN%w");
        $table_rows[] = $table_row;
        unset($table_row);

        // End Update Database
    } elseif ($port['disabled'] != "1") {
        print_message("Port Deleted."); // Port missing from SNMP cache.
        if (isset($port['ifIndex']) && $port['deleted'] != "1") {
            $ports_db_deleted[] = ['port_id' => $ports[$port['ifIndex']]['port_id'], 'ifIndex' => $port['ifIndex'], 'device_id' => $device['device_id'], // UNIQUE fields
                                   'deleted' => '1', 'ifLastChange' => date('Y-m-d H:i:s', $polled)]; // Update this fields
            //dbUpdate(array('deleted' => '1', 'ifLastChange' => date('Y-m-d H:i:s', $polled)), 'ports',  '`device_id` = ? AND `ifIndex` = ?', array($device['device_id'], $port['ifIndex']));
            log_event("Interface was marked as DELETED", $device, 'port', $port);
        }
    } else {
        print_message("Port Disabled.");
    }

    //echo("\n");

    // Clear Per-Port Variables Here
    unset($this_port);
}

// MultiUpdate deleted ports
if (!safe_empty($ports_db_deleted)) {
    print_debug("MultiUpdate deleted ports DB.");
    // MultiUpdate required all UNIQUE keys!
    dbUpdateMulti($ports_db_deleted, 'ports', ['deleted', 'ifLastChange']);
}

// MultiUpdate ports state
if (!safe_empty($ports_db_state)) {
    print_debug("MultiUpdate ports states DB.");
    // MultiUpdate required all UNIQUE keys!
    dbUpdateMulti($ports_db_state, 'ports');
    // Better to pass keys need to update, but without also normal
    //$columns = array_diff(array_keys($port['state']), array_keys($this_port_indexes));
    //dbUpdateMulti($ports_db_state, 'ports', $columns);
}

$headers = ['%WifIndex%n', '%WLabel%n', '%WType%n', '%WSpeed%n', '%WBPS In%n', '%WBPS Out%n', '%WData In%n', '%WData Out%n', '%WPPS In%n', '%WPPS Out%n', '%WHC%n'];
print_cli_table($table_rows, $headers);

echo(PHP_EOL);

// Clear Variables Here
unset($port_stats, $process_port_functions, $process_port_db, $has_ifEntry, $has_ifXEntry, $has_ifEntry_error_code, $ports_ignored_count, $ports_ignored_count_db);

// EOF
