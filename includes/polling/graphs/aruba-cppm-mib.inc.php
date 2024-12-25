<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) Adam Armstrong
 *
 */

if ($device['os'] !== 'aruba-cppm') {
    return;
}

// ARUBA-CPPM-MIB::radiusServerTable
//
// ARUBA-CPPM-MIB::radPolicyEvalTime."" = INTEGER: 82
// ARUBA-CPPM-MIB::radAuthRequestTime."" = INTEGER: 453
// ARUBA-CPPM-MIB::radServerCounterSuccess."" = INTEGER: 129034
// ARUBA-CPPM-MIB::radServerCounterFailure."" = INTEGER: 5443
// ARUBA-CPPM-MIB::radServerCounterCount."" = INTEGER: 134477

// Print the processed MIB object
echo "radiusServerTable \n";

// define variables
$radiusServerTable = [];
$data              = [];

// Get data from snmp
print_cli_data("Collecting", "ARUBA-CPPM-MIB::radiusServerTable", 3);
$radiusServerTable = snmpwalk_cache_oid($device, "radiusServerTable", [], "ARUBA-CPPM-MIB");

if (!$radiusServerTable) {
    return;
}

// Enable the clearpass graph section
$graphs['aruba-cppm'] = TRUE;

// FIX for aruba clearpass
// rearrange $data array for rrd writing
// manual index bc no index from the mib/snmp data
$data["1"]["radAuthRequestTime"] = $radiusServerTable[""]["radAuthRequestTime"];
$data["1"]["radPolicyEvalTime"]  = $radiusServerTable[""]["radPolicyEvalTime"];

// Pipe the data to the rrd
foreach ($data as $index => $value) {
    rrdtool_update_ng($device, "aruba-cppm-radiusServerTable", $value, $index);
}

// ( ToDo: Debug Output )
print_debug_vars($data);

// Clear variables after processing the data
unset($data, $radiusServerTable);

// EOF
