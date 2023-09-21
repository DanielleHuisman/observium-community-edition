<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2015, Adam Armstrong - http://www.observium.org
 *
 * @package        observium
 * @subpackage     webui
 * @author         Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

register_html_title("Printing");

$navbar          = [];
$navbar['brand'] = "Printer supplies";
$navbar['class'] = "navbar-narrow";
// Convert generic view to supply var
if (!isset($vars['supply']) && isset($vars['view'])) {
    $vars['supply'] = $vars['view'];
    unset($vars['view']);
}

foreach ($printing_tabs as $type) {
    if (!$vars['supply']) {
        $vars['supply'] = $type;
    }

    $navbar['options'][$type]['url']  = generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'printing', 'supply' => $type]);
    $navbar['options'][$type]['text'] = nicecase($type);
    if ($vars['supply'] == $type) {
        $navbar['options'][$type]['class'] = "active";
    }

}

if (dbExist('counters', '`device_id` = ? AND `counter_class` = ?', [$device['device_id'], 'printersupply'])) {
    $navbar['options']['counters']['url']  = generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'printing', 'supply' => 'counters']);
    $navbar['options']['counters']['text'] = 'Printed counters';
    if ($vars['supply'] == 'counters') {
        $navbar['options']['counters']['class'] = "active";
    }
}

print_navbar($navbar);
unset($navbar);

switch ($vars['supply']) {
    case 'counter':
    case 'counters':
        echo generate_box_open();
        echo('<table class="table table-condensed table-striped  table-striped">');

        $graph_title = "Counters";
        $graph_type  = "device_counter";

        include("includes/print-device-graph.php");

        echo('</table>');
        echo generate_box_close();

        print_counter_table(['device_id' => $device['device_id'], 'class' => 'printersupply', 'page' => 'device']);
        break;
    case 'sensor':
    case 'sensors':
        echo generate_box_open();
        echo('<table class="table table-condensed table-striped  table-striped">');

        $graph_title = "Sensors";
        $graph_type  = "device_pagecount";

        include("includes/print-device-graph.php");

        echo('</table>');
        echo generate_box_close();

        print_sensor_table(['device_id' => $device['device_id'], 'metric' => 'counter', 'sensor_descr' => 'print', 'page' => 'device']);
        break;
    default:
        echo generate_box_open();
        echo('<table class="table table-condensed table-striped  table-striped">');

        $graph_title = nicecase($vars['supply']);
        $graph_type  = "device_printersupplies_" . $vars['supply'];

        include("includes/print-device-graph.php");

        echo('</table>');
        echo generate_box_close();

        print_printersupplies_table($vars);
        break;
}

// EOF
