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

$entity = get_entity_by_id_cache($type, $vars['id']);

if (is_numeric($entity['device_id']) && ($auth || device_permitted($entity['device_id']))) {
    $device = device_by_id_cache($entity['device_id']);

    $auth   = TRUE;

    $index = strtolower($entity['mib']) . '-' . $entity['pwIndex'];
    if ($subtype === 'uptime') {
        $index = strtolower($entity['mib']) . '-uptime-' . $entity['pwIndex'];
    }
    $rrd_filename = get_rrd_path($device, "pseudowire-" . $index . ".rrd");

    $unit_text = 'ID ' . $entity['pwID'];
    if ($entity['pwDescr']) {
        $unit_text .= ' - ' . $entity['pwDescr'];
    }

    $graph_title   = device_name($device, TRUE);
    $graph_title   .= ' :: Pseudowire :: ' . $unit_text;

}

// EOF
