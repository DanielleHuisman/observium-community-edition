<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage ajax
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

$rows_updated = 0;
$update_ports = [];
//r($vars);
//$ports_attribs = get_device_entities_attribs($device_id, 'port'); // Get all attribs

foreach($vars['port'] as $port_id => $port_data)
{

    if (is_entity_write_permitted('port', $port_id)) {
        $port = get_port_by_id_cache($port_id);
        $device = device_by_id_cache($port['device_id']);

        $updated = FALSE;
        $update_array = array();

        $port_attribs = get_entity_attribs('port', $port['port_id']);

        if (is_array($port_attribs)) {
            $port = array_merge($port, $port_attribs);
        }

        // Check ignored and disabled port
        foreach (array('ignore', 'disabled') as $param) {
            $old_param = $port[$param] ? 1 : 0;
            $new_param = (isset($port_data[$param]) && $port_data[$param]) ? 1 : 0;
            if ($old_param != $new_param) {
                $update_array[$param] = $new_param;
            }
        }

        if (count($update_array)) {
            dbUpdate($update_array, 'ports', '`port_id` = ?', array($port_id));
            $updated = TRUE;
        }

        // Check custom ifSpeed

        $old_ifSpeed_bool = isset($port['ifSpeed_custom']);
        $new_ifSpeed_bool = isset($port_data['ifSpeed_custom_bool']) && $port_data['ifSpeed_custom_bool'];
        if ($new_ifSpeed_bool) {
            $port_data['ifSpeed_custom'] = (int) unit_string_to_numeric($port_data['ifSpeed_custom'], 1000);
            if ($port_data['ifSpeed_custom'] <= 0) {
                // Wrong ifSpeed, skip
                //print_warning("Passed incorrect value for port speed: ".unit_string_to_numeric($port_data['ifSpeed_custom'], 1000));
                $old_ifSpeed_bool = $new_ifSpeed_bool = FALSE; // Skip change
            }
            //$updated = TRUE;
        }

        if ($old_ifSpeed_bool && $new_ifSpeed_bool) {
            // Both set, compare values
            if ($port_data['ifSpeed_custom'] != $port['ifSpeed_custom']) {
                //r($vars['ifSpeed_custom_' . $port_id]); r($port['ifSpeed_custom']);
                set_entity_attrib('port', $port_id, 'ifSpeed_custom', $port_data['ifSpeed_custom'], $device['device_id']);
                $update_array['ifSpeed_custom'] = $port_data['ifSpeed_custom'];
                $updated = TRUE;
            }
        } elseif ($old_ifSpeed_bool !== $new_ifSpeed_bool) {
            // Added or removed
            if ($old_ifSpeed_bool) {
                del_entity_attrib('port', $port_id, 'ifSpeed_custom');
                $update_array['ifSpeed_custom_bool'] = 0;
            } else {
                set_entity_attrib('port', $port_id, 'ifSpeed_custom', $port_data['ifSpeed_custom'], $device['device_id']);
                $update_array['ifSpeed_custom_bool'] = 1;
            }
            $updated = TRUE;
        }

        // Count updates
        if ($updated) {
            $update_ports[$port_id] = $update_array;
            $rows_updated++;
        }
    }
}
// Query updated sensors array
if ($rows_updated) {
  print_json_status('ok', $rows_updated.' port(s) updated.', [ 'update_array' => $update_ports ]);
} else {
  print_json_status('failed', 'No update performed.');
}

unset($ports_attribs);

// EOF
