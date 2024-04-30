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

// Set Defaults here

if (!isset($vars['format'])) {
    $vars['format'] = "detail";
}
if (!$config['web_show_disabled'] && !isset($vars['disabled'])) {
    $vars['disabled'] = '0';
}
if ($vars['format'] != 'graphs') {
    // reset all from/to vars if not use graphs
    unset($vars['from'], $vars['to'], $vars['timestamp_from'], $vars['timestamp_to'], $vars['graph']);
}

$query_permitted = generate_query_permitted_ng(['device'], ['device_table' => 'devices']);

$where_array = build_devices_where_array($vars);

// Fixme. Unused?
$where = generate_where_clause($where_array);

register_html_title("Devices");
if (is_string($vars['type']) && isset($config['devicetypes'][$vars['type']]['text'])) {
    register_html_title($config['devicetypes'][$vars['type']]['text']);
} elseif (isset($vars['status']) && !$vars['status'] && safe_count($vars) === 3) {
    register_html_title("Down");
} elseif (isset($vars['disabled']) && $vars['disabled'] && safe_count($vars) === 3) {
    register_html_title("Disabled");
}

foreach ($config['device_types'] as $entry) {
    $types[$entry['type']] = $entry;
}

$query_join_geocoding = '';
if ($config['geocoding']['enable']) {
    foreach (['location_lat', 'location_lon', 'location_country', 'location_state', 'location_county', 'location_city'] as $field) {
        if (isset($where_array[$field])) {
            $query_join_geocoding = ' LEFT JOIN `devices_locations` USING (`device_id`)';
            break;
        }
    }
}

//r($where_array);

// Generate array with form elements
$form_items = [];
foreach (['os', 'hardware', 'vendor', 'version', 'features', 'type', 'distro'] as $entry) {
    $query = "SELECT DISTINCT `$entry` FROM `devices`" . $query_join_geocoding;

    $tmp_where_array         = $where_array;
    $tmp_where_array[$entry] = "`$entry` != ''";
    $query                   .= generate_where_clause($tmp_where_array, $query_permitted);
    unset($tmp_where_array);
    $selected = dbFetchColumn($query);
    //r($query);

    $query_all = "SELECT DISTINCT `$entry` FROM `devices` " . generate_where_clause("`$entry` != ''", $query_permitted) . " ORDER BY `$entry`";
    //r($query_all);

    foreach (dbFetchColumn($query_all) as $item) {
        $group = in_array($item, $selected, TRUE) ? "Listed" : "Other";
        if ($entry === 'os') {
            $name = ['name' => $config['os'][$item]['text'], 'group' => $group];
        } elseif ($entry === 'type') {
            if (isset($types[$item])) {
                $name = ['name' => $types[$item]['text'], 'icon' => $types[$item]['icon'], 'group' => $group];
            } else {
                $name = ['name' => nicecase($item), 'icon' => $config['icon']['exclamation'], 'group' => $group];
            }
        } else {
            $name = ['name' => nicecase($item), 'group' => $group];
        }
        $form_items[$entry][$item] = $name;
    }
}


if (isset($form_items['os'])) {
    asort($form_items['os']);
}

$tmp_where_array = $where_array;
if (isset($where_array['location'])) {
    unset($tmp_where_array['location']);
}
$query    = "SELECT DISTINCT `devices`.`location` FROM `devices`" . $query_join_geocoding;
$query    .= generate_where_clause($tmp, $query_permitted);
$selected = dbFetchColumn($query);
unset($tmp_where_array);

foreach (get_locations() as $entry) {
    $group = in_array($entry, $selected, TRUE) ? "Listed" : "Other";
    if ($entry === '') {
        $entry = OBS_VAR_UNSET;
    }
    $form_items['location'][$entry] = ['name' => $entry, 'group' => $group];
}

foreach (get_type_groups('device') as $entry) {
    $form_items['group'][$entry['group_id']] = $entry['group_name'];
}
//print_vars($form_items);

$form_items['sort'] = ['hostname' => 'Hostname',
                       'domain'   => 'Hostname in Domain Order',
                       'location' => 'Location',
                       'os'       => 'Operating System',
                       'version'  => 'Version',
                       'features' => 'Featureset',
                       'type'     => 'Device Type',
                       'uptime'   => 'Uptime'];

