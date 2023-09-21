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

if (is_intnum($vars['id']) && $alert = get_alert_entry_by_id($vars['id'])) {

  $device = device_by_id_cache($alert['device_id']);

  if (device_permitted($device['device_id']) || $auth) {
    $entity = get_entity_by_id_cache($alert['entity_type'], $alert['entity_id']);

    $rrd_filename = get_rrd_path($device, "alert-" . $alert['alert_test_id'] . "-" . $alert['entity_type'] . "-" . $alert['entity_id'] . ".rrd");
    $auth = TRUE;

    $title_array   = [];
    $title_array[] = [ 'text' => $device['hostname'],
                       'url' => generate_url(['page' => 'device', 'device' => $device['device_id']]) ];

    $graph_title   = device_name($device, TRUE);
  }
}

// EOF
