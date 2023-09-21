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

// JUNIPER-COS-MIB Queue Stats

$port_module = 'jnx_cos_qstat';

if ($ports_modules[$port_module] && $port_stats_count) {

    echo("JUNIPER-COS-MIB Queue Stats ");
    $qstat_oids = ['jnxCosQstatQedPkts', 'jnxCosQstatQedBytes', 'jnxCosQstatTxedPkts', 'jnxCosQstatTxedBytes', 'jnxCosQstatTailDropPkts', 'jnxCosQstatTotalRedDropPkts', 'jnxCosQstatTotalRedDropBytes'];

    $q_names   = snmpwalk_cache_oid($device, 'jnxCosFcIdToFcName', [], "JUNIPER-COS-MIB");
    $q_numbers = snmpwalk_cache_oid($device, 'jnxCosFcQueueNr', [], "JUNIPER-COS-MIB");

    //$qstats = snmpwalk_cache_oid($device, array_shift($qstat_oids), array(), "JUNIPER-COS-MIB");

    $process_port_functions[$port_module] = $GLOBALS['snmp_status'];

    if ($GLOBALS['snmp_status']) {

        $q_names = snmpwalk_cache_oid($device, 'jnxCosFcFabricPriority', $q_names, "JUNIPER-COS-MIB");

        $queues = [];
        foreach ($q_names as $index => $data) {
            $queues[$index]['queue'] = $q_numbers[$data['jnxCosFcIdToFcName']]['jnxCosFcQueueNr'];
            if (isset($data['jnxCosFcIdToFcName'])) {
                $queues[$index]['name'] = $data['jnxCosFcIdToFcName'];
            }
            if (isset($data['jnxCosFcFabricPriority'])) {
                $queues[$index]['prio'] = $data['jnxCosFcFabricPriority'];
            }
        }
        set_entity_attrib('device', $device['device_id'], 'jnx_cos_queues', json_encode($queues));


        foreach ($qstat_oids as $oid) {
            $qstats = snmpwalk_cache_oid($device, $oid, $qstats, "JUNIPER-COS-MIB");
        }

        foreach ($qstats as $qstat_index => $qstat) {
            [$qstat_ifindex, $qstat_queue] = explode('.', $qstat_index);

            $port_stats[$qstat_ifindex]['jnx_cos_qstat'][$qstat_queue] = $qstat;

        }
        unset($qstats);
        unset($queues);

    }


    //print_vars($port_stats);

}

// Additional db fields for update
//$process_port_db[$port_module][] = 'ifDuplex'; // this field used in main data fields

// EOF

