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

/**
 * Build devices where array
 *
 * This function returns an array of "WHERE" statements from a $vars array.
 * The returned array can be imploded and used on the devices table.
 * Originally extracted from the /devices/ page
 *
 * @param array $vars
 *
 * @return array
 */
function build_devices_where_array($vars) {
    $where_array = [];
    foreach ($vars as $var => $value) {
        if (!safe_empty($value)) {
            switch ($var) {
                case 'group':
                case 'group_id':
                    $values            = get_group_entities($value);
                    $where_array[$var] = generate_query_values($values, 'device_id');
                    break;
                case 'poller':
                    // Poller Name
                    if (!is_numeric($value) &&
                        $poller_id = dbFetchCell("SELECT `poller_id` FROM `pollers` WHERE `poller_name` = ?", [ $value ])) {
                        $value = $poller_id;
                    }
                    // No break here
                case 'poller_id':
                    $where_array[$var] = generate_query_values($value, 'poller_id');
                    break;
                case 'device':
                case 'device_id':
                    $where_array[$var] = generate_query_values($value, 'device_id');
                    break;
                case 'hostname':
                case 'sysname':
                case 'sysContact':
                case 'sysDescr':
                case 'serial':
                case 'purpose':
                    $where_array[$var] = generate_query_values($value, $var, '%LIKE%');
                    break;
                case 'location_text':
                    $where_array[$var] = generate_query_values($value, 'devices.location', '%LIKE%');
                    break;
                case 'location':
                    $where_array[$var] = generate_query_values($value, 'devices.location');
                    break;
                case 'location_lat':
                case 'location_lon':
                case 'location_country':
                case 'location_state':
                case 'location_county':
                case 'location_city':
                    if ($GLOBALS['config']['geocoding']['enable']) {
                        $where_array[$var] = generate_query_values($value, 'devices_locations.' . $var);
                    }
                    break;
                case 'os':
                case 'version':
                case 'hardware':
                case 'vendor':
                case 'features':
                case 'type':
                case 'status':
                case 'status_type':
                case 'distro':
                case 'ignore':
                case 'disabled':
                case 'snmpable':
                case 'snmp_community':
                case 'snmp_context':
                case 'snmp_transport':
                case 'snmp_port':
                case 'snmp_maxrep':
                case 'snmp_version':
                    $where_array[$var] = generate_query_values($value, $var);
                    break;
                case 'graph':
                    $where_array[$var] = generate_query_values(devices_with_graph($value), "devices.device_id");
            }
        }
    }

    return $where_array;
}

function devices_with_graph($graph) {

    $devices = [];

    $sql = "SELECT `device_id` FROM `device_graphs` WHERE `graph` = ? AND `enabled` = ?";
    foreach (dbFetchRows($sql, [ $graph, 1 ]) as $entry) {
        $devices[$entry['device_id']] = $entry['device_id'];
    }

    return $devices;

}

function build_devices_sort($vars)
{
    $order = '';
    switch ($vars['sort']) {
        case 'uptime':
        case 'location':
        case 'version':
        case 'features':
        case 'type':
        case 'os':
        case 'sysName':
        case 'device_id':
            $order = ' ORDER BY `devices`.`' . $vars['sort'] . '`';
            if ($vars['sort_order'] == "desc") {
                $order .= " DESC";
            }
            break;

        case 'domain':
            // Special order hostnames in Domain Order
            // SELECT `hostname`,
            //        SUBSTRING_INDEX(SUBSTRING_INDEX(`hostname`,'.',-3),'.',1) AS `leftmost`,
            //        SUBSTRING_INDEX(SUBSTRING_INDEX(`hostname`,'.',-2),'.',1) AS `middle`,
            //        SUBSTRING_INDEX(`hostname`,'.',-1) AS `rightmost`
            // FROM `devices` ORDER by `middle`, `rightmost`, `leftmost`;
            if ($vars['sort_order'] == "desc") {
                $order = ' ORDER BY `middle` DESC, `rightmost` DESC, `leftmost` DESC';
            } else {
                $order = ' ORDER BY `middle`, `rightmost`, `leftmost`';
            }
            break;

        case 'hostname':
        default:
            $order = ' ORDER BY `devices`.`hostname`';
            if ($vars['sort_order'] == "desc") {
                $order .= " DESC";
            }
            break;
    }
    return $order;
}

