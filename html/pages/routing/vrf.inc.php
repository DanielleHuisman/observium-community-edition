<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

if ($_SESSION['userlevel'] < 5) {
    print_error_permission();
    return;
}
if (!$config['enable_vrfs']) {
    print_error("VRFs disabled globally.");
    return;
}

$link_array = ['page'     => 'routing',
               'protocol' => 'vrf'];

$navbar = ['brand' => "VRFs", 'class' => "navbar-narrow"];

$navbar['options']['vrf']['text'] = 'All VRFs';
if (!isset($vars['vrf'])) {
    $navbar['options']['vrf']['class'] .= " active";
}
$navbar['options']['vrf']['url'] = generate_url($vars, ['vrf' => NULL]);

$options['basic']['text'] = 'Basic';
// $navbar['options']['details']['text'] = 'Details';
$options['graphs'] = ['text' => 'Graphs', 'class' => 'pull-right', 'icon' => $config['icon']['graphs']];

if (!isset($vars['view'])) {
    $vars['view'] = 'basic';
}

foreach ($options as $option => $array) {
    $navbar['options'][$option] = $array;
    if ($vars['view'] == $option) {
        $navbar['options'][$option]['class'] .= " active";
    }
    $navbar['options'][$option]['url'] = generate_url($vars, ['view' => $option]);
}

foreach (['graphs'] as $type) {
    foreach ($config['graph_types']['port'] as $option => $data) {
        if ($vars['view'] == $type && $vars['view'] == $option) {
            $navbar['options'][$type]['suboptions'][$option]['class'] = 'active';
            $navbar['options'][$type]['text']                         .= ' (' . $data['name'] . ')';
        }
        $navbar['options'][$type]['suboptions'][$option]['text'] = $data['name'];
        $navbar['options'][$type]['suboptions'][$option]['url']  = generate_url($vars, ['view' => $type, 'graph' => $option]);
    }

}

print_navbar($navbar);
unset($navbar);

