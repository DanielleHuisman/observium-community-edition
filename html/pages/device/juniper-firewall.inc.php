<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     web
 * @copyright  (C) Adam Armstrong
 *
 */

if (!isset($attribs['juniper-firewall-mib'])) {
    return;
}

echo generate_box_open();

echo '<table class="table table-striped-two table-condensed">';

$json = str_decompress($attribs['juniper-firewall-mib']);
if (!$json) {
    // Compat with old uncompressed
    $json = $attribs['juniper-firewall-mib'];
}
if ($filters = safe_json_decode($json)) {
    ksort($filters);
}
foreach ($filters as $filter => $counters) {

    ksort($counters);

    foreach ($counters as $counter => $types) {

        foreach ($types as $type => $data) {
            echo '<tr><td><h3><i class="sprite-qos"></i> ' . $filter . ' | <i class="sprite-counter"></i>' . $counter . ' | ' . $type . '</h3></td></tr>';

            echo '<tr>';
            echo '<td>';
            echo '<h4>Packets</h4>';

            $graph_array = ['type'         => 'device_juniper-firewall-pkts',
                            'device'       => $device['device_id'],
                            'filter'       => safename($filter),
                            'counter'      => safename($counter),
                            'counter_type' => safename($type)
            ];

            print_graph_row($graph_array);

            echo '<h4>Bytes</h4>';

            $graph_array = ['type'         => 'device_juniper-firewall-bits',
                            'device'       => $device['device_id'],
                            'filter'       => safename($filter),
                            'counter'      => safename($counter),
                            'counter_type' => safename($type)
            ];

            print_graph_row($graph_array);

            echo '</td></tr>';
        }
    }
}

echo '</table>';

unset($json, $filters);

// EOF
