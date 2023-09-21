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

// TIMETRA-PORT-MIB Engress Queue Stats

$port_module = 'sros_egress_qstat';

//print_r($ports_modules);

if ($ports_modules[$port_module] && $port_stats_count) {

    echo("TIMETRA-PORT-MIB Egress Queue Stats ");
    $qstat_oids = ['tmnxPortNetEgressFwdInProfPkts',
                   'tmnxPortNetEgressFwdOutProfPkts',
                   'tmnxPortNetEgressFwdInProfOcts',
                   'tmnxPortNetEgressFwdOutProfOcts',
                   'tmnxPortNetEgressDroInProfPkts',
                   'tmnxPortNetEgressDroOutProfPkts',
                   'tmnxPortNetEgressDroInProfOcts',
                   'tmnxPortNetEgressDroOutProfOcts'];

    $qstats = snmpwalk_cache_oid($device, array_shift($qstat_oids), [], "TIMETRA-PORT-MIB");

    $process_port_functions[$port_module] = $GLOBALS['snmp_status'];

    if ($GLOBALS['snmp_status']) {

        foreach ($qstat_oids as $oid) {
            $qstats = snmpwalk_cache_oid($device, $oid, $qstats, "TIMETRA-PORT-MIB");
        }

        foreach ($qstats as $qstat_index => $qstat) {
            [$qstat_chassis, $qstat_ifindex, $qstat_queue] = explode('.', $qstat_index);
            $port_stats[$qstat_ifindex]['sros_egress_qstat'][$qstat_queue] = $qstat;
        }

        unset($qstats);
    }

    //print_vars($port_stats);

}


// TIMETRA-PORT-MIB Ingress Queue Stats

$port_module = 'sros_ingress_qstat';

//print_r($ports_modules);

if ($ports_modules[$port_module] && $port_stats_count) {

    echo("TIMETRA-PORT-MIB Ingress Queue Stats ");
    $qstat_oids = ['tmnxPortNetIngressFwdInProfPkts',
                   'tmnxPortNetIngressFwdOutProfPkts',
                   'tmnxPortNetIngressFwdInProfOcts',
                   'tmnxPortNetIngressFwdOutProfOcts',
                   'tmnxPortNetIngressDroInProfPkts',
                   'tmnxPortNetIngressDroOutProfPkts',
                   'tmnxPortNetIngressDroInProfOcts',
                   'tmnxPortNetIngressDroOutProfOcts'];

    $qstats = snmpwalk_cache_oid($device, array_shift($qstat_oids), [], "TIMETRA-PORT-MIB");

    $process_port_functions[$port_module] = $GLOBALS['snmp_status'];

    if ($GLOBALS['snmp_status']) {

        foreach ($qstat_oids as $oid) {
            $qstats = snmpwalk_cache_oid($device, $oid, $qstats, "TIMETRA-PORT-MIB");
        }

        foreach ($qstats as $qstat_index => $qstat) {
            [$qstat_chassis, $qstat_ifindex, $qstat_queue] = explode('.', $qstat_index);
            $port_stats[$qstat_ifindex]['sros_ingress_qstat'][$qstat_queue] = $qstat;
        }

        unset($qstats);
    }

    //print_vars($port_stats);

}



// Additional db fields for update
//$process_port_db[$port_module][] = 'ifDuplex'; // this field used in main data fields

// EOF