$form = ['type'          => 'rows',
         'space'         => '10px',
         'submit_by_key' => TRUE,
         'url'           => generate_url($vars)];
// First row
$form['row'][0]['hostname'] = [
  'type'        => 'text',
  'name'        => 'Hostname',
  'value'       => $vars['hostname'],
  'width'       => '100%', //'180px',
  'placeholder' => TRUE];
$form['row'][0]['location'] = [
  'type'   => 'multiselect',
  'name'   => 'Select Locations',
  'width'  => '100%', //'180px',
  'encode' => TRUE,
  'value'  => $vars['location'],
  'groups' => ['Listed', 'Other'],
  'values' => $form_items['location']];
$form['row'][0]['os']       = [
  'type'   => 'multiselect',
  'name'   => 'Select OS',
  'width'  => '100%', //'180px',
  'value'  => $vars['os'],
  'groups' => ['Listed', 'Other'],
  'values' => $form_items['os']];
$form['row'][0]['hardware'] = [
  'type'   => 'multiselect',
  'name'   => 'Select Hardware',
  'width'  => '100%', //'180px',
  'value'  => $vars['hardware'],
  'groups' => ['Listed', 'Other'],
  'values' => $form_items['hardware']];
$form['row'][0]['vendor']   = [
  'type'   => 'multiselect',
  'name'   => 'Select Vendor',
  'width'  => '100%', //'180px',
  'value'  => $vars['vendor'],
  'groups' => ['Listed', 'Other'],
  'values' => $form_items['vendor']];
$form['row'][0]['group']    = [
  'type'   => 'multiselect',
  'name'   => 'Select Groups',
  'width'  => '100%', //'180px',
  'value'  => $vars['group'],
  'values' => $form_items['group']];
// Select sort pull-rigth
//$form['row'][0]['sort']     = array(
//                                'type'        => 'select',
//                                'icon'        => $config['icon']['sort'],
//                                'right'       => TRUE,
//                                'width'       => '100%', //'150px',
//                                'value'       => $vars['sort'],
//                                'values'      => $form_items['sort']);

// Second row
$form['row'][1]['sysname']       = [
  'type'        => 'text',
  'name'        => 'sysName',
  'value'       => $vars['sysname'],
  'width'       => '100%', //'180px',
  'placeholder' => TRUE];
$form['row'][1]['location_text'] = [
  'type'        => 'text',
  'name'        => 'Location',
  'value'       => $vars['location_text'],
  'width'       => '100%', //'180px',
  'placeholder' => TRUE];
$form['row'][1]['version']       = [
  'type'   => 'multiselect',
  'name'   => 'Select OS Version',
  'width'  => '100%', //'180px',
  'value'  => $vars['version'],
  'groups' => ['Listed', 'Other'],
  'values' => $form_items['version']];
$form['row'][1]['features']      = [
  'type'   => 'multiselect',
  'name'   => 'Select Featureset',
  'width'  => '100%', //'180px',
  'value'  => $vars['features'],
  'groups' => ['Listed', 'Other'],
  'values' => $form_items['features']];
$form['row'][1]['type']          = [
  'type'   => 'multiselect',
  'name'   => 'Select Device Type',
  'width'  => '100%', //'180px',
  'value'  => $vars['type'],
  'groups' => ['Listed', 'Other'], // Order
  'values' => $form_items['type']];
// Select sort pull-right
$form['row'][1]['sort'] = [
  'type'   => 'select',
  'icon'   => $config['icon']['sort'],
  'right'  => TRUE,
  'width'  => '100%', //'150px',
  'value'  => $vars['sort'],
  'values' => $form_items['sort']];

// Third row
$form['row'][2]['sysDescr'] = [
  'type'        => 'text',
  'name'        => 'sysDescr',
  'value'       => $vars['sysDescr'],
  'width'       => '100%', //'180px',
  'placeholder' => TRUE];