if (!$vars['vrf']) {
    // Pre-Cache in arrays
    // That's heavier on RAM, but much faster on CPU (1:40)

    // Specifying the fields reduces a lot the RAM used (1:4) .
    $vrf_fields  = "`vrf_id`, `vrf_rd`, `vrf_descr`, `vrf_name`";
    $dev_fields  = "`device_id`, `hostname`, `os`, `hardware`, `version`, `features`, `location`, `status`, `ignore`, `disabled`";
    $port_fields = "`port_id`, `ifVrf`, `device_id`, `ifDescr`, `ifAlias`, `ifName`";

    foreach (dbFetchRows("SELECT $vrf_fields, $dev_fields FROM `vrfs` LEFT JOIN `devices` USING (`device_id`)" . generate_where_clause($GLOBALS['cache']['where']['devices_permitted'])) as $vrf_device) {
        if (empty($vrf_devices[$vrf_device['vrf_rd']])) {
            $vrf_devices[$vrf_device['vrf_rd']][0] = $vrf_device;
        } else {
            array_push($vrf_devices[$vrf_device['vrf_rd']], $vrf_device);
        }
    }

    foreach (dbFetchRows("SELECT $port_fields FROM `ports`" . generate_where_clause($GLOBALS['cache']['where']['ports_permitted'], '`ifVrf` IS NOT NULL')) as $port) {
        if (empty($vrf_ports[$port['ifVrf']][$port['device_id']])) {
            $vrf_ports[$port['ifVrf']][$port['device_id']][0] = $port;
        } else {
            array_push($vrf_ports[$port['ifVrf']][$port['device_id']], $port);
        }
    }

    echo generate_box_open();

    echo('<table class="table  table-striped">');
    foreach (dbFetchRows("SELECT * FROM `vrfs`" . generate_where_clause($GLOBALS['cache']['where']['devices_permitted']) . " GROUP BY `vrf_rd`") as $vrf) {
        echo('<tr>');
        echo('<td style="width: 240px;"><a class="entity" href="' . generate_url($link_array, ['vrf' => $vrf['vrf_rd']]) . '">' . $vrf['vrf_name'] . '</a><br />
              <span class="small">' . $vrf['vrf_descr'] . '</span></td>');
        echo('<td style="width: 75px;"><span class="label label-primary">' . $vrf['vrf_rd'] . '</span></td>');
        echo('<td>');

        echo generate_box_open();

        echo('<table class="table table-striped  table-condensed">');

        foreach ($vrf_devices[$vrf['vrf_rd']] as $device) {
            echo('<tr><td style="width: 150px;"><span class="entity">' . generate_device_link_short($device) . '</span>');

            if ($device['vrf_name'] != $vrf['vrf_name']) {
                echo(generate_tooltip_link(NULL, '&nbsp;' . get_icon('exclamation'), "Expected Name : " . $vrf['vrf_name'] . "<br />Configured : " . $device['vrf_name']));
            }
            echo("</td><td>");
            unset($seperator);

            foreach ($vrf_ports[$device['vrf_id']][$device['device_id']] as $port) {
                $port = array_merge($device, $port);

                switch ($vars['graph']) {
                    case 'bits':
                    case 'upkts':
                    case 'nupkts':
                    case 'errors':
                        $port['width']      = "130";
                        $port['height']     = "30";
                        $port['from']       = get_time('day');
                        $port['to']         = get_time();
                        $port['bg']         = "#" . $bg;
                        $port['graph_type'] = "port_" . $vars['graph'];
                        echo '<div class="box box-solid" style="display: block; padding: 3px; margin: 3px; min-width: 135px; max-width:135px; min-height:75px; max-height:75px;
                 text-align: center; float: left;"">
                 <div style="font-weight: bold;">' . short_ifname($port['ifDescr']) . '</div>';
                        generate_port_thumbnail($port);
                        echo("<div style='font-size: 9px;'>" . short_port_descr($port['ifAlias']) . "</div>
                </div>");
                        break;

                    default:
                        echo($seperator . generate_port_link_short($port));
                        $seperator = ", ";
                        break;
                }
            }
            echo("</td></tr>");
        } // End While

        echo '</table>';
        echo generate_box_close();
        echo '</td>';
    }
    echo("</table>");

    echo generate_box_close();

} else {

    // Print single VRF

    echo generate_box_open();
    echo('<table class="table  table-striped">');
    $vrf = dbFetchRow("SELECT * FROM `vrfs` " . generate_where_clause('`vrf_rd` = ?', $GLOBALS['cache']['where']['devices_permitted']), [$vars['vrf']]);
    echo('<tr>');
    echo('<td style="width: 200px;" class="entity-title"><a href="' . generate_url($link_array, ['vrf' => $vrf['vrf_rd']]) . '">' . $vrf['vrf_name'] . '</a></td>');
    echo('<td style="width: 100px;" class="small">' . $vrf['vrf_rd'] . '</td>');
    echo('<td style="width: 200px;" class="small">' . $vrf['vrf_descr'] . '</td>');
    echo('</table>');
    echo generate_box_close();

    $vrf_devices = dbFetchRows("SELECT * FROM `vrfs` LEFT JOIN `devices` USING (`device_id`)" . generate_where_clause('`vrf_rd` = ?', $GLOBALS['cache']['where']['devices_permitted']), [$vrf['vrf_rd']]);
    foreach ($vrf_devices as $device) {
        $hostname = $device['hostname'];
        echo generate_box_open();
        echo('<table cellpadding="10" cellspacing="0" class="table  table-striped" width="100%">');

        print_device_row($device);

        echo('</table>');
        echo generate_box_close();
        unset($seperator);
        echo('<div style="margin: 10px;"><table class="table box box-solid table-striped">');

        foreach (dbFetchRows("SELECT * FROM `ports`" . generate_where_clause('`ifVrf` = ? AND `device_id` = ?', $GLOBALS['cache']['where']['ports_permitted']), [$device['vrf_id'], $device['device_id']]) as $port) {
            print_port_row($port);
        }

        echo('</table></div>');
        echo('<div style="height: 10px;"></div>');
    }
}

// EOF
