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

if ($mempool['mempool_index'] == "swap") {

    // this is for swap

    $data = snmp_get_multi_oid($device, 'memTotalSwap.0 memAvailSwap.0', [], 'UCD-SNMP-MIB');
    $data = $data[0];

    $mempool['total'] = $data['memTotalSwap'] * 1024;
    $mempool['free']  = $data['memAvailSwap'] * 1024;
    $mempool['used']  = $mempool['total'] - $mempool['free'];
    $mempool['perc']  = percent($mempool['free'], $mempool['total'], FALSE);

} elseif ($mempool['mempool_hc']) {
    $data = snmp_get_multi_oid($device, 'memTotalReal.0 memAvailReal.0', [], 'UCD-SNMP-MIB');
    $data = $data[0];

    $mempool['total'] = $data['memTotalReal'] * 1024;
    $mempool['free']  = $data['memAvailReal'] * 1024;
    $mempool['used']  = $mempool['total'] - $mempool['free'];
    $mempool['perc']  = percent($mempool['free'], $mempool['total'], FALSE);
} else {

    // Not doing swap, so do memory!

    //$data = snmpwalk_cache_oid($device, "mem", array(), "UCD-SNMP-MIB");
    $data = snmp_get_multi_oid($device, 'memTotalReal.0 memAvailReal.0 memBuffer.0 memCached.0', [], 'UCD-SNMP-MIB');
    $data = $data[0];

    $mempool['total'] = $data['memTotalReal'] * 1024;
    //$mempool['free']  = $data['memAvailReal'] * 1024;
    $mempool['free'] = ($data['memAvailReal'] + ($data['memBuffer'] + $data['memCached'])) * 1024;
    $mempool['used'] = $mempool['total'] - $mempool['free'];
    $mempool['perc'] = percent($mempool['free'], $mempool['total'], FALSE);
}

// EOF