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

if ($vars['view'] === 'graphs' || $vars['view'] === 'minigraphs') {
    if (isset($vars['graph'])) {
        $graph_type = "port_" . $vars['graph'];
    } else {
        $graph_type = "port_bits";
    }
}

if (!$vars['view']) {
    $vars['view'] = trim($config['ports_page_default'], '/');
}

$navbar = ['brand' => "Ports", 'class' => "navbar-narrow"];

$navbar['options']['basic']['text']   = 'Basic';
$navbar['options']['details']['text'] = 'Details';

if (is_array($ports_exist['navbar'])) {
    $navbar['options'] = array_merge($navbar['options'], $ports_exist['navbar']);
}
/*
if (dbExist('ipv4_addresses', '`device_id` = ?', array($device['device_id'])))
{
  $navbar['options']['ipv4']['text'] = 'IPv4 addresses';
}
if (dbExist('ipv6_addresses', '`device_id` = ?', array($device['device_id'])))
{
  $navbar['options']['ipv6']['text'] = 'IPv6 addresses';
}

// FIXME, need add device_id field into table ip_mac
if (dbFetchCell("SELECT COUNT(*) FROM `ip_mac` LEFT JOIN `ports` USING(`port_id`) WHERE `device_id` = ?", array($device['device_id'])))
//if (dbExist('ip_mac', '`device_id` = ?', array($device['device_id'])))
{
  $navbar['options']['arp']['text'] = 'ARP/NDP Table';
}

if (dbExist('vlans_fdb', '`device_id` = ?', array($device['device_id'])))
{
  $navbar['options']['fdb']['text'] = 'FDB Table';
}

if (dbExist('sensors', '`device_id` = ? AND `measured_class` = ? AND `sensor_deleted` = ?', [ $device['device_id'], 'port', 0 ]))
{
  $navbar['options']['sensors']['text'] = 'Sensors';
}

if (dbExist('neighbours', '`device_id` = ?', array($device['device_id'])))
{
  $navbar['options']['neighbours']['text'] = 'Neighbours';
  $navbar['options']['map']['text']        = 'Map';
}

if (dbExist('ports', '`ifType` = ? AND `device_id` = ?', array('adsl', $device['device_id'])))
{
  $navbar['options']['adsl']['text'] = 'ADSL';
}
*/

$navbar['options']['graphs']     = ['text' => 'Graphs', 'class' => 'pull-right'];
$navbar['options']['minigraphs'] = ['text' => 'Minigraphs', 'class' => 'pull-right'];

foreach ($navbar['options'] as $option => $array) {
    if ($vars['view'] == $option) {
        $navbar['options'][$option]['class'] .= " active";
    }
    $navbar['options'][$option]['url'] = generate_url($link_array, ['view' => $option]);
}

//r($config['graph_types']['port']);
//r($device['graphs']);
foreach (['graphs', 'minigraphs'] as $type) {
    foreach ($config['graph_types']['port'] as $option => $data) {
        // Skip unavailable port graphs
        //if (!isset($device['graphs']['port_'.$option])) { continue; } // device graphs array is not the place for this

        if ($vars['view'] == $type && $vars['graph'] == $option) {
            $navbar['options'][$type]['suboptions'][$option]['class'] = 'active';
            $navbar['options'][$type]['text']                         .= ' (' . $data['name'] . ')';
        }
        $navbar['options'][$type]['suboptions'][$option]['text'] = $data['name'];
        $navbar['options'][$type]['suboptions'][$option]['url']  = generate_url($link_array, ['view' => $type, 'graph' => $option]);
    }
}

// Quick filters

if (isset($vars['view']) && in_array($vars['view'], [ 'basic', 'details', 'graphs', 'minigraphs' ])) {

    // Add filter by ifType
    $extra = [];
    foreach (dbFetchColumn('SELECT DISTINCT `ifType` FROM `ports` WHERE `device_id` = ? AND `deleted` = ?', [ $device['device_id'], 0 ]) as $iftype) {
        foreach ($config['port_types'] as $port_type => $type_entry) {
            if (in_array($iftype, $type_entry['iftype'], TRUE)) {
                $extra[] = $port_type;
                continue 2;
            }
        }
    }

    $filters_array = navbar_ports_filter($navbar, $vars, array_unique($extra));
}

