<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$link_array = ['page'   => 'device',
               'device' => $device['device_id'],
               'tab'    => 'latency'];

$navbar['brand'] = "Latency";
$navbar['class'] = "navbar-narrow";

foreach (['incoming', 'outgoing'] as $view) {
    if (!strlen($vars['view'])) {
        $vars['view'] = $view;
    }

    if (safe_count($smokeping_files[$view][$device['hostname']])) {
        if ($vars['view'] == $view) {
            $navbar['options'][$view]['class'] = "active";
        }
        $navbar['options'][$view]['url']  = generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'latency', 'view' => $view]);
        $navbar['options'][$view]['text'] = ucwords($view);
    }
}

print_navbar($navbar);

echo generate_box_open();
echo '<table class="table table-condensed table-striped table-hover ">';

if ($vars['view'] === "incoming" && safe_count($smokeping_files['incoming'][$device['hostname']])) {
    $graph_array['type']   = "device_smokeping_in_all_avg";
    $graph_array['device'] = $device['device_id'];
    echo('<tr><td>');
    echo('<h3>Average</h4>');

    print_graph_row($graph_array);

    echo('</td></tr>');

    $graph_array['type']   = "device_smokeping_in_all";
    $graph_array['legend'] = 'no';
    echo('<tr><td>');
    echo('<h3>Aggregate</h4>');

    print_graph_row($graph_array);

    echo('</td></tr>');

    unset($graph_array['legend']);

    ksort($smokeping_files['incoming'][$device['hostname']]);
    foreach ($smokeping_files['incoming'][$device['hostname']] as $src => $host) {
        $hostname = str_replace(".rrd", "", $host);
        $host     = device_by_name($src);
        if (is_numeric($host['device_id'])) {
            echo('<tr><td>');
            echo('<h3>' . generate_device_link($host) . '</h4>');
            $graph_array['type']   = "smokeping_in";
            $graph_array['device'] = $device['device_id'];
            $graph_array['src']    = $host['device_id'];

            print_graph_row($graph_array);

            echo('</td></tr>');
        }
    }
} elseif ($vars['view'] === "outgoing" && safe_count($smokeping_files['outgoing'][$device['hostname']])) {
    $graph_array['type']   = "device_smokeping_out_all_avg";
    $graph_array['device'] = $device['device_id'];
    echo('<tr><td>');
    echo('<h3>Average</h4>');

    print_graph_row($graph_array);

    echo('</td></tr>');

    $graph_array['type']   = "device_smokeping_out_all";
    $graph_array['legend'] = 'no';
    echo('<tr><td>');
    echo('<h3>Aggregate</h4>');

    print_graph_row($graph_array);

    echo('</td></tr>');

    unset($graph_array['legend']);

    asort($smokeping_files['outgoing'][$device['hostname']]);
    foreach ($smokeping_files['outgoing'][$device['hostname']] as $host) {
        $hostname = basename($host, ".rrd");
        [$hostname] = explode("~", $hostname);
        if ($config['smokeping']['suffix']) {
            $hostname = $hostname . $config['smokeping']['suffix'];
        }
        if ($config['smokeping']['split_char']) {
            $hostname = str_replace($config['smokeping']['split_char'], ".", $hostname);
        }
        $host = device_by_name($hostname);
        if (is_numeric($host['device_id'])) {
            echo('<tr><td>');
            echo('<h3>' . generate_device_link($host) . '</h4>');
            $graph_array['type']   = "smokeping_out";
            $graph_array['device'] = $device['device_id'];
            $graph_array['dest']   = $host['device_id'];

            print_graph_row($graph_array);

            echo('</td></tr>');
        }
    }
}

echo '</table>';

echo generate_box_close();

register_html_title("Latency");

// EOF
