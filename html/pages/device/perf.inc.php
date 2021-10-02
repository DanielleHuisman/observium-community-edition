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

?>

<div class="row">
  <div class="col-md-12">

<?php

$graph_array = array('type'   => 'device_poller_perf',
                     'device' => $device['device_id']
                     );
?>


<?php

echo generate_box_open(array('title' => 'Poller Performance'));
print_graph_row($graph_array);
echo generate_box_close();

$navbar = array('brand' => "Performance", 'class' => "navbar-narrow");

$navbar['options']['overview']['text']       = 'Overview';
$navbar['options']['poller']['text']         = 'Poller Modules';
$navbar['options']['memory']['text']         = 'Poller Memory';
$navbar['options']['snmp']['text']           = 'Poller SNMP';
$navbar['options']['db']['text']             = 'Poller DB';

foreach ($navbar['options'] as $option => $array)
{
  if (!isset($vars['view'])) { $vars['view'] = "overview"; }
  if ($vars['view'] == $option) { $navbar['options'][$option]['class'] .= " active"; }
  $navbar['options'][$option]['url'] = generate_url($vars, array('view' => $option));
}

print_navbar($navbar);
unset($navbar);

if (is_array($device['state']['poller_mod_perf'])) {
  arsort($device['state']['poller_mod_perf']);
}

if ($vars['view'] === 'db')
{
  echo generate_box_open();
  echo '<table class="' .OBS_CLASS_TABLE_STRIPED_TWO.' table-hover"><tbody>' . PHP_EOL;

  foreach (array('device_pollerdb_count' => 'MySQL Operations',
                 'device_pollerdb_times' => 'MySQL Times') as $graphtype => $name)
  {

    echo '<tr><td><h3>'.$name.'</h3></td></tr>';
    echo '<tr><td>';

    $graph = array('type'   => $graphtype,
                   'device' => $device['device_id']);

    print_graph_row($graph);

    echo '</td></tr>';

  }

  echo '</tbody></table>';
  echo generate_box_close();
}
elseif ($vars['view'] === 'snmp')
{
  echo generate_box_open();
  echo '<table class="' .OBS_CLASS_TABLE_STRIPED_TWO.' table-hover"><tbody>' . PHP_EOL;

  foreach (array('device_pollersnmp_count' => 'SNMP Requests',
                 'device_pollersnmp_times' => 'SNMP Times',
                 'device_pollersnmp_errors_count' => 'SNMP Errors',
                 'device_pollersnmp_errors_times' => 'SNMP Errors Times') as $graphtype => $name)
  {

    echo '<tr><td><h3>'.$name.'</h3></td></tr>';
    echo '<tr><td>';

    $graph = array('type'   => $graphtype,
                   'device' => $device['device_id']);

    print_graph_row($graph);

    echo '</td></tr>';

  }

  echo '</tbody></table>';
  echo generate_box_close();
}
elseif ($vars['view'] === 'memory')
{
  echo generate_box_open();
  echo '<table class="' .OBS_CLASS_TABLE_STRIPED_TWO.' table-hover"><tbody>' . PHP_EOL;

  echo '<tr><td><h3>Memory usage</h3></td></tr>';
  echo '<tr><td>';

  $graph = array('type'   => 'device_pollermemory_perf',
                 'device' => $device['device_id']);

  print_graph_row($graph);

  echo '</td></tr>';

  echo '</tbody></table>';
  echo generate_box_close();
}
elseif ($vars['view'] === 'poller')
{

  echo generate_box_open();
  echo '<table class="' .OBS_CLASS_TABLE_STRIPED_TWO.' table-hover"><tbody>' . PHP_EOL;

  foreach ($device['state']['poller_mod_perf'] as $module => $time)
  {

    echo '<tr><td><h3>'.$module.'</h3></td><td style="width: 40px">'.$time.'s</td></tr>';
    echo '<tr><td colspan=2>';

    $graph = array('type'   => 'device_pollermodule_perf',
                   'device' => $device['device_id'],
                   'module' => $module);

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
  <div class="col-md-6">
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

foreach ($device['state']['poller_mod_perf'] as $module => $time)
{
  if ($time > 0.001)
  {
    $perc = round($time / $device['last_polled_timetaken'] * 100, 2, 2);

    echo('    <tr>
      <td><strong>'.$module.'</strong></td>
      <td style="width: 80px;">'.number_format($time, 4).'s</td>
      <td style="width: 70px;">'.$perc.'%</td>
    </tr>');

    // Separate sub-module perf (ie ports)
    foreach ($device['state']['poller_'.$module.'_perf'] as $submodule => $subtime)
    {
      echo('    <tr>
        <td>&nbsp;<i class="icon-share-alt icon-flip-vertical"></i><strong style="padding-left:1em"><i>'.$submodule.'</i></strong></td>
        <td style="width: 80px;"><i>'.number_format($subtime, 4).'s</i></td>
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

  <div class="col-md-3">
      <div class="box box-solid">
        <div class="box-header with-border">
          <h3 class="box-title">Poller Total Times</h3>
        </div>
        <div class="box-body no-padding">
          <table class="table table-hover table-striped table-condensed ">
            <thead>
              <tr>
                <th>Time</th>
                <th>Duration</th>
              </tr>
            </thead>
            <tbody>
<?php

$times = is_array($device['state']['poller_history']) ? array_slice($device['state']['poller_history'], 0, 30, TRUE) : [];
foreach ($times as $start => $duration)
{
  echo('    <tr>
      <td>'.format_unixtime($start).'</td>
      <td>'.format_uptime($duration).'</td>
    </tr>');
}

?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="box box-solid">
        <div class="box-header with-border">
          <h3 class="box-title">Discovery Times</h3>
        </div>
        <div class="box-body no-padding">
          <table class="table table-hover table-striped  table-condensed ">
            <thead>
              <tr>
                <th>Time</th>
                <th>Duration</th>
              </tr>
            </thead>
            <tbody>
<?php

$times = is_array($device['state']['discovery_history']) ? array_slice($device['state']['discovery_history'], 0, 30, TRUE) : [];
foreach ($times as $start => $duration)
{
  echo('    <tr>
      <td>'.format_unixtime($start).'</td>
      <td>'.format_uptime($duration).'</td>
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
