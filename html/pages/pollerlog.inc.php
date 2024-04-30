<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Global read permissions required.
if ($_SESSION['userlevel'] < 5) {
    print_error_permission();
    return;
}

register_html_title("Poller/Discovery Timing");

// Generate cache of Pollers
$pollers = [ 0 => [ 'poller_name' => "Default", 'poller_id' => 0 ]];

$navbar = ['brand' => "Poller", 'class' => "navbar-narrow"];

if ($_SESSION['userlevel'] >= 7) {
    $navbar['options']['wrapper']['text'] = 'Wrapper';
}
$navbar['options']['devices']['text'] = 'Per-Device';
$navbar['options']['modules']['text'] = 'Per-Module';

if (OBS_DISTRIBUTED) {

    foreach (dbFetchRows("SELECT *, UNIX_TIMESTAMP() - UNIX_TIMESTAMP(`timestamp`) AS `lasttime` FROM `pollers`") as $entry) {
        $pollers[$entry['poller_id']] = $entry;
    }

    $navbar['options']['pollers']['text'] = 'Partitions';

    $navbar['options_right']['poller_id']['text'] = 'Poller Partition (All)';

    if (!safe_empty($vars['poller_id'])) {
        $navbar['options_right']['poller_id']['suboptions']['all']['text'] = 'All Partitions';
        $navbar['options_right']['poller_id']['suboptions']['all']['url']  = generate_url($vars, ['poller_id' => NULL]);
    }

    foreach ($pollers as $poller) {
        $navbar['options_right']['poller_id']['suboptions'][$poller['poller_id']]['text'] = escape_html($poller['poller_name']);
        $navbar['options_right']['poller_id']['suboptions'][$poller['poller_id']]['url']  = generate_url($vars, ['poller_id' => $poller['poller_id']]);
        if (!safe_empty($vars['poller_id']) && $vars['poller_id'] == $poller['poller_id']) {
            $navbar['options_right']['poller_id']['suboptions'][$poller['poller_id']]['class'] = "active";
            $navbar['options_right']['poller_id']['text']                                      = 'Poller Partition (' . $poller['poller_name'] . ')';
        }
    }
}

foreach ($navbar['options'] as $option => $array) {
    if (!isset($vars['view'])) {
        $vars['view'] = $option;
    }
    if ($vars['view'] == $option) {
        $navbar['options'][$option]['class'] .= " active";
    }
    $navbar['options'][$option]['url'] = generate_url($vars, ['view' => $option]);
}

print_navbar($navbar);
unset($navbar);

// Generate statistics

$totals['pollery']  = 0;
$totals['disovery'] = 0;
$totals['count']    = 0;

$proc['avg2']['poller']    = 0;
$proc['avg2']['discovery'] = 0;
$proc['max']['poller']     = 0;
$proc['max']['discovery']  = 0;

$mod_total = 0;
$mods      = [];