$form['row'][2]['purpose'] = [
  'type'        => 'text',
  'name'        => 'Description / Purpose',
  'value'       => $vars['purpose'],
  'width'       => '100%', //'180px',
  'placeholder' => TRUE];

$form['row'][2]['sysContact'] = [
  'type'        => 'text',
  'name'        => 'sysContact',
  'value'       => $vars['sysContact'],
  'width'       => '100%', //'180px',
  'placeholder' => TRUE];

$form['row'][2]['distro'] = [
  'type'   => 'multiselect',
  'name'   => 'Select Distro',
  'width'  => '100%', //'180px',
  'value'  => $vars['distro'],
  'groups' => ['Listed', 'Other'],
  'values' => $form_items['distro']];

$form['row'][2]['serial'] = [
  'type'        => 'text',
  'name'        => 'Serial Number',
  'value'       => $vars['serial'],
  'width'       => '100%', //'180px',
  'placeholder' => TRUE];

$form['row'][2]['disabled'] = [
  'type'     => 'switch-ng',
  //'on-text'       => 'Disabled',
  'on-color' => 'primary',
  'on-icon'  => 'icon-eye-close',
  //'off-text'      => 'Enabled',
  'off-icon' => 'icon-eye-open',
  'grid'     => 1,
  //'size'          => 'large',
  //'height'        => '15px',
  'title'    => 'Show Enabled/Disabled',
  //'placeholder'   => 'Disabled',
  //'readonly'      => TRUE,
  //'disabled'      => TRUE,
  //'submit_by_key' => TRUE,
  'value'    => $vars['disabled']
];

// search button
$form['row'][2]['search'] = [
  'type'  => 'submit',
  //'name'        => 'Search',
  //'class' => 'btn-primary',
  //'icon'        => 'icon-search',
  'grid'  => 1,
  'right' => TRUE,
];

$panel_form = ['type'          => 'rows',
               'title'         => 'Search Devices',
               'space'         => '10px',
               'submit_by_key' => TRUE,
               'url'           => generate_url($vars)];

$panel_form['row'][0] = ['hostname' => $form['row'][0]['hostname'],
                         'sysname'  => $form['row'][1]['sysname']];

$panel_form['row'][1] = ['sysDescr' => $form['row'][2]['sysDescr'],
                         'purpose'  => $form['row'][2]['purpose']];

$panel_form['row'][2] = ['sysContact' => $form['row'][2]['sysContact'],
                         'serial'     => $form['row'][2]['serial']];

$panel_form['row'][3] = ['location'      => $form['row'][0]['location'],
                         'location_text' => $form['row'][1]['location_text']];


$panel_form['row'][4]['os']      = $form['row'][0]['os'];
$panel_form['row'][4]['version'] = $form['row'][1]['version'];
$panel_form['row'][4]['distro']  = $form['row'][2]['distro'];

$panel_form['row'][5]['hardware'] = $form['row'][0]['hardware'];
$panel_form['row'][5]['vendor']   = $form['row'][0]['vendor'];

$panel_form['row'][6]['type']     = $form['row'][1]['type'];
$panel_form['row'][6]['features'] = $form['row'][1]['features'];

$panel_form['row'][7]['group']            = $form['row'][0]['group'];
$panel_form['row'][7]['group']['grid']    = 4;
$panel_form['row'][7]['sort']             = $form['row'][1]['sort'];
$panel_form['row'][7]['sort']['grid']     = 4;
$panel_form['row'][7]['disabled']         = $form['row'][2]['disabled'];
$panel_form['row'][7]['disabled']['grid'] = 2;
$panel_form['row'][7]['search']           = $form['row'][2]['search'];
$panel_form['row'][7]['search']['grid']   = 2;

// Register custom panel
register_html_panel(generate_form($panel_form));


if ($vars['searchbar'] !== "hide") {
    echo '<div class="hidden-xl">';
    print_form($form);
    echo '</div>';
}
unset($form, $panel_form, $form_items);

// Build Devices navbar

$navbar = ['brand' => "Devices", 'class' => "navbar-narrow"];

$navbar['options']['basic']['text']  = 'Basic';
$navbar['options']['detail']['text'] = 'Details';
$navbar['options']['status']['text'] = 'Status';

