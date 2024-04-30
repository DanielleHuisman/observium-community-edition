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

?>

    <div class="row">
    <div class="col-md-12">

<?php

$graph_array = [
  'type'   => 'device_poller_perf',
  'device' => $device['device_id']
];

echo generate_box_open(['title' => 'Poller Performance']);
print_graph_row($graph_array);
echo generate_box_close();

$sql = "SELECT `process_command`, `process_name`, `process_start`, `poller_id` FROM `observium_processes` WHERE `device_id` = ? ORDER BY `process_ppid`, `process_start`";
if ($processes = dbFetchRows($sql, [$device['device_id']])) {
    echo generate_box_open(['title' => 'Running Processes']);
    $cols = [
        //'Process ID', 'PID', 'PPID', 'UID',
        'Command', 'Name', 'Started', 'Poller ID'
        //'Device'
    ];
    echo build_table($processes, ['columns' => $cols, 'process_start' => 'prettytime']);
    echo generate_box_close();
}

$navbar = ['brand' => "Performance", 'class' => "navbar-narrow"];

$navbar['options']['overview']['text'] = 'Overview';
$navbar['options']['poller']['text']   = 'Poller Modules';
$navbar['options']['memory']['text']   = 'Poller Memory';
$navbar['options']['snmp']['text']     = 'Poller SNMP';
$navbar['options']['db']['text']       = 'Poller DB';

foreach ($navbar['options'] as $option => $array) {
    if (!isset($vars['view'])) {
        $vars['view'] = "overview";
    }
    if ($vars['view'] == $option) {
        $navbar['options'][$option]['class'] .= " active";
    }
    $navbar['options'][$option]['url'] = generate_url($vars, ['view' => $option]);
}

print_navbar($navbar);
unset($navbar);

if (is_array($device['state']['poller_mod_perf'])) {
    arsort($device['state']['poller_mod_perf']);
}
if (is_array($device['state']['discovery_mod_perf'])) {
    arsort($device['state']['discovery_mod_perf']);
}