print_navbar($navbar);
unset($navbar);

if ($vars['view'] === 'minigraphs') {
    $timeperiods = ['-1d', '-1w', '-1m', '-1y'];
    $from        = '-1d';
    echo '<div class="row">';
    unset ($seperator);

    $sql   = "SELECT *, `ports`.`port_id` as `port_id`";
    $sql   .= " FROM  `ports`";
    //$sql   .= " WHERE `device_id` = ? ORDER BY `ifIndex` ASC";
    //$ports = dbFetchRows($sql, [$device['device_id']]);
    //r(generate_where_clause('`device_id` = ?', build_ports_where_filter($device, $filters_array)));
    $sql   .= generate_where_clause('`device_id` = ?' , build_ports_where_filter($device, $filters_array)) . " ORDER BY `ifIndex` ASC";

    foreach (dbFetchRows($sql, [ $device['device_id'] ]) as $port) {

        print_port_minigraph($port, $graph_type);
    }

    echo '</div>';
} elseif (is_alpha($vars['view']) && is_file($config['html_dir'] . '/pages/device/ports/' . $vars['view'] . '.inc.php')) {
    include($config['html_dir'] . '/pages/device/ports/' . $vars['view'] . '.inc.php');
} else {
    if ($vars['view'] === "details") {
        $port_details = 1;
    }

    $table_class = $vars['view'] === "graphs" ? OBS_CLASS_TABLE_STRIPED_TWO : OBS_CLASS_TABLE_STRIPED;

    $i = "1";

    $sql   = "SELECT *, `ports`.`port_id` as `port_id`";
    $sql   .= " FROM  `ports`";
    //$sql   .= " WHERE `device_id` = ? ORDER BY `ifIndex` ASC";
    //$ports = dbFetchRows($sql, [$device['device_id']]);
    //r(generate_where_clause('`device_id` = ?', build_ports_where_filter($device, $filters_array)));
    $sql   .= generate_where_clause('`device_id` = ?' , build_ports_where_filter($device, $filters_array)) . " ORDER BY `ifIndex` ASC";
    $ports = dbFetchRows($sql, [ $device['device_id'] ]);

    //r($ports);

    // Sort ports, sharing code with global ports page.
    include($config['html_dir'] . "/includes/port-sort.inc.php");

    /* As we've dragged the whole database, lets pre-populate our caches :) */
    //foreach ($ports as $port) {
    //    $port_cache[$port['port_id']]                           = $port;
    //    $port_index_cache[$port['device_id']][$port['ifIndex']] = @$port_cache[$port['port_id']];
    //}


    // Collect port IDs and ifIndexes who has adsl/cbqos/pagp/ip and other.
    cache_ports_tables($device, $vars);
    //r(array_filter_key($cache, 'ports_', 'starts'));
    //r($cache['ports_stack']);


    echo generate_box_open();
    echo '<table class="' . $table_class . ' table-hover">' . PHP_EOL;

    if ($vars['view'] === 'basic') {
        $cols = [
          [NULL, 'class="state-marker"'],
          [NULL],
          'port'    => ['Port'],
          //[ NULL ],
          'traffic' => ['Traffic'],
          [NULL],
          [NULL],
          'speed'   => ['Speed'],
          'mac'     => ['MAC Address'],
          //[ NULL ],
        ];
    } else {
        $cols = [
          [NULL, 'class="state-marker"'],
          [NULL],
          'port'    => ['Port'],
          [NULL],
          'traffic' => ['Traffic'],
          'speed'   => ['Speed'],
          'mac'     => ['MAC Address'],
          [NULL],
        ];
    }

    echo get_table_header($cols, $vars);
    echo '<tbody>' . PHP_EOL;

    foreach ($ports as $port) {

        print_port_row($port, $vars);
    }

    echo '</tbody></table>';
    echo generate_box_close();
}

register_html_title("Ports");

// EOF
