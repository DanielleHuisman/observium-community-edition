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

// Check if QoS exists on the host

$query = 'SELECT * FROM `ports_cbqos`';
$query .= ' WHERE `device_id` = ?';

$cbq_db = dbFetchRows($query, [$device['device_id']]);
foreach ($cbq_db as $cbq) {
    $cbq_table[$cbq['policy_index']][$cbq['object_index']] = $cbq;
}

// Walk the first service policies OID and then see if it was populated before we continue

$service_policies = []; // This ends up being indexed by cbQosPolicyIndex
$service_policies = snmpwalk_cache_oid($device, "cbQosIfType", $service_policies, 'CISCO-CLASS-BASED-QOS-MIB');

if (count($service_policies)) {

    $table_rows = [];

    // Continue populating service policies
    $service_policies = snmpwalk_cache_oid($device, "cbQosPolicyDirection", $service_policies, 'CISCO-CLASS-BASED-QOS-MIB');
    $service_policies = snmpwalk_cache_oid($device, "cbQosIfIndex", $service_policies, 'CISCO-CLASS-BASED-QOS-MIB');

    $policy_maps    = snmpwalk_cache_oid($device, "cbQosPolicyMapCfgEntry", [], 'CISCO-CLASS-BASED-QOS-MIB');
    $class_maps     = snmpwalk_cache_oid($device, "cbQosCMCfgEntry", [], 'CISCO-CLASS-BASED-QOS-MIB');
    $object_indexes = snmpwalk_cache_twopart_oid($device, "cbQosObjectsEntry", [], 'CISCO-CLASS-BASED-QOS-MIB');

#  print_r($policy_maps);
#  print_r($class_maps);
#  print_r($object_indexes);

    $cm_stats = [];
    // $oids = array('cbQosCMPrePolicyPkt64','cbQosCMPrePolicyByte64', 'cbQosCMPostPolicyByte64', 'cbQosCMDropPkt64', 'cbQosCMDropByte64', 'cbQosCMNoBufDropPkt64');
    $oids = ['cbQosCMPrePolicyPkt64'];
    foreach ($oids as $oid) {
        $cm_stats = snmpwalk_cache_twopart_oid($device, $oid, $cm_stats, 'CISCO-CLASS-BASED-QOS-MIB');
    }

    foreach ($cm_stats as $policy_index => $policy_entry) {
        foreach ($policy_entry as $object_index => $object_entry) {
            $port = get_port_by_ifIndex($device['device_id'], $service_policies[$policy_index]['cbQosIfIndex']);

            $object_entry['port_id']      = $port['port_id'];
            $object_entry['direction']    = $service_policies[$policy_index]['cbQosPolicyDirection'];
            $object_entry['policy_index'] = $policy_index;
            $object_entry['object_index'] = $object_index;
            $object_entry['cm_cfg_index'] = $object_indexes[$policy_index][$object_index]['cbQosConfigIndex'];
            $object_entry['pm_cfg_index'] = $object_indexes[$policy_index][$policy_index]['cbQosConfigIndex'];

            // Loop the entries for this policy and get the policy configuration id. This is messy. This MIB sucks. Also sometimes the indexing format changes (!)
            foreach ($object_indexes[$policy_index] as $object_data) {
                #       print_vars($object_data);
                if ($object_data['cbQosObjectsType'] == 'policymap') {
                    $object_entry['pm_cfg_index'] = $object_data['cbQosConfigIndex'];
                }
            }

            $object_entry['policy_name'] = $policy_maps[$object_entry['pm_cfg_index']]['cbQosPolicyMapName'];
            $object_entry['policy_desc'] = $policy_maps[$object_entry['pm_cfg_index']]['cbQosPolicyMapDesc'];

            $object_entry['cm_name'] = $class_maps[$object_entry['cm_cfg_index']]['cbQosCMName'];
            $object_entry['cm_desc'] = $class_maps[$object_entry['cm_cfg_index']]['cbQosCMDesc'];
            $object_entry['cm_info'] = $class_maps[$object_entry['cm_cfg_index']]['cbQosCMInfo'];

            if ($object_entry['policy_index'] == '1995099406') {
                print_vars($object_entry);
            }

            if (!isset($cbq_table[$policy_index][$object_index])) {
                dbInsert(['device_id' => $device['device_id'], 'port_id' => $port['port_id'], 'policy_index' => $policy_index, 'object_index' => $object_index, 'direction' => $object_entry['direction'], 'object_name' => $object_entry['cm_name'], 'policy_name' => $object_entry['policy_name']], 'ports_cbqos');
                //echo("+");
                $cbq_table[$policy_index][$object_index] = dbFetchRow("SELECT * FROM `ports_cbqos` WHERE `device_id` = ? AND `port_id` = ? AND `policy_index` = ? AND `object_index` = ?",
                                                                      [$device['device_id'], $port['port_id'], $policy_index, $object_index]);
            } else {
                if ($cbq_table[$policy_index][$object_index]['policy_name'] != $object_entry['policy_name'] || $cbq_table[$policy_index][$object_index]['object_name'] != $object_entry['cm_name']) {
                    dbUpdate(['object_name' => $object_entry['cm_name'], 'policy_name' => $object_entry['policy_name']], 'ports_cbqos', '`device_id` = ? AND `port_id` = ? AND `policy_index` = ? AND `object_index` = ?', [$device['device_id'], $port['port_id'], $policy_index, $object_index]);
                    //echo("U");
                }

                unset($cbq_table[$policy_index][$object_index]);

            }

            $table_row    = [];
            $table_row[]  = $port['port_label_short'];
            $table_row[]  = $object_entry['policy_name'];
            $table_row[]  = $object_entry['cm_name'];
            $table_row[]  = $object_entry['direction'];
            $table_rows[] = $table_row;
            unset($table_row);

        }
    }

    $headers = ['%WPort%n', '%WPolicy%n', '%WObject%n', '%WDir%n'];
    print_cli_table($table_rows, $headers);

} // End check if QoS is enabled before we walk everything
else {
    echo 'QoS not configured.', PHP_EOL;
}

foreach ($cbq_table as $policy => $objects) {
    foreach ($objects as $object_name => $object) {
        dbDelete('ports_cbqos', '`cbqos_id` = ?', [$object['cbqos_id']]);
        echo '-';
    }
}


// EOF