if ($_SESSION['userlevel'] >= 7) {
    $navbar['options']['perf']['text'] = 'Polling Performance';
}
$navbar['options']['graphs']['text'] = 'Graphs';

foreach ($navbar['options'] as $option => $array) {
    //if (!isset($vars['format'])) { $vars['format'] = 'basic'; }
    if ($vars['format'] == $option) {
        $navbar['options'][$option]['class'] .= " active";
    }
    $navbar['options'][$option]['url'] = generate_url($vars, ['format' => $option]);
}

// Set graph period stuff
if ($vars['format'] === 'graphs') {

    if (isset($vars['timestamp_from']) && preg_match(OBS_PATTERN_TIMESTAMP, $vars['timestamp_from'])) {
        $vars['from'] = strtotime($vars['timestamp_from']);
        unset($vars['timestamp_from']);
    }
    if (isset($vars['timestamp_to']) && preg_match(OBS_PATTERN_TIMESTAMP, $vars['timestamp_to'])) {
        $vars['to'] = strtotime($vars['timestamp_to']);
        unset($vars['timestamp_to']);
    }

    if (!is_numeric($vars['from'])) {
        $vars['from'] = get_time('day');
    }
    if (!is_numeric($vars['to'])) {
        $vars['to'] = get_time();
    }
}

// Print options related to graphs.
//$menu_options = array('bits'      => 'Bits',
//                      'processor' => 'CPU',
//                      'mempool'   => 'Memory',
//                      'uptime'    => 'Uptime',
//                      'storage'   => 'Storage',
//                      'diskio'    => 'Disk I/O',
//                      'poller_perf' => 'Poll Time'
//                      );
foreach (['graphs'] as $type) {
    /// FIXME. Weird graph menu, they too long and not actual for all devices,
    /// but here also not possible use sql query from `device_graphs` because here not stored all graphs
    /// FIXME - We need to register all graphs in `device_graphs` :D

    $vars_graphs = $vars;
    unset($vars_graphs['graph']);
    $where_graphs = build_devices_where_array($vars_graphs);

    //$where_graphs = ' WHERE 1 ' . implode('', $where_graphs);

    $query = 'SELECT `graph` FROM `device_graphs` LEFT JOIN `devices` USING (`device_id`)' . $query_join_geocoding;
    $query .= generate_where_clause($where_graphs, $query_permitted) . ' AND `device_graphs`.`enabled` = 1 GROUP BY `graph`';

    $graph_types = dbFetchColumn($query);

    foreach ($graph_types as $option) {
        $data = $config['graph_types']['device'][$option];
        if (!isset($data['descr'])) {
            $data['descr'] = nicecase($option);
        }

        if ($vars['format'] == $type && $vars['graph'] == $option) {
            $navbar['options'][$type]['suboptions'][$option]['class'] = 'active';
            $navbar['options'][$type]['text']                         .= " (" . $data['descr'] . ')';
        }
        $navbar['options'][$type]['suboptions'][$option]['text'] = $data['descr'];
        $navbar['options'][$type]['suboptions'][$option]['url']  = generate_url($vars, ['view' => NULL, 'format' => $type, 'graph' => $option]);
    }
}

if (isset($vars['pagination']) && (!$vars['pagination'] || $vars['pagination'] == 'no')) {
    $navbar['options_right']['pagination'] = ['text' => 'Enable Pagination', 'url' => generate_url($vars, ['pagination' => NULL])];
} else {
    $navbar['options_right']['pagination'] = ['text' => 'Disable Pagination', 'url' => generate_url($vars, ['pagination' => '0', 'pageno' => NULL, 'pagesize' => NULL])];
}

if ($vars['searchbar'] == "hide") {
    $navbar['options_right']['searchbar'] = ['text' => 'Show Search', 'url' => generate_url($vars, ['searchbar' => NULL])];
} else {
    $navbar['options_right']['searchbar'] = ['text' => 'Hide Search', 'url' => generate_url($vars, ['searchbar' => 'hide'])];
}