// Make poller table
$devices = [];
foreach (dbFetchRows("SELECT * FROM `devices`") as $device) {

    $device_id = $device['device_id'];
    humanize_device($device);

    if ($device['disabled'] == 1 && !$config['web_show_disabled']) {
        continue;
    }
    if (!safe_empty($vars['poller_id']) && $device['poller_id'] != $vars['poller_id']) {
        // Restricting devices list to matching poller domain.
        //unset($devices[$device['device_id']]);
        continue;
    }

    // Convert empty times to numeric
    $device['last_polled_timetaken']     = (float)$device['last_polled_timetaken'];
    $device['last_discovered_timetaken'] = (float)$device['last_discovered_timetaken'];

    $devices[$device['device_id']] = $device;

    // Find max poller/discovery times
    if ($device['status']) {
        if ($device['last_polled_timetaken'] > $proc['max']['poller']) {
            $proc['max']['poller'] = $device['last_polled_timetaken'];
        }
        if ($device['last_discovered_timetaken'] > $proc['max']['discovery']) {
            $proc['max']['discovery'] = $device['last_discovered_timetaken'];
        }
    }
    $proc['avg2']['poller']    += $device['last_polled_timetaken'] ** 2;
    $proc['avg2']['discovery'] += $device['last_discovered_timetaken'] ** 2;

    $totals['count']++;
    $totals['poller']    += $device['last_polled_timetaken'];
    $totals['discovery'] += $device['last_discovered_timetaken'];

    $devices[$device_id] = array_merge($devices[$device_id], [ 'html_row_class'            => $device['html_row_class'],
                                                              'device_hostname'           => $device['hostname'],
                                                              'device_link'               => generate_device_link($device),
                                                              'device_status'             => $device['status'],
                                                              'device_disabled'           => $device['disabled'],
                                                              'last_polled_timetaken'     => $device['last_polled_timetaken'],
                                                              //'last_polled'               => $device['last_polled'],
                                                              'last_discovered_timetaken' => $device['last_discovered_timetaken'],
                                                              //'last_discovered'           => $device['last_discovered']
                                                             ]
    );

    foreach ($device['state']['poller_mod_perf'] as $mod => $time) {
        $mods[$mod]['time'] += $time;
        $mods[$mod]['count']++;
        $mod_total += $time;
    }
}

$proc['avg']['poller']    = round(float_div($totals['polling'], $totals['count']), 2);
$proc['avg']['discovery'] = round(float_div($totals['discovery'], $totals['count']), 2);

// End generate statistics

