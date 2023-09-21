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

// Check if QoS exists on the host

$cbq_table = [];
foreach (dbFetchRows('SELECT * FROM `ports_cbqos` WHERE `device_id` = ?', [$device['device_id']]) as $cbq) {
    $cbq_table[$cbq['policy_index']][$cbq['object_index']] = $cbq;
}

$oids = ['cbQosCMPrePolicyPkt64', 'cbQosCMPrePolicyByte64', 'cbQosCMPostPolicyByte64',
         'cbQosCMDropPkt64', 'cbQosCMDropByte64', 'cbQosCMNoBufDropPkt64'];

// Walk the first service policies OID and then see if it was populated before we continue

$device_context = $device;
if (safe_empty($cbq_table)) {
    // Set retries to 1 for speedup first walking, only if previously polling also empty (DB empty)
    $device_context['snmp_retries'] = 1;
}
$service_policies = snmpwalk_cache_oid($device_context, "cbQosIfType", [], "CISCO-CLASS-BASED-QOS-MIB");
unset($device_context);

if (!safe_empty($service_policies)) {

    $table_rows = [];

    // Continue populating service policies
    $service_policies = snmpwalk_cache_oid($device, "cbQosPolicyDirection", $service_policies, "CISCO-CLASS-BASED-QOS-MIB");
    $service_policies = snmpwalk_cache_oid($device, "cbQosIfIndex", $service_policies, "CISCO-CLASS-BASED-QOS-MIB");

    $policy_maps    = snmpwalk_cache_oid($device, "cbQosPolicyMapCfgEntry", [], "CISCO-CLASS-BASED-QOS-MIB");
    $class_maps     = snmpwalk_cache_oid($device, "cbQosCMCfgEntry", [], "CISCO-CLASS-BASED-QOS-MIB");
    $object_indexes = snmpwalk_cache_twopart_oid($device, "cbQosConfigIndex", [], "CISCO-CLASS-BASED-QOS-MIB");

    #print_r($policy_maps);
    #print_r($class_maps);
    #print_r($object_indexes);

    $cm_stats = [];
    foreach ($oids as $oid) {
        $cm_stats = snmpwalk_cache_twopart_oid($device, $oid, $cm_stats, "CISCO-CLASS-BASED-QOS-MIB");
    }

    $polled = time();

    foreach ($cm_stats as $policy_index => $policy_entry) {
        foreach ($policy_entry as $object_index => $object_entry) {

            $port                         = get_port_by_ifIndex($device['device_id'], $service_policies[$policy_index]['cbQosIfIndex']);
            $object_entry['port_id']      = $port['port_id'];
            $object_entry['direction']    = $service_policies[$policy_index]['cbQosPolicyDirection'];
            $object_entry['policy_index'] = $policy_index;
            $object_entry['object_index'] = $object_index;
            $object_entry['cm_cfg_index'] = $object_indexes[$policy_index][$object_index]['cbQosConfigIndex'];
            $object_entry['pm_cfg_index'] = $object_indexes[$policy_index][$policy_index]['cbQosConfigIndex'];
            if (!is_numeric($object_entry['pm_cfg_index'])) {
                $object_entry['pm_cfg_index'] = $object_indexes[$policy_index]['1']['cbQosConfigIndex'];
            }
            $object_entry['policy_name'] = $policy_maps[$object_entry['pm_cfg_index']]['cbQosPolicyMapName'];
            $object_entry['policy_desc'] = $policy_maps[$object_entry['pm_cfg_index']]['cbQosPolicyMapDesc'];

            $object_entry['cm_name'] = $class_maps[$object_entry['cm_cfg_index']]['cbQosCMName'];
            $object_entry['cm_desc'] = $class_maps[$object_entry['cm_cfg_index']]['cbQosCMDesc'];
            $object_entry['cm_info'] = $class_maps[$object_entry['cm_cfg_index']]['cbQosCMInfo'];

            //print_r($object_entry);

            // Populate $metrics array using field names used in RRD and MySQL
            $metrics = [
              'PrePolicyPkt'   => $object_entry['cbQosCMPrePolicyPkt64'],
              'PrePolicyByte'  => $object_entry['cbQosCMPrePolicyByte64'],
              'PostPolicyByte' => $object_entry['cbQosCMPostPolicyByte64'],
              'DropPkt'        => $object_entry['cbQosCMDropPkt64'],
              'DropByte'       => $object_entry['cbQosCMDropByte64'],
              'NoBufDropPkt'   => $object_entry['cbQosCMNoBufDropPkt64'],
            ];

            // Clone so we can filter out bad data.
            $metrics_rrd = $metrics;

            // Check if we already have a MySQL entry
            if (isset($cbq_table[$policy_index][$object_index])) {
                $db_object     = $cbq_table[$policy_index][$object_index];
                $polled_period = $polled - $db_object['cbqos_lastpolled'];

                $metrics_computed = [];

                foreach ($metrics as $oid => $value) {
                    $diff = $value - $db_object[$oid];
                    $rate = round(float_div($diff, $polled_period));

                    if ($rate < 0 || !isset($db_object[$oid])) {
                        print_warning("Negative $oid. Possible spike on next poll!");

                        // Send 0 to the database since we know this value is wrong.
                        $rate = "0";

                        // Send U to RRD to try to squash spikes
                        $metrics_rrd[$oid] = 'U';
                    }
                    $metrics_computed[$oid . '_rate'] = $rate;
                }

                $metrics_computed['cbqos_lastpolled'] = $polled;
                $db_update                            = array_merge($metrics_computed, $metrics);

                $db_update['policy_name'] = $object_entry['policy_name'];
                $db_update['object_name'] = $object_entry['cm_name'];

                //dbUpdate($db_update, 'ports_cbqos', '`cbqos_id` = ?', [ $db_object['cbqos_id'] ]);
                $db_update['cbqos_id'] = $db_object['cbqos_id'];
                dbUpdateRowMulti($db_update, 'ports_cbqos', 'cbqos_id');

            } else {
                $db_insert = ['device_id' => $device['device_id'], 'port_id' => $port['port_id'], 'policy_index' => $policy_index, 'object_index' => $object_index, 'direction' => $object_entry['direction']];
                $db_insert = array_merge($db_insert, $metrics);

                $db_insert['policy_name'] = $object_entry['policy_name'];
                $db_insert['object_name'] = $object_entry['cm_name'];

                $cbqos_id                                = dbInsert($db_insert, 'ports_cbqos');
                $cbq_table[$policy_index][$object_index] = dbFetchRow("SELECT * FROM `ports_cbqos` WHERE `cbqos_id` = ?", [$cbqos_id]);
                // $cbq_table[$policy_index][$object_index] = dbFetchRow("SELECT * FROM `ports_cbqos` WHERE `device_id` = ? AND `port_id` = ? AND `policy_index` = ? AND `object_index` = ?",
                //   [ $device['device_id'], $port['port_id'], $policy_index, $object_index ]);
            }

            // Do the RRD thing!
            rrdtool_update_ng($device, 'cisco-cbqos', $metrics, "$policy_index-$object_index");

            // Check alerts
            check_entity('cbqos', $cbq_table[$policy_index][$object_index], $metrics_computed);

            $table_row    = [];
            $table_row[]  = $port['port_label_short'];
            $table_row[]  = $cbq_table[$policy_index][$object_index]['policy_name'];
            $table_row[]  = $cbq_table[$policy_index][$object_index]['object_name'];
            $table_row[]  = $cbq_table[$policy_index][$object_index]['direction'];
            $table_row[]  = format_number($metrics_computed['PrePolicyPkt_rate']);
            $table_row[]  = format_number($metrics_computed['PrePolicyByte_rate']);
            $table_row[]  = format_number($metrics_computed['PostPolicyByte_rate']);
            $table_row[]  = format_number($metrics_computed['DropPkt_rate']);
            $table_row[]  = format_number($metrics_computed['DropByte_rate']);
            $table_row[]  = format_number($metrics_computed['NoBufDropPkt_rate']);
            $table_rows[] = $table_row;
            unset($table_row);
        }
    }

    // Process Multi Update/Insert db
    dbProcessMulti('ports_cbqos');

    $headers = ['%WPort%n', '%WPolicy%n', '%WObject%n', '%WDir%n', '%WPrePkts%n', '%WPreBytes%n', '%WPostByte%n', '%WDropPkt%n', '%WDropByte%n', '%WNoBufDropPkt%n'];
    print_cli_table($table_rows, $headers);

} // End check if QoS is enabled before we walk everything

// EOF
