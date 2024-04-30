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

register_html_title('Deleted ports');

if ($vars['purge'] == 'all') {
    foreach (dbFetchRows('SELECT * FROM `ports` WHERE `deleted` = ?', ['1']) as $port) {
        if (port_permitted($port['port_id'], $port['device_id'])) {
            print_message(delete_port($port['port_id']), 'console');
        }
    }
} elseif (is_numeric($vars['purge'])) {
    $port = dbFetchRow('SELECT * FROM `ports` WHERE `port_id` = ? AND `deleted` = ?', [$vars['purge'], '1']);
    if ($port && port_permitted($port['port_id'], $port['device_id'])) {
        print_message(delete_port($port['port_id']), 'console');
    }
}

echo generate_box_open();

echo('<table class="table table-condensed table-striped  table-condensed">
  <thead><tr>
    <th>Device</th>
    <th>Port</th>
    <th>Description</th>
    <th>Deleted since</th>
    <th style="text-align: right;"><a class="btn btn-danger btn-mini" href="' . generate_url(['page' => 'deleted-ports', 'purge' => 'all']) . '" role="button"><i class="icon-remove icon-white"></i> Purge All</a></th>
  </tr></thead>');

foreach (dbFetchRows('SELECT * FROM `ports` WHERE `deleted` = ?', ['1']) as $port) {
    humanize_port($port);
    $since = get_time('now') - strtotime($port['ifLastChange']);
    if (port_permitted($port['port_id'], $port['device_id'])) {
        echo('<tr class="list">');
        echo('<td style="width: 200px;" class="strong">' . generate_device_link($port) . '</td>');
        echo('<td style="width: 350px;" class="strong">' . generate_port_link($port) . '</td>');
        echo('<td>' . escape_html($port['ifAlias']) . '</td>');
        echo('<td>' . format_uptime($since, 'short-2') . ' ago</td>');
        echo('<td style="width: 100px; text-align: right;"><a class="btn btn-danger btn-mini" href="' . generate_url(['page' => 'deleted-ports', 'purge' => $port['port_id']]) . '" role="button"><i class="icon-remove icon-white"></i> Purge</a></td>');
        echo(PHP_EOL);
    }
}

echo('</table>');

echo generate_box_close();

// EOF
