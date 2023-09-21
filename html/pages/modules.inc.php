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

if ($_SESSION['userlevel'] <= 7) {
    print_error_permission();
    return;
}

// r($config['poller_modules']);

$cache_times_db = dbFetchRows("SELECT `device_id`,`hostname`,`device_state` FROM `devices`");

$modules = [];

foreach ($config['poller_modules'] as $module => $state) {
    $modules[$module]['state'] = $state;
}

$total_time = 0;
foreach ($cache_times_db as $cache_time) {
    $cache_time['device_state']            = safe_unserialize($cache_time['device_state']);
    $cache_times[$cache_time['device_id']] = $cache_time;

    foreach ($cache_time['device_state']['poller_mod_perf'] as $module => $time) {
        $modules[$module]['time'] += $time;
        $total_time               += $time;
    }
}

foreach ($modules as $module => $data) {

    if (isset($data['time'])) {
        $modules[$module]['perc'] = round(float_div($data['time'], $total_time) * 100, 2);
    } else {
        $modules[$module]['perc'] = 0;
    }
}

// r($modules);
// r($cache_times);

$modules = array_sort($modules, 'time', 'SORT_DESC');

echo generate_box_open();

echo '
<table class="table table-striped table-condensed ">
  <thead>
    <tr>
      <th>Module</th>
      <th>Description</th>
      <th style="width: 60px;">Status</th>
      <th>Description</th>
      <th width=100px>Total Time</th>
    </tr>
  </thead>
  <tbody>';

foreach ($modules as $module => $data) {

    $bar_bg = get_percentage_colours($data['perc']);

    echo '<tr>';
    echo '  <td><a class="entity" href="' . generate_url(['page' => 'modules', 'module' => $module]) . '">' . $module . '</a></td>';
    echo '  <td></td>';
    echo '  <td>' . ($data['state'] == 1 ? '<span class="label label-success">enabled</span>' : '<span class="label label-error">disabled</span>') . '</td>';
    echo '  <td></td>';
    echo '  <td>' . (isset($modules[$module]['time']) ? round($modules[$module]['time'], 2) . 's' : '-') . '</td>';
    echo '  <td>' . print_percentage_bar('100%', '20', $data['perc'], $data['perc'] . '%', "ffffff", $bar_bg['left'], '', "ffffff", $bar_bg['right']) . '</td>';
    echo '</tr>';

}

echo '
  </tbody>
</table>';

echo generate_box_close();




// EOF
