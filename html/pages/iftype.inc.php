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

if (!is_array($vars['type'])) {
    $vars['type'] = [$vars['type']];
}

$where = 'WHERE 1';
$where .= generate_query_values_and($vars['type'], 'port_descr_type', 'LIKE');
$where .= generate_query_permitted(['port']);
//$where .= $cache['where']['ports_permitted'];

//$ports = dbFetchRows('SELECT * FROM `ports` AS I, `devices` AS D '.$where.' AND I.`device_id` = D.`device_id` ORDER BY I.`ifAlias`');
$ports = dbFetchRows('SELECT * FROM `ports` ' . $where . ' ORDER BY `ifAlias`');

$port_list = [];
foreach ($ports as $port) {
    $port_list[] = $port['port_id'];
}
$port_count    = count($port_list);
$port_compress = $port_count >= 128; // compress list of ports (not sure about ports count @mike)
$port_list     = implode(',', $port_list);

$type_list = [];
foreach ($vars['type'] as $type) {
    $type_list[] = nicecase($type);
}

$types = implode(' & ', $type_list);

register_html_title("$types Ports");

echo generate_box_open(['title' => 'Total ' . $types . ' Traffic']);

//echo '<h3>Total '.$types.' Traffic</h3>';

if ($port_count) {
    $graph_array['type'] = 'multi-port_bits_separate';
    $graph_array['to']   = get_time();
    $graph_array['id']   = $port_list;
    if ($port_count > $GLOBALS['config']['web_porttype_legend_limit']) {
        $graph_array['legend'] = 'no';
    }
    if ($port_compress) {
        $graph_array['compressed'] = 1;
        $graph_array['id'] = str_compress($graph_array['id']);
    }

    print_graph_row($graph_array);

    echo generate_box_close();

    echo generate_box_open();

    ?>

    <table class="table table-hover table-striped-two table-condensed">
        <thead>
        <tr>
            <th style="width: 250px;"><span style="font-weight: bold;" class="interface">Description</span></th>
            <th style="width: 150px;">Device</th>
            <th style="width: 100px;">Interface</th>
            <th style="width: 100px;">Speed</th>
            <th style="width: 100px;">Circuit</th>
            <th>Notes</th>
        </tr>
        </thead>

    <?php

    foreach ($ports as $port) {
        $done = 'yes';
        unset($class);
        $port['ifAlias'] = str_ireplace($type . ': ', '', $port['ifAlias']);
        $port['ifAlias'] = str_ireplace('[PNI]', 'Private', $port['ifAlias']);
        $ifclass         = port_html_class($port['ifOperStatus'], $port['ifAdminStatus'], $port['encrypted']);
        echo('<tr>
             <td><span class="entity-title">' . generate_port_link($port, $port['port_descr_descr']) . '</span>');
#            <span class=small style='float: left;'>'.generate_device_link($port).' '.generate_port_link($port).' </span>');

        if (dbExist('mac_accounting', 'port_id = ?', [$port['port_id']])) {
            echo("<span style='float: right;'><a href='device/device=" . $port['device_id'] . "/tab=port/port=" . $port['port_id'] . "/view=macaccounting/'>".get_icon('port')." MAC Accounting</a></span>");
        }

        echo('</td>');

        echo('   <td style="width: 150px;" class="strong">' . generate_device_link($port['device_id']) . '</td>
             <td style="width: 150px;" class="strong">' . generate_port_link_short($port) . '</td>
             <td style="width: 75px;">' . $port['port_descr_speed'] . '</td>
             <td style="width: 150px;">' . $port['port_descr_circuit'] . '</td>
             <td>' . $port['port_descr_notes'] . '</td>');

        echo('</tr><tr><td colspan="6">');

        $rrdfile = get_port_rrdfilename($port, NULL, TRUE);
        if (rrd_is_file($rrdfile, TRUE)) {
            $graph_array['type'] = 'port_bits';
            $graph_array['to']   = get_time();
            $graph_array['id']   = $port['port_id'];

            print_graph_row($graph_array);
        }

        echo('</td></tr>');
    }

    echo('</table>');
    echo generate_box_close();
} else {
    print_warning('None found.');
}

// EOF