if ($vars['view'] === "modules") {

    if ($_SESSION['userlevel'] >= 7) {
        echo generate_box_open(['header-border' => TRUE, 'title' => 'Poller Modules']);

        $graph_array = [
          'type'   => 'global_pollermods',
          'from'   => get_time('week'),
          'to'     => get_time(),
          'legend' => 'no'
        ];

        if (!safe_empty($vars['poller_id']) && is_numeric($vars['poller_id'])) {
            $graph_array['poller_id'] = $vars['poller_id'];
        }

        print_graph_row($graph_array);

        echo generate_box_close();
    }


    echo generate_box_open();
    if ($_SESSION['userlevel'] >= 7) {
        echo('<table class="' . OBS_CLASS_TABLE_STRIPED_TWO . '">' . PHP_EOL);
    } else {
        echo('<table class="' . OBS_CLASS_TABLE_STRIPED . '">' . PHP_EOL);
    }

    $mods = array_sort_by($mods, 'time', SORT_DESC, SORT_NUMERIC);

    foreach ($mods as $mod => $data) {

        $perc = round(float_div($data['time'], $mod_total) * 100);
        $bg   = get_percentage_colours($perc);

        echo '<tr>';
        echo '  <td><h3>' . $mod . '</h3></td>';
        echo '  <td width="200">' . print_percentage_bar('100%', '20', $perc, $perc . '%', "ffffff", $bg['left'], '', "ffffff", $bg['right']) . '</td>';
        echo '  <td width="60">' . $data['count'] . '</td>';
        echo '  <td width="60">' . round($data['time'], 3) . 's</td>';
        echo '</tr>';
        if ($_SESSION['userlevel'] >= 7) {
            echo '<tr>';
            echo '  <td colspan=6>';

            $graph_array = [
              'type'   => 'global_pollermod',
              'module' => $mod,
              'legend' => 'no'
            ];

            if (!safe_empty($vars['poller_id']) && is_numeric($vars['poller_id'])) {
                $graph_array['poller_id'] = $vars['poller_id'];
            }

            print_graph_row($graph_array);

            echo '  </td>';
            echo '</tr>';
        }

    }

    ?>
    </tbody>
    </table>

    <?php

    echo generate_box_close();

} elseif ($vars['view'] === "wrapper" && $_SESSION['userlevel'] >= 7) {

    $rrd_file = $config['rrd_dir'] . '/poller-wrapper.rrd';
    if (rrd_is_file($rrd_file, TRUE)) {
        echo generate_box_open(['header-border' => TRUE, 'title' => 'Poller Wrapper History']);

        $graph_array = [
          'type'   => 'poller_wrapper_threads',
          //'operation' => 'poll',
          //'width'  => 1158,
          'height' => 100,
          'from'   => get_time('week'),
          'to'     => get_time()
        ];

        // We've selected a paritioned poller. Switch the graph.
        if (isset($vars['poller_id']) && $vars['poller_id'] != 0) {
            $graph_array['type'] = 'poller_partitioned_wrapper_threads';
            $graph_array['id']   = $vars['poller_id'];
        }

        //echo(generate_graph_tag($graph_array));
        print_graph_row($graph_array);

        //$graph_array = array('type'   => 'poller_wrapper_count',
        //                     //'operation' => 'poll',
        //                     'width'  => 1158,
        //                     'height' => 100,
        //                     'from'   => get_time('week'),
        //                     'to'     => get_time('now'),
        //                     );
        //echo(generate_graph_tag($graph_array));
        //echo "<h3>Poller wrapper Total time</h3>";
        $graph_array = [
          'type'   => 'poller_wrapper_times',
          //'operation' => 'poll',
          //'width'  => 1158,
          'height' => 100,
          'from'   => get_time('week'),
          'to'     => get_time()
        ];

        // We've selected a paritioned poller. Switch the graph.
        if (isset($vars['poller_id']) && $vars['poller_id'] != 0) {
            $graph_array['type'] = 'poller_partitioned_wrapper_times';
            $graph_array['id']   = $vars['poller_id'];
        }

        //echo(generate_graph_tag($graph_array));
        print_graph_row($graph_array);

        //echo generate_box_close([ 'footer_content' => '<b>Please note:</b> Total poller wrapper time is the execution time for the poller wrapper process.' ]);
        echo generate_box_close();

    }

} elseif ($vars['view'] === "devices") {

    if ($_SESSION['userlevel'] >= 7) {
        echo generate_box_open(['header-border' => TRUE, 'title' => 'All Devices Poller Performance']);

        $graph_array = [
          'type'   => 'global_poller',
          'from'   => get_time('week'),
          'to'     => get_time(),
          'legend' => 'no'
        ];

        if (!safe_empty($vars['poller_id']) && is_numeric($vars['poller_id'])) {
            $graph_array['poller_id'] = $vars['poller_id'];
        }

        print_graph_row($graph_array);

        echo generate_box_close();
    }

    echo generate_box_open(['header-border' => TRUE, 'title' => 'Poller/Discovery Timing']);
    echo('<table class="' . OBS_CLASS_TABLE_STRIPED_MORE . '">' . PHP_EOL);

// FIXME -- table header generator / sorting

    ?>

    <thead>
    <tr>
        <th class="state-marker"></th>
        <th>Device</th>
        <th colspan="3">Last Polled</th>
        <th colspan="3">Last Discovered</th>
        <?php if (safe_empty($vars['poller_id'])) {
            echo '<th>Poller</th>';
        } ?>
    </tr>
    </thead>
    <tbody>
    <?php


    // Sort poller table
    // sort order: $polled > $discovered > $hostname
    $devices = array_sort_by($devices, 'device_status', SORT_DESC, SORT_NUMERIC,
                             'last_polled_timetaken', SORT_DESC, SORT_NUMERIC,
                             'last_discovered_timetaken', SORT_DESC, SORT_NUMERIC,
                             'device_hostname', SORT_ASC, SORT_STRING);

    // Print poller table
    foreach ($devices as $row) {
        $proc['time']['poller']  = round(float_div($row['last_polled_timetaken'], $proc['max']['poller']) * 100);
        $proc['color']['poller'] = "success";
        if ($row['last_polled_timetaken'] > ($proc['max']['poller'] * 0.75)) {
            $proc['color']['poller'] = "danger";
        } elseif ($row['last_polled_timetaken'] > ($proc['max']['poller'] * 0.5)) {
            $proc['color']['poller'] = "warning";
        } elseif ($row['last_polled_timetaken'] >= ($proc['max']['poller'] * 0.25)) {
            $proc['color']['poller'] = "info";
        }
        $proc['time']['discovery']  = round(float_div($row['last_discovered_timetaken'], $proc['max']['discovery']) * 100);
        $proc['color']['discovery'] = "success";
        if ($row['last_discovered_timetaken'] > ($proc['max']['discovery'] * 0.75)) {
            $proc['color']['discovery'] = "danger";
        } elseif ($row['last_discovered_timetaken'] > ($proc['max']['discovery'] * 0.5)) {
            $proc['color']['discovery'] = "warning";
        } elseif ($row['last_discovered_timetaken'] >= ($proc['max']['discovery'] * 0.25)) {
            $proc['color']['discovery'] = "info";
        }

        $poll_bg     = get_percentage_colours($proc['time']['poller']);
        $discover_bg = get_percentage_colours($proc['time']['discovery']);

        // Poller times
        echo('    <tr class="' . $row['html_row_class'] . '">
      <td class="state-marker"></td>
      <td class="entity">' . $row['device_link'] . '</td>
      <td style="width: 12%;">' .
             print_percentage_bar('100%', '20', $proc['time']['poller'], $proc['time']['poller'] . '%', "ffffff", $poll_bg['left'], '', "ffffff", $poll_bg['right'])
             . '</td>
      <td style="width: 7%">
        ' . $row['last_polled_timetaken'] . 's
      </td>
      <!-- <td>' . format_timestamp($row['last_polled']) . ' </td> -->
      <td>' . format_uptime(get_time('now') - strtotime($row['last_polled']), 'shorter') . ' ago</td>');

        // Discovery times
        echo('
      <td style="width: 12%;">' .
             print_percentage_bar('100%', '20', $proc['time']['discovery'], $proc['time']['discovery'] . '%', "ffffff", $discover_bg['left'], '', "ffffff", $discover_bg['right'])
             . '</td>
      <td style="width: 7%">
        ' . $row['last_discovered_timetaken'] . 's
      </td>
      <!-- <td>' . format_timestamp($row['last_discovered']) . '</td> -->
      <td>' . format_uptime(get_time('now') - strtotime($row['last_discovered']), 'shorter') . ' ago</td> ');

        if (safe_empty($vars['poller_id'])) {
            echo '
   <td>' . get_type_class_label($pollers[$row['poller_id']]['poller_name'], 'poller') . '</td>';
        }

        echo '
    </tr>
';
    }

    // Calculate root mean square
    $proc['avg2']['poller']    = sqrt(float_div($proc['avg2']['poller'], $totals['count']));
    $proc['avg2']['poller']    = round($proc['avg2']['poller'], 2);
    $proc['avg2']['discovery'] = sqrt(float_div($proc['avg2']['discovery'], $totals['count']));
    $proc['avg2']['discovery'] = round($proc['avg2']['discovery'], 2);

    echo('    <tr>
      <th></th>
      <th style="text-align: right;">Total time for all devices (average per device):</th>
      <th></th>
      <th colspan="3">' . $totals['poller'] . 's (' . $proc['avg2']['poller'] . 's)</th>
      <th></th>
      <th colspan="3">' . $totals['discovery'] . 's (' . $proc['avg2']['discovery'] . 's)</th>
    </tr>
');

    unset($row);

    ?>
    </tbody>
    </table>

    <?php

    echo generate_box_close();

} elseif ($view = 'pollers') {

    //$pollers = dbFetchRows("SELECT * FROM `pollers`");
    unset($pollers[0]); // remove default poller here

    echo generate_box_open();

    echo '<table class="' . OBS_CLASS_TABLE_STRIPED . '">' . PHP_EOL;

    echo get_table_header([
                            [ 'ID', 'style="width: 20px;"' ],
                            [ 'Poller Name', 'style="width: 8%;"' ],
                            [ 'Host ID / Uname', 'style="width: 30%;"' ],
                            [ 'Version', 'style="width: 80px;"' ],
                            'Assigned Devices',
                            [ 'Polled (ago)', 'style="width: 80px;"' ],
                          ]);
    //echo '<tr><td>Poller Name</td><td>Assigned Devices</td></tr>';

    foreach ($pollers as $poller) {
        $device_list = [];
        echo '<tr>';
        echo '<td class="entity-name">' . $poller['poller_id'] . '</td>';
        echo '<td class="entity-name">' . $poller['poller_name'] . '</td>';
        if ($poller['device_id']) {
            echo '<td><small>' . $poller['host_id'] . ' (' . generate_device_link($poller['device_id']) . ')<br /><i>' .
                generate_device_link($poller['device_id'], $poller['host_uname']) . '</i></small></td>';
        } else {
            echo '<td><small>' . $poller['host_id'] . '<br /><i>' . $poller['host_uname'] . '</i></small></td>';
        }
        $poller_version = explode(' ', $poller['poller_version'])[0];
        $poller_revision = explode('.', $poller_version)[2];
        if ((get_obs_attrib('latest_rev') - $poller_revision) >= $config['version_check_revs']) {
            echo '<td><span class="label label-error">' . $poller_version . '</span></td>';
        } elseif (abs(OBSERVIUM_REV - $poller_revision) >= $config['version_check_revs']) {
            echo '<td><span class="label label-warning">' . $poller_version . '</span></td>';
        } else {
            echo '<td><span class="label">' . $poller_version . '</span></td>';
        }

        echo '<td>';
        foreach (dbFetchRows("SELECT * FROM `devices` WHERE `poller_id` = ?", [$poller['poller_id']]) as $device) {
            $device_list[] = generate_device_link($device);
        }

        $pollers[$poller['poller_id']]['devices'] = count($device_list);
        echo implode(', ', $device_list);
        echo '</td>';

        if ($poller['lasttime'] < 3600) {
            // Use dynamic counter
            $poller_refresh = 'poller_'.$poller['poller_id'].'_refresh';
            register_html_resource('script', 'time_refresh("'.$poller_refresh.'", '.$poller['lasttime'].', 1)');
            echo '<td><span id="'.$poller_refresh.'"></span></td>';
        } else {
            echo '<td>' . format_uptime($poller['lasttime']) . '</td>';
        }
        echo '</tr>';
    }

    echo '</table>';

    echo generate_box_close();

    foreach ($pollers as $poller) {
        if (!$poller['devices']) {
            // poller not have associated device, skip graphs
            continue;
        }
        echo generate_box_open(['header-border' => TRUE, 'title' => 'Poller Wrapper (' . $poller['poller_name'] . ') History']);

        //r($poller);
        $graph_array = [
          'type'   => 'poller_partitioned_wrapper_threads',
          //'operation' => 'poll',
          'id'     => $poller['poller_id'],
          // 'width'  => 1158,
          'height' => 100,
          'from'   => get_time('week'),
          'to'     => get_time(),
        ];
        //echo(generate_graph_tag($graph_array));
        print_graph_row($graph_array);

        $graph_array = [
          'type'   => 'poller_partitioned_wrapper_times',
          //'operation' => 'poll',
          'id'     => $poller['poller_id'],
          // 'width'  => 1158,
          'height' => 100,
          'from'   => get_time('week'),
          'to'     => get_time(),
        ];
        //echo(generate_graph_tag($graph_array));
        print_graph_row($graph_array);

        echo generate_box_close();

        if ($actions = dbFetchRows('SELECT * FROM `observium_actions` WHERE `poller_id` = ?', [$poller['poller_id']])) {
            echo generate_box_open(['header-border' => TRUE, 'title' => 'Poller Actions (' . $poller['poller_name'] . ')']);
            $options = [
              'columns' => [
                'ID', 'Poller', 'Action', 'Identifier', 'Variables', 'Added'
              ],
              'vars'    => 'json',
              'added'   => 'unixtime'
            ];
            echo build_table($actions, $options);
            echo generate_box_close();
        }

    }
}

unset($devices, $proc, $pollers);

// EOF
