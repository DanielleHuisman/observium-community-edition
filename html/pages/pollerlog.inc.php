<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

register_html_title("Poller/Discovery Timing");

$navbar = array('brand' => "Performance", 'class' => "navbar-narrow");

if ($_SESSION['userlevel'] >= 7)
{
  $navbar['options']['wrapper']['text']        = 'Wrapper';
}
$navbar['options']['devices']['text']        = 'Per-Device';
$navbar['options']['modules']['text']        = 'Per-Module';

//if (dbFetchCell("SELECT COUNT(*) FROM `pollers`"))
if (dbExist('pollers')) {
  $navbar['options']['pollers']['text']        = 'Pollers';

}

foreach ($navbar['options'] as $option => $array)
{
  if (!isset($vars['view'])) { $vars['view'] = $option; }
  if ($vars['view'] == $option) { $navbar['options'][$option]['class'] .= " active"; }
  $navbar['options'][$option]['url'] = generate_url($vars, array('view' => $option));
}

print_navbar($navbar);
unset($navbar);

// Generate statistics

if (isset($cache['devices']['stat']['count']) && $cache['devices']['stat']['count'] > 0) {
  $proc['avg']['poller']    = round($cache['devices']['timers']['polling'] / $cache['devices']['stat']['count'], 2);
  $proc['avg']['discovery'] = round($cache['devices']['timers']['discovery'] / $cache['devices']['stat']['count'], 2);
} else {
  $proc['avg']['poller']    = 0;
  $proc['avg']['discovery'] = 0;
}
$proc['avg2']['poller']    = 0;
$proc['avg2']['discovery'] = 0;
$proc['max']['poller']    = 0;
$proc['max']['discovery'] = 0;

// Make poller table
$poller_table = array();
foreach ($cache['devices']['hostname'] as $hostname => $id)
{
  // Reference the cache.
  $device = &$cache['devices']['id'][$id];

  if ($device['disabled'] == 1 && !$config['web_show_disabled']) { continue; }

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

  $poller_table[] = array(
    'html_row_class'            => $device['html_row_class'],
    'device_hostname'           => $device['hostname'],
    'device_link'               => generate_device_link($device),
    'device_status'             => $device['status'],
    'device_disabled'           => $device['disabled'],
    'last_polled_timetaken'     => $device['last_polled_timetaken'],
    'last_polled'               => $device['last_polled'],
    'last_discovered_timetaken' => $device['last_discovered_timetaken'],
    'last_discovered'           => $device['last_discovered']
  );

  foreach($device['state']['poller_mod_perf'] AS $mod => $time)
  {
    $mods[$mod]['time'] += $time;
    $mods[$mod]['count']++;
    $mod_total += $time;
  }
}

// End generate statistics