// DOCME needs phpdoc block
function print_device_header($device, $args = [])
{
    global $config;

    if (!is_array($device)) {
        print_error("Invalid device passed to print_device_header()!");
    }

    $div_class = 'box box-solid';
    if (!safe_empty($args['div-class'])) {
        $div_class .= " " . $args['div-class'];
    }

    echo '<div class="' . $div_class . '">
  <table class=" table table-hover table-condensed ' . $args['class'] . '" style="margin-bottom: 10px; min-height: 70px; border-radius: 2px;">';
    $location_url = escape_html($device['location']);
    if (device_permitted($device)) {
        $location_url = '<a href="' . generate_location_url($device['location']) . '">' . $location_url . '</a>';
    }
    echo '
              <tr class="' . $device['html_row_class'] . ' vertical-align">
               <td class="state-marker"></td>
               <td style="width: 70px; text-align: center;">' . get_device_icon($device) . '</td>
               <td><span style="font-size: 20px;">' . generate_device_link($device) . '</span>
               <br />' . $location_url . '</td>
               ';

    if (device_permitted($device) && !$args['no_graphs']) {

        echo '<td>';

        // Only show graphs for device_permitted(), don't show device graphs to users who can only see a single entity.

        if (isset($config['os'][$device['os']]['graphs'])) {
            $graphs = $config['os'][$device['os']]['graphs'];
        } elseif (isset($device['os_group'], $config['os'][$device['os_group']]['graphs'])) {
            $graphs = $config['os'][$device['os_group']]['graphs'];
        } else {
            // Default group
            $graphs = $config['os_group']['default']['graphs'];
        }

        $graph_array = [];
        //$graph_array['height'] = "100";
        //$graph_array['width']  = "310";
        $graph_array['to']     = get_time();
        $graph_array['device'] = $device['device_id'];
        $graph_array['type']   = "device_bits";
        $graph_array['from']   = get_time('day');
        $graph_array['legend'] = "no";

        $graph_array['height'] = "45";
        $graph_array['width']  = "150";
        $graph_array['style']  = ['width: 150px !important']; // Fix for FF issue on HiDPI screen
        $graph_array['bg']     = "FFFFFF00";

        // Preprocess device graphs array
        $graphs_enabled = [];
        foreach ($device['graphs'] as $graph) {
            $graphs_enabled[] = $graph['graph'];
        }

        foreach ($graphs as $entry) {
            if ($entry && in_array(str_replace('device_', '', $entry), $graphs_enabled, TRUE)) {
                $graph_array['type'] = $entry;

                if (preg_match(OBS_PATTERN_GRAPH_TYPE, $entry, $graphtype)) {
                    $type    = $graphtype['type'];
                    $subtype = $graphtype['subtype'];

                    $text = $config['graph_types'][$type][$subtype]['descr'];
                } else {
                    $text = nicecase($entry); // Fallback to the type itself as a string, should not happen!
                }

                echo '<div class="pull-right" style="padding: 2px; margin: 0;">';
                //echo generate_graph_tag($graph_array);
                echo generate_graph_popup($graph_array);
                echo '<div style="padding: 0px; font-weight: bold; font-size: 7pt; text-align: center;">' . $text . '</div>';
                echo '</div>';
            }
        }

        echo '    </td>';

    } // Only show graphs for device_permitted()

    echo('
   </tr>
 </table>
</div>');
}

function print_device_row($device, $vars = ['view' => 'basic'], $link_vars = [])
{
    global $config, $cache;

    if (!is_array($device)) {
        print_error("Invalid device passed to print_device_row()!");
    }

    if (!is_array($vars)) {
        $vars = ['view' => $vars];
    } // For compatibility

    humanize_device($device);

    $tags = [
      'html_row_class' => $device['html_row_class'],
      'device_id'      => $device['device_id'],
      'device_link'    => generate_device_link($device, NULL, $link_vars),
      'device_url'     => generate_device_url($device, $link_vars),
      'hardware'       => escape_html($device['hardware']),
      'features'       => escape_html($device['features']),
      'os_text'        => $device['os_text'],
      'version'        => escape_html($device['version']),
      //'sysName'       => escape_html($device['sysName']),
      'device_uptime'  => device_uptime($device, 'short'),
      'location'       => escape_html(truncate($device['location'], 40, ''))
    ];

    switch (strtolower($config['web_device_name'])) {
        case 'sysname':
        case 'purpose':
        case 'descr':
        case 'description':
            $tags['sysName'] = escape_html($device['hostname']);
            if (!safe_empty($device['sysName'])) {
                $tags['sysName'] .= ' / ' . escape_html($device['sysName']);
            }
            break;
        default:
            $tags['sysName'] = escape_html($device['sysName']);
    }

    switch ($vars['view']) {
        case 'detail':
        case 'details':
            $table_cols           = 7;
            $tags['device_image'] = get_device_icon($device);
            $tags['ports_count']  = dbFetchCell("SELECT COUNT(*) FROM `ports` WHERE `device_id` = ? AND `deleted` = ?", [$device['device_id'], 0]);
            //$tags['sensors_count'] = dbFetchCell("SELECT COUNT(*) FROM `sensors` WHERE `device_id` = ? AND `sensor_deleted` = ?", array($device['device_id'], 0));
            //$tags['sensors_count'] += dbFetchCell("SELECT COUNT(*) FROM `status` WHERE `device_id` = ? AND `status_deleted` = ?", array($device['device_id'], 0));
            $tags['sensors_count'] = $cache['sensors']['devices'][$device['device_id']]['count'];
            $tags['sensors_count'] += $cache['statuses']['devices'][$device['device_id']]['count'];
            $hostbox               = '
  <tr class="' . $tags['html_row_class'] . '" onclick="openLink(\'' . $tags['device_url'] . '\')" style="cursor: pointer;">
    <td class="state-marker"></td>
    <td class="text-center vertical-align" style="width: 64px; text-align: center;">' . $tags['device_image'] . '</td>
    <td style="width: 300px;"><span class="entity-title">' . $tags['device_link'] . '</span><br />' . $tags['location'] . '</td>
    <td class="text-nowrap" style="width: 55px;">';
            if ($tags['ports_count']) {
                $hostbox .= '<i class="' . $config['icon']['port'] . '"></i> <span class="label">' . $tags['ports_count'] . '</span>';
            }
            $hostbox .= '<br />';
            if ($tags['sensors_count']) {
                $hostbox      .= '<i class="' . $config['icon']['sensor'] . '"></i> ';
                $sensor_items = [];
                // Ok
                if ($event_count = $cache['sensors']['devices'][$device['device_id']]['ok'] + $cache['statuses']['devices'][$device['device_id']]['ok']) {
                    $sensor_items[] = ['event' => 'success', 'text' => $event_count];
                }
                // Warning
                if ($event_count = $cache['sensors']['devices'][$device['device_id']]['warning'] + $cache['statuses']['devices'][$device['device_id']]['warning']) {
                    $sensor_items[] = ['event' => 'warning', 'text' => $event_count];
                }
                // Alert
                if ($event_count = $cache['sensors']['devices'][$device['device_id']]['alert'] + $cache['statuses']['devices'][$device['device_id']]['alert']) {
                    $sensor_items[] = ['event' => 'danger', 'text' => $event_count];
                }
                // Ignored
                if ($event_count = $cache['sensors']['devices'][$device['device_id']]['ignored'] + $cache['statuses']['devices'][$device['device_id']]['ignored']) {
                    $sensor_items[] = ['event' => 'default', 'text' => $event_count];
                }
                $hostbox .= get_label_group($sensor_items);

                //'<span class="label">'.$tags['sensors_count'].'</span>';
            }
            $hostbox .= '</td>
    <td>' . $tags['os_text'] . ' ' . $tags['version'] . (!empty($tags['features']) ? ' (' . $tags['features'] . ')' : '') . '<br />
        ' . $tags['hardware'] . '</td>
    <td>' . $tags['device_uptime'] . '<br />' . $tags['sysName'] . '</td>
  </tr>';
            break;
        case 'perf':
            if ($_SESSION['userlevel'] >= 7) {
                $tags['device_image'] = get_device_icon($device);
                $graph_array          = [
                  'type'      => 'device_poller_perf',
                  'device'    => $device['device_id'],
                  'operation' => 'poll',
                  'legend'    => 'no',
                  'width'     => 600,
                  'height'    => 90,
                  'from'      => get_time('week'),
                  'to'        => get_time(),
                ];

                $hostbox = '
  <tr class="' . $tags['html_row_class'] . '" onclick="openLink(\'' . generate_device_url($device, ['tab' => 'perf']) . '\')" style="cursor: pointer;">
    <td class="state-marker"></td>
    <td class="vertical-align" style="width: 64px; text-align: center;">' . $tags['device_image'] . '</td>
    <td class="vertical-align" style="width: 300px;"><span class="entity-title">' . $tags['device_link'] . '</span><br />' . $tags['location'] . '</td>
    <td><div class="pull-right" style="height: 130px; padding: 2px; margin: 0;">' . generate_graph_tag($graph_array) . '</div></td>
  </tr>';
            }
            break;
        case 'status':
            $tags['device_image'] = get_device_icon($device);

            // Graphs
            $graph_array           = [];
            $graph_array['height'] = "100";
            $graph_array['width']  = "310";
            $graph_array['to']     = get_time();
            $graph_array['device'] = $device['device_id'];
            $graph_array['type']   = "device_bits";
            $graph_array['from']   = get_time('day');
            $graph_array['legend'] = "no";
            $graph_array['height'] = "45";
            $graph_array['width']  = "175";
            $graph_array['bg']     = "FFFFFF00";

            if (isset($config['os'][$device['os']]['graphs'])) {
                $graphs = $config['os'][$device['os']]['graphs'];
            } elseif (isset($device['os_group'], $config['os'][$device['os_group']]['graphs'])) {
                $graphs = $config['os'][$device['os_group']]['graphs'];
            } else {
                // Default group
                $graphs = $config['os_group']['default']['graphs'];
            }

            // Preprocess device graphs array
            $graphs_enabled = [];
            foreach ($device['graphs'] as $graph) {
                $graphs_enabled[] = $graph['graph'];
            }

            foreach ($graphs as $entry) {
                [, $graph_subtype] = explode("_", $entry, 2);

                if ($entry && in_array(str_replace("device_", "", $entry), $graphs_enabled)) {
                    $graph_array['type'] = $entry;
                    if (isset($config['graph_types']['device'][$graph_subtype])) {
                        $title = $config['graph_types']['device'][$graph_subtype]['descr'];
                    } else {
                        $title = nicecase(str_replace("_", " ", $graph_subtype));
                    }
                    $tags['graphs'][] = '<div class="pull-right" style="margin: 5px; margin-bottom: 0px;">' . generate_graph_popup($graph_array) . '<br /><div style="text-align: center; padding: 0px; font-size: 7pt; font-weight: bold;">' . $title . '</div></div>';
                }
            }

            $hostbox = '
  <tr class="' . $tags['html_row_class'] . '" onclick="openLink(\'' . $tags['device_url'] . '\')" style="cursor: pointer;">
    <td class="state-marker"></td>
    <td class="vertical-align" style="width: 64px; text-align: center;">' . $tags['device_image'] . '</td>
    <td style="width: 300px;"><span class="entity-title">' . $tags['device_link'] . '</span><br />' . $tags['location'] . '</td>
    <td>';
            if ($tags['graphs']) {
                $hostbox .= implode($tags['graphs']);
            }
            $hostbox .= '</td>
  </tr>';
            break;
        default: // basic
            $table_cols            = 6;
            $tags['device_image']  = get_device_icon($device);
            $tags['ports_count']   = dbFetchCell("SELECT COUNT(*) FROM `ports` WHERE `device_id` = ? AND `deleted` = 0;", [$device['device_id']]);
            $tags['sensors_count'] = dbFetchCell("SELECT COUNT(*) FROM `sensors` WHERE `device_id` = ?;", [$device['device_id']]);
            $tags['sensors_count'] += dbFetchCell("SELECT COUNT(*) FROM `status` WHERE `device_id` = ?;", [$device['device_id']]);
            $hostbox               = '
  <tr class="' . $tags['html_row_class'] . '" onclick="openLink(\'' . $tags['device_url'] . '\')" style="cursor: pointer;">
    <td class="state-marker"></td>
    <td class="vertical-align" style="width: 64px; text-align: center;">' . $tags['device_image'] . '</td>
    <td style="width: 300;"><span class="entity-title">' . $tags['device_link'] . '</span><br />' . $tags['location'] . '</td>
    <td>' . $tags['hardware'] . ' ' . $tags['features'] . '</td>
    <td>' . $tags['os_text'] . ' ' . $tags['version'] . '</td>
    <td>' . $tags['device_uptime'] . '</td>
  </tr>';
    }


    // If we're showing graphs, generate the graph

    if ($vars['graph']) {
        $hostbox .= '<tr><td colspan="' . $table_cols . '">';

        $graph_array['to']     = get_time();
        $graph_array['device'] = $device['device_id'];
        $graph_array['type']   = 'device_' . $vars['graph'];

        $hostbox .= generate_graph_row($graph_array);

        $hostbox .= '</td></tr>';

    }

    echo($hostbox);
}

/**
 * Returns icon tag (by default) or icon name for a current device array
 *
 * @param array $device    Array with device info (from DB)
 * @param bool  $base_icon Return complete img tag with icon (by default) or just base icon name
 * @param bool  $dark      Prefer dark variant of icon (also set by session var)
 *
 * @return string Img tag with icon or base icon name
 */
function get_device_icon($device, $base_icon = FALSE, $dark = FALSE) {
    global $config;

    $icon = 'generic';
    $ext  = 'png'; // currently fallback default

    if (device_permitted($device)) {
        $device['os'] = strtolower($device['os']);
        $model        = $config['os'][$device['os']]['model'];

        if (!safe_empty($device['icon']) && $ext = is_file_ext($config['html_dir'] . '/images/os/' . $device['icon'])) {
            // Custom device icon from DB
            $icon = $device['icon'];
        } elseif ($model && isset($config['model'][$model][$device['sysObjectID']]['icon']) &&
                  $ext = is_file_ext($config['html_dir'] . '/images/os/' . $config['model'][$model][$device['sysObjectID']]['icon'])) {
            // Per model icon
            $icon = $config['model'][$model][$device['sysObjectID']]['icon'];
        } elseif (isset($config['os'][$device['os']]['icon']) &&
                  $ext = is_file_ext($config['html_dir'] . '/images/os/' . $config['os'][$device['os']]['icon'])) {
            // Icon defined in os definition
            $icon = $config['os'][$device['os']]['icon'];
        } else {
            if ($device['distro']) {
                // Icon by distro name
                // Red Hat Enterprise -> redhat
                $distro = strtolower(trim(str_replace([ ' Enterprise', 'Red Hat' ], [ '', 'redhat' ], $device['distro'])));
                $distro = safename($distro);
                if ($ext = is_file_ext($config['html_dir'] . '/images/os/' . $distro)) {
                    $icon = $distro;
                }
            }

            if ($icon === 'generic' && $ext = is_file_ext($config['html_dir'] . '/images/os/' . $device['os'])) {
                // Icon by OS name
                $icon = $device['os'];
            }
        }

        // Icon by vendor name
        if ($icon === 'generic' && ($config['os'][$device['os']]['vendor'] || $device['vendor'])) {
            if ($device['vendor']) {
                $vendor = $device['vendor'];
            } else {
                $vendor = rewrite_vendor($config['os'][$device['os']]['vendor']); // Compatibility, if a device not polled for long time
            }

            $vendor_safe = safename(strtolower($vendor));
            if (isset($config['vendors'][$vendor_safe]['icon'])) {
                $icon = $config['vendors'][$vendor_safe]['icon'];
                $ext = is_file_ext($config['html_dir'] . '/images/os/' . $icon);
            } elseif ($ext = is_file_ext($config['html_dir'] . '/images/os/' . $vendor_safe)) {
                $icon = $vendor_safe;
            } elseif (isset($config['os'][$device['os']]['icons'])) {
                // Fallback to os alternative icon
                $icon = array_values($config['os'][$device['os']]['icons'])[0];
                $ext = is_file_ext($config['html_dir'] . '/images/os/' . $icon);
            }
        }
    } else {
        print_warning('NOT permitted');
    }

    // Set dark mode by session
    if (isset($_SESSION['theme'])) {
        $dark = str_contains($_SESSION['theme'], 'dark');
    }

    // Prefer dark variant of icon in dark mode
    if ($dark && is_file_ext($config['html_dir'] . '/images/os/' . $icon . '-dark', $ext)) {
        $icon .= '-dark';
    }

    if ($base_icon) {
        // return base name for os icon
        return $icon;
    }

    // return image html tag
    $base_url = rtrim($config['base_url'], '/');
    if ($ext === 'svg') {
        return '<img src="' . $base_url . '/images/os/' . $icon . '.svg" style="max-height: 32px; max-width: 48px;" alt="" />';
    }

    // PNG images
    $srcset   = '';
    // Now we always have 2x icon variant!
    //if (is_file($config['html_dir'] . '/images/os/' . $icon . '_2x.png')) // HiDPI image exist?
    //{
    // Detect allowed screen ratio for current browser
    $ua_info = detect_browser();

    if ($ua_info['screen_ratio'] > 1) {
        $srcset = ' srcset="' . $base_url . '/images/os/' . $icon . '_2x.png' . ' 2x"';
    }
    //}

    // Image tag -- FIXME re-engineer this code to do this properly. This is messy.
    return '<img src="' . $base_url . '/images/os/' . $icon . '.png"' . $srcset . ' alt="" />';
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function generate_device_url($device, $vars = [])
{
    return generate_url(['page' => 'device', 'device' => $device['device_id']], $vars);
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function generate_device_popup_header($device, $vars = [])
{

    humanize_device($device);

    $device_name = device_name($device);
    if ($device['hostname'] !== $device_name) {
        $sysName = $device['hostname'];
        if (!safe_empty($device['sysName'])) {
            $sysName .= ' / ' . $device['sysName'];
        }
    } else {
        $sysName = $device['sysName'];
    }

    $string = generate_box_open() . '
<table class="table table-striped table-rounded table-condensed">
  <tr class="' . $device['html_row_class'] . '" style="font-size: 10pt;">
    <td class="state-marker"></td>
    <td class="vertical-align" style="width: 64px; text-align: center;">' . get_device_icon($device) . '</td>' . PHP_EOL;
    if (device_permitted($device)) {
        $string .= '    <td width="200px"><a href="' . generate_device_url($device) . '" class="' . device_link_class($device) . '" style="font-size: 15px; font-weight: bold;">' .
                   escape_html(device_name($device)) . '</a><br />' . escape_html(truncate($device['location'], 64, '')) . '</td>
    <td>' . $device['os_text'] . ' ' . escape_html($device['version']) . ' <br /> ' .
                   ($device['vendor'] ? escape_html($device['vendor']) . ' ' : '') . escape_html($device['hardware']) . '</td>
    <td>' . device_uptime($device, 'short') . '<br />' . escape_html($sysName) . '</td>' . PHP_EOL;
    } else {
        $string .= '<td width="90%"><span style="font-size: 20px;">' . generate_device_link($device) . '</span>
               <br />' . escape_html(truncate($device['location'], 64, '')) . '</td>';
    }
    $string .= '  </tr>
</table>
' . generate_box_close();

    return $string;
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function generate_device_popup($device, $vars = [])
{
    global $config;

    $content = generate_device_popup_header($device, $vars);

    if (isset($config['os'][$device['os']]['graphs'])) {
        $graphs = $config['os'][$device['os']]['graphs'];
    } elseif (isset($device['os_group'], $config['os'][$device['os_group']]['graphs'])) {
        $graphs = $config['os'][$device['os_group']]['graphs'];
    } else {
        // Default group
        $graphs = $config['os_group']['default']['graphs'];
    }

    // Preprocess device graphs array
    $graphs_enabled = [];
    foreach ($device['graphs'] as $graph) {
        if ($graph['enabled'] != '0') {
            $graphs_enabled[] = $graph['graph'];
        }
    }

    $count = 0;
    foreach ($graphs as $entry) {

        if ($count == 3) {
            break;
        }

        if ($entry && in_array(str_replace('device_', '', $entry), $graphs_enabled, TRUE)) {
            // No text provided for the minigraph, fetch from array
            if (preg_match(OBS_PATTERN_GRAPH_TYPE, $entry, $graphtype)) {
                $type    = $graphtype['type'];
                $subtype = $graphtype['subtype'];

                $text = $config['graph_types'][$type][$subtype]['descr'];
            } else {
                $text = nicecase($entry); // Fallback to the type itself as a string, should not happen!
            }

            // FIXME -- function!

            $graph_array           = [];
            $graph_array['height'] = "100";
            $graph_array['width']  = "290";
            $graph_array['to']     = get_time();
            $graph_array['device'] = $device['device_id'];
            $graph_array['type']   = $entry;
            $graph_array['from']   = get_time('day');
            $graph_array['legend'] = "no";

            $content             .= '<div style="width: 730px; white-space: nowrap;">';
            $content             .= "<div class=entity-title><h4>" . $text . "</h4></div>";
            $content             .= generate_graph_tag($graph_array);
            $graph_array['from'] = get_time('week');
            $content             .= generate_graph_tag($graph_array);
            $content             .= '</div>';

            $count++;

        }
    }

    //r($content);
    return $content;
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function generate_device_link($device, $text = NULL, $vars = [], $escape = TRUE, $short = FALSE)
{

    if (is_array($device) && !($device['hostname'] && isset($device['status']))) {
        // partial device array, get full
        $device = device_by_id_cache($device['device_id']);
    } elseif (is_numeric($device)) {
        $device = device_by_id_cache($device);
    }

    if (!$device) {
        return escape_html($text);
    }
    if (!device_permitted($device['device_id'])) {
        $text = device_name($device, $short);
        return $escape ? escape_html($text) : $text;
    }

    $class = device_link_class($device);

    if (safe_empty($text)) {
        $text = device_name($device, $short);
    }

    $url = generate_device_url($device, $vars);

    if ($escape) {
        $text = escape_html($text);
    }

    return '<a href="' . $url . '" class="entity-popup ' . $class . ' text-nowrap" data-eid="' . $device['device_id'] . '" data-etype="device">' . $text . '</a>';
}

// Simple wrapper to generate_device_link() for common usage with only device_name
function generate_device_link_short($device, $vars = [], $short = TRUE)
{
    // defaults - always short device name, escaped
    return generate_device_link($device, NULL, $vars, TRUE, $short);
}

/**
 * Generate device form values based on the given form filter, column, and options.
 * generate_form_values('device')
 *
 * The $form_filter can be an array of device IDs to filter the form values. If false
 * or not an array, no filtering will be applied.
 *
 * @param bool|array $form_filter Array of device IDs to filter by, or false for no filtering.
 * @param string $column The column to use in the device form values (default: 'device_id').
 * @param array $options Options to customize the device form values.
 *                       Available: filter_mode (include|exclude), show_disabled (bool), show_down (bool), show_icon (bool), subtext (string)
 *
 * @return array The generated device form values.
 */
function generate_device_form_values($form_filter = FALSE, $column = 'device_id', $options = [])
{
    global $cache;

    //r($form_filter);
    //r($column);
    //r($options);
    if (!is_array($form_filter)) {
        $options['filter_mode'] = FALSE;
    }

    $form_items = [];
    foreach ($cache['devices']['hostname'] as $hostname => $device_id) {
        // Filter items based on filter_mode
        if ($options['filter_mode'] === 'include') {
            if (!in_array($device_id, $form_filter)) {
                //r($device_id);
                continue;
            }
        } elseif ($options['filter_mode'] === 'exclude') {
            if (in_array($device_id, $form_filter)) {
                continue;
            }
        }

        if (in_array($device_id, $cache['devices']['disabled'])) {
            if (isset($options['show_disabled'])) {
                // Force display disabled devices
                if (!$options['show_disabled']) {
                    continue;
                }
            } elseif (!$GLOBALS['config']['web_show_disabled'] && in_array($device_id, $cache['devices']['disabled'])) {
                continue;
            }

            $form_items[$device_id]['group'] = 'DISABLED';
            $form_items[$device_id]['class'] = 'bg-disabled';

        } elseif (in_array($device_id, $cache['devices']['down'])) {
            if (isset($options['show_down']) && !$options['show_down']) {
                continue;
            } // Skip down
            $form_items[$device_id]['group'] = 'DOWN';
            $form_items[$device_id]['class'] = 'bg-danger';
        } else {
            if (isset($options['up']) && in_array($device_id, $cache['devices']['up'])) {
                continue;
            } // Skip up
            $form_items[$device_id]['group'] = 'UP';
            $form_items[$device_id]['class'] = 'bg-info';
        }
        if ($GLOBALS['config']['web_device_name'] && $GLOBALS['config']['web_device_name'] !== 'hostname') {
            $device = device_by_id_cache($device_id);
            $form_items[$device_id]['name'] = device_name($device);
            if (!isset($options['subtext']) && $form_items[$device_id]['name'] !== $hostname) {
                $form_items[$device_id]['subtext'] = $hostname;
            }
        } else {
            $form_items[$device_id]['name'] = $hostname;
        }

        if (isset($options['subtext'])) {
            $device = $device ?? device_by_id_cache($device_id);
            $form_items[$device_id]['subtext'] = array_tag_replace($device, $options['subtext']);
        }
        if (isset($options['show_icon']) && $options['show_icon']) {
            $device = $device ?? device_by_id_cache($device_id);
            $form_items[$device_id]['icon'] = $GLOBALS['config']['devicetypes'][$device['type']]['icon'] ?? $GLOBALS['config']['entities']['device']['icon'];
        }
        unset($device);
    }

    return $form_items;
}

function print_device_permission_box($mode, $perms, $params = []) {
    global $config;
    
    echo generate_box_open([ 'header-border' => TRUE, 'title' => 'Device Permissions' ]);

    $perms_devices = !safe_empty($perms['device']);
    if ($perms_devices) {
        echo('<table class="' . OBS_CLASS_TABLE . '">' . PHP_EOL);

        foreach ($perms['device'] as $device_id => $status) {
            $device = device_by_id_cache($device_id);

            echo('<tr><td style="width: 1px;"></td>
                <td style="overflow: hidden;">' . get_icon($config['devicetypes'][$device['type']]['icon'] ?? $config['entities']['device']['icon']) . generate_device_link($device) . '
                <small>' . $device['location'] . '</small></td>
                <td width="25">');

            $form = [];
            $form['type'] = 'simple';

            $action_del = $mode === 'role' ? 'role_entity_del' : 'user_perm_del';
            // Elements
            $form['row'][0]['auth_secret'] = [
                'type'  => 'hidden',
                'value' => $_SESSION['auth_secret']
            ];
            $form['row'][0]['entity_id'] = [
                'type'  => 'hidden',
                'value' => $device['device_id']
            ];
            $form['row'][0]['entity_type'] = [
                'type'  => 'hidden',
                'value' => 'device'
            ];
            $form['row'][0]['action'] = [
                'type'  => 'hidden',
                'value' => $action_del
            ];
            $form['row'][0]['submit'] = [
                'type'  => 'submit',
                'name'  => ' ',
                'class' => 'btn-danger btn-mini',
                'icon'  => 'icon-trash',
                'value' => $action_del
            ];
            print_form($form);
            unset($form);

            echo('</td>
              </tr>');
        }
        echo('</table>' . PHP_EOL);

    } else {
        echo('<p class="text-center text-warning bg-warning" style="padding: 10px; margin: 0px;"><strong>This '.$mode.' currently has no permitted devices</strong></p>');
    }

    // Devices
    $permissions_list = array_keys((array)$perms['device']);

    // Display devices this user doesn't have Permissions to
    $form = [];
    $form['type'] = 'simple';
    $form['style'] = 'padding: 7px; margin: 0px;';

    // Elements
    $form['row'][0]['auth_secret'] = [
        'type'  => 'hidden',
        'value' => $_SESSION['auth_secret']
    ];
    if ($mode === 'role') {
        $action_add = 'role_entity_add';
        $form['row'][0]['role_id'] = [
            'type'  => 'hidden',
            'value' => $params['role_id']
        ];
    } else {
        $action_add = 'user_perm_add';
        $form['row'][0]['user_id'] = [
            'type'  => 'hidden',
            'value' => $params['user_id']
        ];
    }
    $form['row'][0]['entity_type'] = [
        'type'  => 'hidden',
        'value' => 'device'
    ];
    $form['row'][0]['action'] = [
        'type'  => 'hidden',
        'value' => $action_add
    ];

    $form_items['devices'] = generate_form_values('device', $permissions_list, 'device_id',
                                                  [ 'filter_mode' => 'exclude', 'subtext' => '%location%', 'show_disabled' => TRUE, 'show_icon' => TRUE ]);
    $form['row'][0]['entity_id'] = [
        'type'   => 'multiselect',
        'name'   => 'Permit Device',
        'width'  => '250px',
        //'value'    => $vars['entity_id'],
        'groups'    => ['', 'UP', 'DOWN', 'DISABLED'], // This is optgroup order for values (if required)
        'values' => $form_items['devices']
    ];
    // add button
    $form['row'][0]['Submit'] = [
        'type'  => 'submit',
        'name'  => 'Add',
        'icon'  => $config['icon']['plus'],
        'right' => TRUE,
        'value' => 'Add'
    ];
    print_form($form);

    echo generate_box_close();
}

function get_device_graphs_sections($device) {
    global $config, $cache;

    if (!isset($cache['graphs_sections'][$device['device_id']])) {

        $graphs_sections = [];

        if (OBSERVIUM_EDITION !== 'community' && dbExist('oids_entries', '`device_id` = ?', [ $device['device_id'] ])) {
            // Custom OIDs
            $device['graphs']['custom'] = [ 'device_id' => $device['device_id'], 'graph' => 'custom', 'enabled' => 1 ];
        }

        foreach ($device['graphs'] as $entry) {
            if (isset($entry['enabled']) && !$entry['enabled']) {
                // Skip disabled graphs
                continue;
            }

            $section = $config['graph_types']['device'][$entry['graph']]['section'] ?? $entry['graph'];

            if (in_array($section, $config['graph_sections'])) {
                // Collect only enabled and exists graphs
                //$graphs_sections[$section][$entry['graph']] = $entry['enabled'];
                if (isset($config['graph_types']['device'][$entry['graph']]['order']) && is_numeric($config['graph_types']['device'][$entry['graph']]['order'])) {
                    $order = $config['graph_types']['device'][$entry['graph']]['order'];
                } else {
                    $order = 999; // Set high order for unordered graphs
                }
                while (isset($graphs_sections[$section][$order])) {
                    $order++;
                }
                $graphs_sections[$section][$order] = $entry['graph'];
            }
        }

        // Set sections order
        $graphs_sections_sorted = [];
        foreach ($config['graph_sections'] as $section) {
            if (isset($graphs_sections[$section])) {
                $graphs_sections_sorted[$section] = $graphs_sections[$section];
                unset($graphs_sections[$section]);
            }
        }
        //r($graphs_sections_sorted);
        //r($graphs_sections);

        $cache['graphs_sections'][$device['device_id']] = array_merge($graphs_sections_sorted, $graphs_sections);
    }

    return $cache['graphs_sections'][$device['device_id']];
}

function navbar_health_menu($device, $vars = []) {
    global $config, $cache;

    if (!isset($cache['health_exist'][$device['device_id']])) {
        $cache['health_exist'][$device['device_id']] = [
            'storage'    => dbExist('storage',    '`device_id` = ?', [ $device['device_id'] ]),
            'diskio'     => dbExist('ucd_diskio', '`device_id` = ?', [ $device['device_id'] ]),
            'mempools'   => dbExist('mempools',   '`device_id` = ?', [ $device['device_id'] ]),
            'processors' => dbExist('processors', '`device_id` = ?', [ $device['device_id'] ]),
            'sensors'    => dbExist('sensors',    '`device_id` = ? AND `sensor_deleted` = 0', [ $device['device_id'] ]),
            'status'     => dbExist('status',     '`device_id` = ? AND `status_deleted` = 0', [ $device['device_id'] ]),
            'counter'    => dbExist('counters',   '`device_id` = ? AND `counter_deleted` = 0', [ $device['device_id'] ]),
        ];

        if ($cache['health_exist'][$device['device_id']]['sensors']) {
            // Keep sensors order for base types static
            $sensors_order = [ 'temperature', 'humidity', 'fanspeed', 'airflow', 'current',
                               'voltage', 'power', 'apower', 'rpower', 'frequency' ];
            $other_types  = array_diff(array_keys($config['sensor_types']), $sensors_order);
            $sensors_order = array_merge($sensors_order, $other_types);
            //r($sensors_order);

            $sensors_classes = dbFetchColumn("SELECT DISTINCT `sensor_class` FROM `sensors` WHERE `device_id` = ? AND `sensor_deleted` = ?", [ $device['device_id'], 0 ]);
            $cache['health_exist'][$device['device_id']]['sensors_classes'] = array_intersect($sensors_order, $sensors_classes);
        }

        /* All counters in single page?
        if ($cache['health_exist'][$device['device_id']]['counter']) {
            $cache['health_exist'][$device['device_id']]['counter_classes'] = dbFetchRows("SELECT DISTINCT `counter_class` FROM `counters` WHERE `device_id` = ? AND `counter_deleted` = ?", [ $device['device_id'], 0 ]);
            foreach ($counters_classes as $counter) {
                $datas[$counter['counter_class']] = [ 'icon' => $config['counter_types'][$counter['counter_class']]['icon'] ];
            }
        }
        */
        //r($cache['health_exist'][$device['device_id']]);
    }
    $health_exist = $cache['health_exist'][$device['device_id']];

    $datas = [];
    if ($health_exist['processors']) {
        $datas['processor'] = [ 'icon' => $config['entities']['processor']['icon'] ];
    }
    if ($health_exist['mempools']) {
        $datas['mempool'] = [ 'icon' => $config['entities']['mempool']['icon'] ];
    }
    if ($health_exist['storage']) {
        $datas['storage'] = [ 'icon' => $config['entities']['storage']['icon'] ];
    }
    if ($health_exist['diskio']) {
        $datas['diskio'] = [ 'icon' => $config['icon']['diskio'] ];
    }

    if ($health_exist['status']) {
        $datas['status'] = [ 'icon' => $config['entities']['status']['icon'] ];
    }

    if ($health_exist['sensors_classes']) {
        foreach ($health_exist['sensors_classes'] as $sensor_class) {
            //if ($sensor['sensor_class'] == 'counter') { continue; } // DEVEL
            $datas[$sensor_class] = [ 'icon' => $config['sensor_types'][$sensor_class]['icon'] ];
        }
    }

    if ($health_exist['counter']) {
        $datas['counter'] = [ 'icon' => $config['entities']['counter']['icon'] ];
    }

    $menu = [];
    $navbar_count = count($datas);
    foreach ($datas as $type => $options) {
        if ($vars['metric'] == $type) {
            $menu[$type]['class'] = "active";
        } elseif ($navbar_count > 8) {
            $menu[$type]['class'] = "icon";
        } // Show only icons if too many items in navbar
        if (isset($options['icon'])) {
            $menu[$type]['icon'] = $options['icon'];
        }
        $menu[$type]['url']  = generate_url([ 'page' => 'device', 'device' => $device['device_id'], 'tab' => 'health', 'metric' => $type ]);
        $menu[$type]['text'] = nicecase($type);
    }

    return $menu;
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function device_permitted($device_id)
{
    global $permissions;

    // If we've been passed an entity with device_id, just use that.
    if (is_array($device_id) && isset($device_id['device_id'])) {
        $device_id = $device_id['device_id'];
    }

    // If we still don't have a numeric device_id, return false because someone messed up.
    if (!is_numeric($device_id)) {
        return $_SESSION['userlevel'] >= 5; // in case when passed a pseudo device (like in OSes page)
    }

    // Level >5 can see everything.
    if ($_SESSION['userlevel'] >= 5) {
        $allowed = TRUE;
    } elseif (isset($permissions['device'][$device_id])) {
        $allowed = TRUE;
    } else {
        $allowed = FALSE;
    }
    return $allowed;
}

// TESTME needs unit testing
// DOCME needs phpdoc block
function device_link_class($device)
{
    if (isset($device['status']) && $device['status'] == '0') {
        $class = "red";
    } else {
        $class = "";
    }
    if ((isset($device['ignore']) && $device['ignore'] == '1')
        || (!is_null($device['ignore_until']) && strtotime($device['ignore_until']) > get_time())) {
        $class = "grey";
        if (isset($device['status']) && $device['status'] == '1') {
            $class = "green";
        }
    }
    if (isset($device['disabled']) && $device['disabled'] == '1') {
        $class = "grey";
    }

    return $class;
}

/**
 * Returns TRUE if the device is marked as ignored in the cache.
 *
 * @param $device_id
 *
 * @return bool
 */
function device_is_ignored($device_id)
{
    return isset($GLOBALS['cache']['devices']['ignored']) && in_array($device_id, $GLOBALS['cache']['devices']['ignored'], TRUE);
}

// EOF