if ($vars['view'] === 'db') {
    echo generate_box_open();
    echo '<table class="' . OBS_CLASS_TABLE_STRIPED_TWO . ' table-hover"><tbody>' . PHP_EOL;

    foreach (['device_pollerdb_count' => 'MySQL Operations',
              'device_pollerdb_times' => 'MySQL Times'] as $graphtype => $name) {

        echo '<tr><td><h3>' . $name . '</h3></td></tr>';
        echo '<tr><td>';

        $graph = ['type'   => $graphtype,
                  'device' => $device['device_id']];

        print_graph_row($graph);

        echo '</td></tr>';

    }

    echo '</tbody></table>';
    echo generate_box_close();
} elseif ($vars['view'] === 'snmp') {
    echo generate_box_open();
    echo '<table class="' . OBS_CLASS_TABLE_STRIPED_TWO . ' table-hover"><tbody>' . PHP_EOL;

    foreach (['device_pollersnmp_count'        => 'SNMP Requests',
              'device_pollersnmp_times'        => 'SNMP Times',
              'device_pollersnmp_errors_count' => 'SNMP Errors',
              'device_pollersnmp_errors_times' => 'SNMP Errors Times'] as $graphtype => $name) {

        echo '<tr><td><h3>' . $name . '</h3></td></tr>';
        echo '<tr><td>';

        $graph = ['type'   => $graphtype,
                  'device' => $device['device_id']];

        print_graph_row($graph);

        echo '</td></tr>';

    }

    echo '</tbody></table>';
    echo generate_box_close();
} elseif ($vars['view'] === 'memory') {
    echo generate_box_open();
    echo '<table class="' . OBS_CLASS_TABLE_STRIPED_TWO . ' table-hover"><tbody>' . PHP_EOL;

    echo '<tr><td><h3>Memory usage</h3></td></tr>';
    echo '<tr><td>';

    $graph = ['type'   => 'device_pollermemory_perf',
              'device' => $device['device_id']];

    print_graph_row($graph);

    echo '</td></tr>';

    echo '</tbody></table>';
    echo generate_box_close();
} elseif ($vars['view'] === 'poller') {

    echo generate_box_open();
    echo '<table class="' . OBS_CLASS_TABLE_STRIPED_TWO . ' table-hover"><tbody>' . PHP_EOL;

    foreach ($device['state']['poller_mod_perf'] as $module => $time) {

        echo '<tr><td><h3>' . $module . '</h3></td><td style="width: 40px">' . $time . 's</td></tr>';
        echo '<tr><td colspan=2>';

        $graph = ['type'   => 'device_pollermodule_perf',
                  'device' => $device['device_id'],
                  'module' => $module];

        print_graph_row($graph);

        echo '</td></tr>';

    }

    echo '</tbody></table>';
    echo generate_box_close();

} else {

    ?>

    </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">Poller Module Times</h3>
                </div>
                <div class="box-body no-padding">
                    <table class="table table-hover table-striped table-condensed">
                        <thead>
                        <tr>
                            <th>Module</th>
                            <th colspan="2">Duration</th>

                        </tr>
                        </thead>
                        <tbody>
                        <?php

                        //r($device['state']['poller_mod_perf']);
                        foreach ($device['state']['poller_mod_perf'] as $module => $time) {
                            if ($time > 0.001) {
                                $perc = format_number_short(float_div($time, $device['last_polled_timetaken']) * 100, 2);

                                echo('    <tr>
      <td><strong>' . $module . '</strong></td>
      <td style="width: 80px;">' . format_value($time) . 's</td>
      <td style="width: 70px;"><span style="color:' . percent_colour($perc) . '">' . $perc . '%</span></td>
    </tr>');

                                // Separate sub-module perf (ie ports)
                                foreach ($device['state']['poller_' . $module . '_perf'] as $submodule => $subtime) {
                                    echo('    <tr>
        <td>&nbsp;<i class="icon-share-alt icon-flip-vertical"></i><strong style="padding-left:1em"><i>' . $submodule . '</i></strong></td>
        <td style="width: 80px;"><i>' . format_value($subtime) . 's</i></td>
        <td style="width: 70px;"></td>
      </tr>');
                                }
                            }
                        }

                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-2">
            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">Poller Times</h3>
                </div>
                <div class="box-body no-padding">
                    <table class="table table-hover table-striped table-condensed ">
                        <thead>
                        <tr>
                            <th>Time</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php

                        $times = is_array($device['state']['poller_history']) ? array_slice($device['state']['poller_history'], 0, 30, TRUE) : [];
                        foreach ($times as $start => $duration) {
                            echo('    <tr>
      <td>' . generate_tooltip_time($start, 'ago') . '</td>
      <td>' . format_uptime($duration) . '</td>
    </tr>');
                        }

                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">


            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">Discovery Module Times</h3>
                </div>
                <div class="box-body no-padding">
                    <table class="table table-hover table-striped table-condensed">
                        <thead>
                        <tr>
                            <th>Module</th>
                            <th colspan="2">Duration</th>

                        </tr>
                        </thead>
                        <tbody>
                        <?php

                        //r($device['state']);

                        foreach ($device['state']['discovery_mod_perf'] as $module => $time) {
                            if ($time > 0.001) {
                                $perc = format_number_short(float_div($time, $device['last_discovered_timetaken']) * 100, 2);

                                echo('    <tr>
      <td><strong>' . $module . '</strong></td>
      <td style="width: 80px;">' . format_value($time) . 's</td>
      <td style="width: 70px;"><span style="color:' . percent_colour($perc) . '">' . $perc . '%</span></td>
    </tr>');

                                // Separate sub-module perf (ie ports)
                                foreach ($device['state']['discovery_' . $module . '_perf'] as $submodule => $subtime) {
                                    echo('    <tr>
        <td>&nbsp;<i class="icon-share-alt icon-flip-vertical"></i><strong style="padding-left:1em"><i>' . $submodule . '</i></strong></td>
        <td style="width: 80px;"><i>' . format_value($subtime) . 's</i></td>
        <td style="width: 70px;"></td>
      </tr>');
                                }
                            }
                        }

                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-2">

            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">Discovery Times</h3>
                </div>
                <div class="box-body no-padding">
                    <table class="table table-hover table-striped  table-condensed ">
                        <thead>
                        <tr>
                            <th>Time</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php

                        $times = is_array($device['state']['discovery_history']) ? array_slice($device['state']['discovery_history'], 0, 30, TRUE) : [];
                        foreach ($times as $start => $duration) {
                            echo('    <tr>
      <td>' . generate_tooltip_time($start, 'ago') . '</td>
      <td>' . format_uptime($duration) . '</td>
    </tr>');
                        }

                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php

}

// EOF
