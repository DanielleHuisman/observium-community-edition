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

$sla = dbFetchRow("SELECT * FROM `slas` WHERE `sla_id` = ?", [$vars['id']]);

if (is_numeric($sla['device_id']) && ($auth || device_permitted($sla['device_id']))) {
    $device = device_by_id_cache($sla['device_id']);

    $auth = TRUE;

    $mib_lower = strtolower($sla['sla_mib']);
    $index     = $mib_lower . '-' . $sla['sla_index'];
    $unit_text = 'SLA ' . $sla['sla_index'];
    if ($sla['sla_tag']) {
        $unit_text .= ' - ' . $sla['sla_tag'];
    }
    if ($sla['sla_owner']) {
        $unit_text .= " (Owner: " . $sla['sla_owner'] . ")";
        $index     .= '-' . $sla['sla_owner'];
    }

    $title_array   = [];
    $title_array[] = ['text' => $device['hostname'], 'url' => generate_url(['page' => 'device', 'device' => $device['device_id']])];
    $title_array[] = ['text' => 'SLAs', 'url' => generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'slas'])];
    //echo(print_r($vars()));
    //echo($vars['id']);
    $title_array[] = ['text' => $config['sla_type_labels'][$sla['rtt_type']] . ' - ' . $sla['sla_tag'], 'url' => generate_url(['page' => 'graphs', 'type' => 'sla_sla', 'id' => $vars['id'], 'device' => $device['device_id']])];

    $graph_title   = device_name($device, TRUE);
    $graph_title   .= ' :: ' . $unit_text; // hostname :: SLA XX
}

// EOF
