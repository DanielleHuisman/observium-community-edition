<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package                           observium
 * @subpackage                        poller
 *
 * @author                            Jens Brueckner <Discord: JTC#3678>
 * @copyright                         'aruba-cppm-mib.inc.php'    (C) 2022 Jens Brueckner
 * @copyright                         'Observium'                (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// ARUBA-CPPM-MIB::radiusServerTable
//
// ARUBA-CPPM-MIB::radPolicyEvalTime."" = INTEGER: 82
// ARUBA-CPPM-MIB::radAuthRequestTime."" = INTEGER: 453
// ARUBA-CPPM-MIB::radServerCounterSuccess."" = INTEGER: 129034
// ARUBA-CPPM-MIB::radServerCounterFailure."" = INTEGER: 5443
// ARUBA-CPPM-MIB::radServerCounterCount."" = INTEGER: 134477

// check conditions
if ($device['type'] === 'server' && $device['os'] === 'aruba-cppm') {
    // only if needed
    //global $config;

    // Print the processed MIB object
    echo "radiusServerTable \n";

    // Enable the clearpass graph section
    $graphs['aruba-cppm'] = TRUE;

    // define variables
    $radiusServerTable = [];
    $data              = [];

    $rrdtype = "aruba-cppm-radiusServerTable";
    $oids    = "radiusServerTable";
    $mib     = "ARUBA-CPPM-MIB";

    // Get data from snmp
    print_cli_data("Collecting", "ARUBA-CPPM-MIB::radiusServerTable", 3);
    $radiusServerTable = snmpwalk_cache_oid($device, $oids, [], $mib, NULL);

    // FIX for aruba clearpass
    // rearrange $data array for rrd writing
    // manual index bc no index from the mib/snmp data
    $data["1"]["radAuthRequestTime"] = $radiusServerTable[""]["radAuthRequestTime"];
    $data["1"]["radPolicyEvalTime"]  = $radiusServerTable[""]["radPolicyEvalTime"];

    // Pipe the data to the rrd
    foreach ($data as $index => $value) {
        rrdtool_update_ng($device, $rrdtype, $value, $index);
    }

    // ( ToDo: Debug Output )
    if (OBS_DEBUG > 1) {
        print_vars($data);
    }

    // Clear variables after processing the data
    unset($data);
    unset($radiusServerTable);
    unset($rrdtype);
    unset($oid);
    unset($mib);
}
// EOF
