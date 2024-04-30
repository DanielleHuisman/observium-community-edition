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

register_html_title('Locations');

if (!$vars['view']) {
    $vars['view'] = 'basic';
}

$navbar['brand'] = 'Locations';
$navbar['class'] = 'navbar-narrow';

foreach (['basic', 'traffic'] as $type) {
    if ($vars['view'] == $type) {
        $navbar['options'][$type]['class'] = 'active';
    }
    $navbar['options'][$type]['url']  = generate_url(['page' => 'locations', 'view' => $type]);
    $navbar['options'][$type]['text'] = ucfirst($type);
}
print_navbar($navbar);
unset($navbar);

echo generate_box_open();

echo('<table class="table table-hover  table-striped table-condensed ">' . PHP_EOL);

//$location_where = generate_query_values_and($vars['location'], 'location');

$cols = [
  [NULL, 'class="state-marker"'],
  'location' => ['Location', 'style="width: 300px;"'],
  'total'    => ['Devices:&nbsp;Total', 'style="width: 50px; text-align: right;"']
];

foreach (array_keys($cache['devices']['types']) as $type) {
    $cols[$type] = [nicecase($type), 'style="width: 40px;"'];
}
echo get_table_header($cols); //, $vars); // Currently sorting is not available

echo('<tr class="success">
          <td class="state-marker"></td>
          <td class="entity">ALL</td>
          <td style="text-align: right;"><strong class="label label-success">' . $devices['count'] . '</strong></td>' . PHP_EOL);
foreach ($cache['devices']['types'] as $type => $type_data) {
    echo('<td><strong class="label label-info">' . $type_data['count'] . '</strong></td>' . PHP_EOL);
}
echo('      </tr>');

foreach (get_locations() as $location) {

    $location_where = generate_where_clause($GLOBALS['cache']['where']['devices_permitted'], generate_query_values($location, 'location'));

    $num = dbFetchCell('SELECT COUNT(*) FROM `devices`' . $location_where);

    $hostalerts = dbFetchCell('SELECT COUNT(*) FROM `devices`' . $location_where . ' AND `status` = ?', [0]);
    if ($hostalerts) {
        $row_class = 'error';
    } else {
        $row_class = '';
    }

    if ($location === '') {
        $location = OBS_VAR_UNSET;
    }
    $value = var_encode($location);

    echo('<tr class="' . $row_class . '">
          <td class="state-marker"></td>
          <td class="entity">' . generate_link($location, ['page' => 'devices', 'location' => $value]) . '</td>
          <td style="text-align: right;"><strong class="label label-success">' . $num . '</strong></td>' . PHP_EOL);
    foreach (array_keys($cache['devices']['types']) as $type) {
        $location_count = dbFetchCell('SELECT COUNT(*) FROM `devices`' . $location_where . ' AND `type` = ?', [$type]);
        if ($location_count > 0) {
            $location_count = '<span class="label">' . $location_count . '</span>';
        }
        echo('<td>' . $location_count . '</td>' . PHP_EOL);
    }
    echo('      </tr>');

    if ($vars['view'] == 'traffic') {
        echo('<tr></tr><tr class="locations"><td colspan="' . count($cols) . '">');

        $graph_array['type']   = 'location_bits';
        $graph_array['height'] = 100;
        $graph_array['width']  = 220;
        $graph_array['to']     = get_time();
        $graph_array['legend'] = 'no';
        $graph_array['id']     = $value;

        print_graph_row($graph_array);

        echo('</tr></td>');
    }
    $done = 'yes';
}

echo('</table>');
echo generate_box_close();

// EOF