if (get_var_true($vars['bare'])) {
    $navbar['options_right']['header'] = ['text' => 'Show Header', 'url' => generate_url($vars, ['bare' => NULL])];
} else {
    $navbar['options_right']['header'] = ['text' => 'Hide Header', 'url' => generate_url($vars, ['bare' => 'yes'])];
}

$navbar['options_right']['reset'] = ['text' => 'Reset', 'url' => generate_url(['page' => 'devices', 'section' => $vars['section']])];

print_navbar($navbar);
unset($navbar);

// Print period options for graphs

if ($vars['format'] === 'graphs') {
    $form = ['type'          => 'rows',
             'space'         => '5px',
             'submit_by_key' => TRUE]; //Do not use url here, because it discards all other vars from url

    // Datetime Field
    $form['row'][0]['timestamp'] = [
      'type'    => 'datetime',
      'grid'    => 10,
      'grid_xs' => 10,
      //'width'       => '70%',
      //'div_class'   => 'col-lg-10 col-md-10 col-sm-10 col-xs-10',
      'presets' => TRUE,
      'min'     => '2007-04-03 16:06:59',  // Hehe, who will guess what this date/time means? --mike
      // First commit! Though Observium was already 7 months old by that point. --adama
      'max'     => date('Y-m-d 23:59:59'), // Today
      'from'    => date('Y-m-d H:i:s', $vars['from']),
      'to'      => date('Y-m-d H:i:s', $vars['to'])];
    // Update button
    $form['row'][0]['update'] = [
      'type'    => 'submit',
      //'name'        => 'Search',
      //'icon'        => 'icon-search',
      //'div_class'   => 'col-lg-2 col-md-2 col-sm-2 col-xs-2',
      'grid'    => 2,
      'grid_xs' => 2,
      'right'   => TRUE];

    print_form($form);
    unset($form);
}

$sort = build_devices_sort($vars);

if (isset($vars['sort']) && $vars['sort'] === 'domain') {
    // Special Domain sort, require additional pseudo fields
    $query = "SELECT *,";
    $query .= " SUBSTRING_INDEX(SUBSTRING_INDEX(`hostname`,'.',-3),'.',1) AS `leftmost`,";
    $query .= " SUBSTRING_INDEX(SUBSTRING_INDEX(`hostname`,'.',-2),'.',1) AS `middle`,";
    $query .= " SUBSTRING_INDEX(`hostname`,'.',-1) AS `rightmost`";
    $query .= " FROM `devices` ";
} else {
    $query = "SELECT * FROM `devices` ";
}

if ($config['geocoding']['enable']) {
    $query .= " LEFT JOIN `devices_locations` USING (`device_id`)";
}
$query .= generate_where_clause($where_array, $query_permitted) . $sort;

// Pagination
$pagination_html = '';
if (isset($vars['pagination']) && (!$vars['pagination'] || $vars['pagination'] == 'no')) {
    // Skip if pagination set to false
} elseif ($count = dbFetchCell("SELECT COUNT(*) FROM `devices`" . $query_join_geocoding . generate_where_clause($where_array, $query_permitted))) {
    pagination($vars, 0, TRUE); // Get default pagesize/pageno
    $start           = $vars['pagesize'] * $vars['pageno'] - $vars['pagesize'];
    $query           .= ' LIMIT ' . $start . ',' . $vars['pagesize'];
    $pagination_html = pagination($vars, $count);
}

[$format, $subformat] = explode("_", $vars['format'], 2);

//r($query);

$devices = dbFetchRows($query);
//$devices = dbFetchRows($query, NULL, TRUE);

if (count($devices)) {
    $include_file = $config['html_dir'] . '/pages/devices/' . $format . '.inc.php';
    if (is_file($include_file)) {
        echo $pagination_html;
        if ($format !== 'graphs') {
            echo generate_box_open();
        }

        include($include_file);

        if ($vars['format'] !== 'graphs') {
            echo generate_box_close();
        }
        echo $pagination_html;
    } else {
        print_error("<h4>Error</h4>
                 This should not happen. Please ensure you are on the latest release and then report this to the Observium developers if it continues.");
    }
} else {
    print_error("<h4>No devices found</h4>
               Please try adjusting your search parameters.");
}


// EOF
