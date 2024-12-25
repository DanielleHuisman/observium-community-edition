<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) Adam Armstrong
 *
 */

register_html_title("Customers");

$navbar['brand'] = "Customers";
$navbar['class'] = "navbar-narrow";

if (get_var_false($vars['customer'], 'hide')) {
    $navbar['options_right']['cust']['class'] = 'active';
    $navbar['options_right']['cust']['url']   = generate_url($vars, ['customer' => NULL]);
} else {
    $navbar['options_right']['cust']['url'] = generate_url($vars, ['customer' => 'hide']);
}
$navbar['options_right']['cust']['text'] = 'Customer Graphs';
$navbar['options_right']['cust']['icon'] = $config['icon']['graphs'];

if (get_var_true($vars['aggregate'], 'show')) {
    $navbar['options_right']['aggregate']['class'] = 'active';
    $navbar['options_right']['aggregate']['url']   = generate_url($vars, ['aggregate' => NULL]);
} else {
    $navbar['options_right']['aggregate']['url'] = generate_url($vars, ['aggregate' => 'show']);
}
$navbar['options_right']['aggregate']['text'] = 'Aggregate';
$navbar['options_right']['aggregate']['icon'] = $config['icon']['graphs'];


print_navbar($navbar);
unset($navbar);

$where = generate_where_clause("`port_descr_type` = 'cust'", $cache['where']['ports_permitted']);

/// Generate customer aggregate graph
if (get_var_true($vars['aggregate'], 'show')) {

    $port_list = implode(',', dbFetchColumn('SELECT `port_id` FROM `ports` ' . $where));

    echo generate_box_open(['title' => 'Total Customer Traffic']);

    if ($port_list) {
        $graph_array['type']   = 'multi-port_bits_separate';
        $graph_array['to']     = get_time();
        $graph_array['legend'] = 'no';
        $graph_array['id']     = $port_list;

        print_graph_row($graph_array);


    }
    echo generate_box_close();
}

echo generate_box_open();
?>

    <table class="table table-hover table-striped-two table-condensed">
        <thead>
        <tr>
            <th style="width: 250px;"><span style="font-weight: bold;" class="interface">Customer</span></th>
            <th style="width: 150px;">Device</th>
            <th style="width: 100px;">Interface</th>
            <th style="width: 100px;">Speed</th>
            <th style="width: 100px;">Circuit</th>
            <th>Notes</th>
        </tr>
        </thead>

<?php


$customers = [];
foreach (dbFetchRows("SELECT * FROM `ports` $where ORDER BY `port_descr_descr`") as $customer) {
    $customers[$customer['port_descr_descr']][] = $customer;
}

foreach ($customers as $customer => $ports) {

    $customer_name = $customer; // Set text name to use on first port.

    foreach ($ports as $port) {
        $device = device_by_id_cache($port['device_id']);

        unset($class);

        //$ifclass = port_html_class($port['ifOperStatus'], $port['ifAdminStatus'], $port['encrypted']);

        if ($device['os'] == "ios") {
            if ($port['ifTrunk']) {
                $vlan = "<span class=small><span class=red>" . $port['ifTrunk'] . "</span></span>";
            } elseif ($port['ifVlan']) {
                $vlan = "<span class=small><span class=blue>VLAN " . $port['ifVlan'] . "</span></span>";
            } else {
                $vlan = "";
            }
        }

        echo('
           <tr>
             <td style="width: 250px;"><span style="font-weight: bold;" class="interface">' . escape_html($customer_name) . '</span></td>
             <td style="width: 150px;">' . generate_device_link($device) . '</td>
             <td style="width: 100px;">' . generate_port_link_short($port) . '</td>
             <td style="width: 100px;">' . $port['port_descr_speed'] . '</td>
             <td style="width: 100px;">' . $port['port_descr_circuit'] . '</td>
             <td>' . escape_html($port['port_descr_notes']) . '</td>
           </tr>
         ');

        unset($customer_name);
    }

    if ($config['ports']['descr_groups']['cust']['graphs'] && !get_var_false($vars['customer'], 'hide')) {
        echo('<tr><td colspan="7">');

        $graph_array['type'] = "customer_bits";
        $graph_array['to']   = get_time();
        $graph_array['id']   = '"' . escape_html($port['port_descr_descr']) . '"'; // use double quotes for prevent split var by commas

        print_graph_row($graph_array);

        echo("</tr>");
    }
}

echo("</table>");

echo generate_box_close();

// EOF
