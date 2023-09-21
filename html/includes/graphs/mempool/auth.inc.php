<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

if (!is_intnum($vars['id'])) {
    return;
}

$mempool = dbFetchRow("SELECT * FROM `mempools` WHERE `mempool_id` = ?", [$vars['id']]);

if (is_numeric($mempool['device_id']) && ($auth || device_permitted($mempool['device_id']))) {
    $device = device_by_id_cache($mempool['device_id']);
    if (isset($mempool['mempool_table'])) {
        $rrd_filename = get_rrd_path($device, "mempool-" . strtolower($mempool['mempool_mib']) . "-" . $mempool['mempool_table'] . "-" . $mempool['mempool_index'] . ".rrd");
    } else {
        $rrd_filename = get_rrd_path($device, "mempool-" . strtolower($mempool['mempool_mib']) . "-" . $mempool['mempool_index'] . ".rrd");
    }

    $auth  = TRUE;

    $graph_title   = device_name($device, TRUE);
    $graph_title   .= " :: Memory Pool :: " . rewrite_entity_name($mempool['mempool_descr'], 'mempool');
}

// EOF
