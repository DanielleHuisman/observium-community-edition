<?php

/**
 * Observium Network Management and Monitoring System
 *
 * @package    observium
 * @subpackage web
 * @author     Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2020 Observium Limited
 *
 */

register_html_title("Customers");

$navbar['brand'] = "Customers";
$navbar['class'] = "navbar-narrow";

if (isset($vars['customer']) && $vars['customer'] == 'hide') {
    $navbar['options_right']['cust']['class'] = 'active';
    $navbar['options_right']['cust']['url'] = generate_url($vars, array('customer' => NULL));
} else {
    $navbar['options_right']['cust']['url'] = generate_url($vars, array('customer' => 'hide'));
}
$navbar['options_right']['cust']['text'] = 'Customer Graphs';
$navbar['options_right']['cust']['icon'] = $config['icon']['graphs'];

if (isset($vars['aggregate']) && $vars['aggregate'] == 'show') {
    $navbar['options_right']['aggregate']['class'] = 'active';
    $navbar['options_right']['aggregate']['url'] = generate_url($vars, array('aggregate' => NULL));
} else {
    $navbar['options_right']['aggregate']['url'] = generate_url($vars, array('aggregate' => 'show'));
}
$navbar['options_right']['aggregate']['text'] = 'Aggregate';
$navbar['options_right']['aggregate']['icon'] = $config['icon']['graphs'];


print_navbar($navbar);
unset($navbar);

/// Generate customer aggregate graph

if (isset($vars['aggregate']) && $vars['aggregate'] == 'show') {

    $where = "WHERE `port_descr_type` = 'cust'";
    $where .= generate_query_permitted(array('port'));

    $ports = dbFetchRows('SELECT `port_id` FROM `ports` ' . $where);

    $port_list = array();
    foreach ($ports as $port) {
        $port_list[] = $port['port_id'];
    }
    $port_list = implode(',', $port_list);

    echo generate_box_open(array('title' => 'Total Customer Traffic'));

    if ($port_list) {
        $graph_array['type'] = 'multi-port_bits_separate';
        $graph_array['to'] = $config['time']['now'];
        $graph_array['legend'] = 'no';
        $graph_array['id'] = $port_list;

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
foreach (dbFetchRows("SELECT * FROM `ports` WHERE `port_descr_type` = 'cust' GROUP BY `port_descr_descr` ORDER BY `port_descr_descr`") as $customer) {
    $customer_name = $customer['port_descr_descr'];

    foreach (dbFetchRows("SELECT * FROM `ports` WHERE `port_descr_type` = 'cust' AND `port_descr_descr` = ?", array($customer['port_descr_descr'])) as $port) {
        $device = device_by_id_cache($port['device_id']);

        unset($class);

        $ifclass = port_html_class($port['ifOperStatus'], $port['ifAdminStatus'], $port['encrypted']);

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
             <td style="width: 250px;"><span style="font-weight: bold;" class="interface">' . $customer_name . '</span></td>
             <td style="width: 150px;">' . generate_device_link($device) . '</td>
             <td style="width: 100px;">' . generate_port_link_short($port) . '</td>
             <td style="width: 100px;">' . $port['port_descr_speed'] . '</td>
             <td style="width: 100px;">' . $port['port_descr_circuit'] . '</td>
             <td>' . $port['port_descr_notes'] . '</td>
           </tr>
         ');

        unset($customer_name);
    }

    if ($config['int_customers_graphs'] && !(isset($vars['customer']) && $vars['customer'] == "hide")) {
        echo('<tr><td colspan="7">');

        $graph_array['type'] = "customer_bits";
        $graph_array['to'] = $config['time']['now'];
        $graph_array['id'] = '"' . $customer['port_descr_descr'] . '"'; // use double quotes for prevent split var by commas

        print_graph_row($graph_array);

        echo("</tr>");
    }
}

echo("</table>");

echo generate_box_close();

// EOF