if ($vars['view'] === "modules")
{

  if ($_SESSION['userlevel'] >= 7)
  {
    echo generate_box_open(array('header-border' => TRUE, 'title' => 'Poller Modules'));

    $graph_array = array('type'   => 'global_pollermods',
                         'from'   => $config['time']['week'],
                         'to'     => $config['time']['now'],
                         'legend' => 'no'
                         );
    print_graph_row($graph_array);

    echo generate_box_close();
  }


  echo generate_box_open();
  if ($_SESSION['userlevel'] >= 7)
  {
    echo('<table class="'.OBS_CLASS_TABLE_STRIPED_TWO.'">' . PHP_EOL);
  } else {
    echo('<table class="'.OBS_CLASS_TABLE_STRIPED.'">' . PHP_EOL);
  }

  $mods = array_sort_by($mods, 'time', SORT_DESC, SORT_NUMERIC);

  foreach($mods as $mod => $data)
  {

    $perc = round($data['time'] / $mod_total * 100);
    $bg     = get_percentage_colours($perc);

    echo '<tr>';
    echo '  <td><h3>'.$mod.'</h3></td>';
    echo '  <td width="200">'.print_percentage_bar ('100%', '20', $perc, $perc.'%', "ffffff", $bg['left'], '', "ffffff", $bg['right']).'</td>';
    echo '  <td width="60">'.$data['count'].'</td>';
    echo '  <td width="60">'.round($data['time'], 3).'s</td>';
    echo '</tr>';
    if ($_SESSION['userlevel'] >= 7)
    {
      echo '<tr>';
      echo '  <td colspan=6>';

      $graph_array = array('type'   => 'global_pollermod',
                           'module' => $mod,
                           'legend' => 'no');

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

}
else if ($vars['view'] == "wrapper" && $_SESSION['userlevel'] >= 7)
{

 $rrd_file = $config['rrd_dir'].'/poller-wrapper.rrd';
 if (OBS_RRD_NOLOCAL || is_file($rrd_file))
 {
  echo generate_box_open(array('header-border' => TRUE, 'title' => 'Poller Wrapper History'));

  $graph_array = array('type'   => 'poller_wrapper_threads',
                       //'operation' => 'poll',
  //                     'width'  => 1158,
                       'height' => 100,
                       'from'   => $config['time']['week'],
                       'to'     => $config['time']['now'],
                       );
  //echo(generate_graph_tag($graph_array));
    print_graph_row($graph_array);

  //$graph_array = array('type'   => 'poller_wrapper_count',
  //                     //'operation' => 'poll',
  //                     'width'  => 1158,
  //                     'height' => 100,
  //                     'from'   => $config['time']['week'],
  //                     'to'     => $config['time']['now'],
  //                     );
  //echo(generate_graph_tag($graph_array));
  //echo "<h3>Poller wrapper Total time</h3>";
  $graph_array = array('type'   => 'poller_wrapper_times',
                       //'operation' => 'poll',
//                       'width'  => 1158,
                       'height' => 100,
                       'from'   => $config['time']['week'],
                       'to'     => $config['time']['now'],
                       );
//  echo(generate_graph_tag($graph_array));
    print_graph_row($graph_array);

  echo generate_box_close(array('footer_content' => '<b>Please note:</b> The total time for the poller wrapper is not the same as the timings below. Total poller wrapper time is real polling time for all devices and all threads.'));
 }

}
else if ($vars['view'] == "devices")
{

  if ($_SESSION['userlevel'] >= 7)
  {
    echo generate_box_open(array('header-border' => TRUE, 'title' => 'All Devices Poller Performance'));

    $graph_array = array('type'   => 'global_poller',
                         'from'   => $config['time']['week'],
                         'to'     => $config['time']['now'],
                         'legend' => 'no'
                         );
    print_graph_row($graph_array);

    echo generate_box_close();
  }

echo generate_box_open(array('header-border' => TRUE, 'title' => 'Poller/Discovery Timing'));
echo('<table class="'.OBS_CLASS_TABLE_STRIPED_MORE.'">' . PHP_EOL);

?>

  <thead>
    <tr>
      <th class="state-marker"></th>
      <th>Device</th>
      <th colspan="3">Last Polled</th>
      <th></th>
      <th colspan="3">Last Discovered</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
<?php


// Sort poller table
// sort order: $polled > $discovered > $hostname
$poller_table = array_sort_by($poller_table, 'device_status',             SORT_DESC, SORT_NUMERIC,
                                             'last_polled_timetaken',     SORT_DESC, SORT_NUMERIC,
                                             'last_discovered_timetaken', SORT_DESC, SORT_NUMERIC,
                                             'device_hostname',           SORT_ASC,  SORT_STRING);

// Print poller table
foreach ($poller_table as $row)
{
  $proc['time']['poller']     = round($row['last_polled_timetaken'] * 100 / $proc['max']['poller']);
  $proc['color']['poller']    = "success";
  if     ($row['last_polled_timetaken'] >  ($proc['max']['poller'] * 0.75)) { $proc['color']['poller'] = "danger"; }
  elseif ($row['last_polled_timetaken'] >  ($proc['max']['poller'] * 0.5))  { $proc['color']['poller'] = "warning"; }
  elseif ($row['last_polled_timetaken'] >= ($proc['max']['poller'] * 0.25)) { $proc['color']['poller'] = "info"; }
  $proc['time']['discovery']  = round($row['last_discovered_timetaken'] * 100 / $proc['max']['discovery']);
  $proc['color']['discovery'] = "success";
  if     ($row['last_discovered_timetaken'] >  ($proc['max']['discovery'] * 0.75)) { $proc['color']['discovery'] = "danger"; }
  elseif ($row['last_discovered_timetaken'] >  ($proc['max']['discovery'] * 0.5))  { $proc['color']['discovery'] = "warning"; }
  elseif ($row['last_discovered_timetaken'] >= ($proc['max']['discovery'] * 0.25)) { $proc['color']['discovery'] = "info"; }

  $poll_bg     = get_percentage_colours($proc['time']['poller']);
  $discover_bg = get_percentage_colours($proc['time']['discovery']);

  // Poller times
  echo('    <tr class="'.$row['html_row_class'].'">
      <td class="state-marker"></td>
      <td class="entity">'.$row['device_link'].'</td>
      <td style="width: 12%;">'.
        print_percentage_bar ('100%', '20', $proc['time']['poller'], $proc['time']['poller'].'%', "ffffff", $poll_bg['left'], '', "ffffff", $poll_bg['right'])
      .'</td>
      <td style="width: 7%">
        '.$row['last_polled_timetaken'].'s
      </td>
      <td>'.format_timestamp($row['last_polled']).' </td>
      <td>'.format_uptime($config['time']['now'] - strtotime($row['last_polled']), 'shorter').' ago</td>');

  // Discovery times
  echo('
      <td style="width: 12%;">'.
        print_percentage_bar ('100%', '20', $proc['time']['discovery'], $proc['time']['discovery'].'%', "ffffff", $discover_bg['left'], '', "ffffff", $discover_bg['right'])
      .'</td>
      <td style="width: 7%">
        '.$row['last_discovered_timetaken'].'s
      </td>
      <td>'.format_timestamp($row['last_discovered']).'</td>
      <td>'.format_uptime($config['time']['now'] - strtotime($row['last_discovered']), 'shorter').' ago</td>

    </tr>
');
}

// Calculate root mean square
$proc['avg2']['poller']    = sqrt($proc['avg2']['poller'] / $cache['devices']['stat']['count']);
$proc['avg2']['poller']    = round($proc['avg2']['poller'], 2);
$proc['avg2']['discovery'] = sqrt($proc['avg2']['discovery'] / $cache['devices']['stat']['count']);
$proc['avg2']['discovery'] = round($proc['avg2']['discovery'], 2);

echo('    <tr>
      <th></th>
      <th style="text-align: right;">Total time for all devices (average per device):</th>
      <th></th>
      <th colspan="3">'.$cache['devices']['timers']['polling'].'s ('.$proc['avg2']['poller'].'s)</th>
      <th></th>
      <th colspan="3">'.$cache['devices']['timers']['discovery'].'s ('.$proc['avg2']['discovery'].'s)</th>
    </tr>
');

unset($poller_table, $proc, $row);

?>
  </tbody>
</table>

<?php

echo generate_box_close();

} elseif ($view = 'pollers') {

  $pollers = dbFetchRows("SELECT * FROM `pollers`");

  echo generate_box_open();

  echo '<table class="'.OBS_CLASS_TABLE_STRIPED.'">' . PHP_EOL;

  echo get_table_header([
    [ 'ID',              'style="width: 20px;"' ],
    [ 'Poller Name',     'style="width: 10%;"' ],
    [ 'Host ID / Uname', 'style="width: 30%;"' ],
    'Assigned Devices'
    ]);
  //echo '<tr><td>Poller Name</td><td>Assigned Devices</td></tr>';

  foreach($pollers as $poller) {
      $device_list = array();
      echo '<tr>';
      echo '<td class="entity-name">'.$poller['poller_id'].'</td>';
      echo '<td class="entity-name">'.$poller['poller_name'].'</td>';
      echo '<td><small>'.$poller['host_id'].'<br /><i>'.$poller['host_uname'].'</i></small></td>';
      echo '<td>';

      foreach(dbFetchRows("SELECT * FROM `devices` WHERE `poller_id` = ?", array($poller['poller_id'])) as $device) {
          $device_list[] = generate_device_link($device);
      }

      echo implode(', ', $device_list);

      echo '</td>';
      echo '</tr>';
  }

  echo '</table>';

  echo generate_box_close();

  foreach ($pollers as $poller) {
    echo generate_box_open(array('header-border' => TRUE, 'title' => 'Poller Wrapper ('.$poller['poller_name'].') History'));

    //r($poller);
    $graph_array = [
      'type'   => 'poller_partitioned_wrapper_threads',
      //'operation' => 'poll',
      'id' => $poller['poller_id'],
      // 'width'  => 1158,
      'height' => 100,
      'from'   => $config['time']['week'],
      'to'     => $config['time']['now'],
    ];
    //echo(generate_graph_tag($graph_array));
    print_graph_row($graph_array);

    $graph_array = [
      'type'   => 'poller_partitioned_wrapper_times',
      //'operation' => 'poll',
      'id' => $poller['poller_id'],
      // 'width'  => 1158,
      'height' => 100,
      'from'   => $config['time']['week'],
      'to'     => $config['time']['now'],
    ];
    //echo(generate_graph_tag($graph_array));
    print_graph_row($graph_array);

    echo generate_box_close();

    if ($actions = dbFetchRows('SELECT * FROM `observium_actions` WHERE `poller_id` = ?', [ $poller['poller_id'] ])) {
      echo generate_box_open(array('header-border' => TRUE, 'title' => 'Poller Actions ('.$poller['poller_name'].')'));
      $options = [
        'columns' => [
          'ID', 'Poller', 'Action', 'Identifier', 'Variables', 'Added'
        ],
        'vars' => 'json',
        'added' => 'unixtime'
      ];
      echo build_table($actions, $options);
      echo generate_box_close();
    }

  }
}

// EOF
