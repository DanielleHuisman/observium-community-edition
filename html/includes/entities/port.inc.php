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

function build_ports_where_array_ng($vars)
{
    return remove_and_from_array(build_ports_where_array($vars));
}

/**
 * Build ports WHERE array
 *
 * This function returns an array of "WHERE" statements from a $vars array.
 * The returned array can be imploded and used on the ports table.
 * Originally extracted from the /ports/ page
 *
 * @param array $vars
 *
 * @return array
 */
function build_ports_where_array($vars)
{
    $where = [];

    foreach ($vars as $var => $value) {
        if (!safe_empty($value)) {
            switch ($var) {
                case 'location':
                    $where[] = generate_query_values_and($value, $var);
                    break;
                case 'device_id':
                    $where[] = generate_query_values_and($value, 'ports.device_id');
                    break;
                case 'group':
                case 'group_id':
                    $values  = get_group_entities($value);
                    $where[] = generate_query_values_and($values, 'ports.port_id');
                    break;
                case 'device_group_id':
                case 'device_group':
                    $values  = get_group_entities($value, 'device');
                    $where[] = generate_query_values_and($values, 'ports.device_id');
                    break;
                case 'disable':
                    $var = 'disabled';
                case 'disabled':    // FIXME. 'disabled' column never used in ports..
                case 'deleted':
                case 'ignore':
                case 'ifSpeed':
                case 'ifType':
                case 'ifVlan':
                case 'port_id':
                    $where[] = generate_query_values_and($value, 'ports.' . $var);
                    break;
                case 'hostname':
                case 'ifAlias':
                case 'ifDescr':
                    $where[] = generate_query_values_and($value, $var, '%LIKE%');
                    break;
                case 'label':
                case 'port_label':
                    $where[] = generate_query_values_and($value, 'port_label', '%LIKE%');
                    break;
                case 'mac':
                case 'ifPhysAddress':
                    $value   = str_replace(['.', '-', ':'], '', $value);
                    $where[] = generate_query_values_and($value, 'ifPhysAddress', '%LIKE%');
                    break;
                case 'port_descr_type':
                    $where[] = generate_query_values_and($value, $var, 'LIKE');
                    break;
                case 'errors':
                    if (get_var_true($value)) {
                        $where[] = " AND (`ifInErrors_delta` > '0' OR `ifOutErrors_delta` > '0')";
                    }
                    break;
                case 'alerted':
                    if (get_var_true($value)) {
                        // this is just state=down
                        //$where[] = ' AND `ifAdminStatus` = "up" AND (`ifOperStatus` = "lowerLayerDown" OR `ifOperStatus` = "down")';
                        $var   = 'state';
                        $value = 'down';
                    }
                // do not break here
                case 'state':
                    // Allowed multiple states as array
                    $state_where = [];
                    foreach ((array)$value as $state) {
                        if ($state === "down") {
                            $state_where[] = '`ifAdminStatus` = "up" AND `ifOperStatus` IN ("lowerLayerDown", "down")';
                            //$state_where[] = generate_query_values_ng('up', 'ifAdminStatus') . generate_query_values_and(['down', 'lowerLayerDown'], 'ifOperStatus');
                        } elseif ($state === "up") {
                            $state_where[] = '`ifAdminStatus` = "up" AND `ifOperStatus` IN ("up", "testing", "monitoring")';
                            //$state_where[] = generate_query_values_ng('up', 'ifAdminStatus') . generate_query_values_and(['up', 'testing', 'monitoring'], 'ifOperStatus');
                        } elseif ($state === "admindown" || $state === "shutdown") {
                            $state_where[] = '`ifAdminStatus` = "down"';
                            //$state_where[] = generate_query_values_ng('down', 'ifAdminStatus');
                        }
                    }
                    switch (count($state_where)) {
                        case 0:
                            // incorrect state passed, ignore
                            break;
                        case 1:
                            $where[] = ' AND ' . $state_where[0];
                            break;
                        default:
                            $where[] = ' AND ((' . implode(') OR (', $state_where) . '))';
                    }
                    break;
                case 'cbqos':
                    if ($value && $value !== 'no') {
                        $where[] = generate_query_values_and($GLOBALS['cache']['ports']['cbqos'], 'ports.port_id');
                    }
                    break;
                case 'mac_accounting':
                    if ($value && $value !== 'no') {
                        $where[] = generate_query_values_and($GLOBALS['cache']['ports']['mac_accounting'], 'ports.port_id');
                    }
                    break;
            }
        }
    }

    return $where;
}

/**
 * Returns a string containing an HTML table to be used in popups for the port entity type
 *
 * @param array $port array
 *
 * @return string Table containing port header for popups
 */
function generate_port_popup_header($port)
{
    // Push through processing function to set attributes
    humanize_port($port);

    $contents = generate_box_open();
    $contents .= '<table class="' . OBS_CLASS_TABLE . '">
     <tr class="' . $port['row_class'] . '" style="font-size: 10pt;">
       <td class="state-marker"></td>
       <td style="width: 10px;"></td>
       <td style="width: 250px;"><a href="' . generate_entity_url('port', $port) . '" class="' . $port['html_class'] .
                 '" style="font-size: 15px; font-weight: bold;">' . escape_html($port['port_label']) . '</a><br />' .
                 escape_html($port['ifAlias']) . '</td>
       <td style="width: 100px;">' . $port['human_speed'] . '<br />' . $port['ifMtu'] . '</td>
       <td>' . $port['human_type'] . '<br />' . $port['human_mac'] . '</td>
     </tr>
     </table>';
    $contents .= generate_box_close();

    return $contents;
}

/**
 * Returns a string containing an HTML to be used as a port popups
 *
 * @param array  $port array
 * @param string $text to be used as port label
 * @param string $type graph type to be used in graphs (bits, nupkts, etc)
 *
 * @return string HTML port popup contents
 */
function generate_port_popup($port, $text = NULL, $type = NULL)
{
    $time = $GLOBALS['config']['time'];

    if (!isset($port['os'])) {
        $port = array_merge($port, device_by_id_cache($port['device_id']));
    }

    humanize_port($port);

    if (!$text) {
        $text = escape_html($port['port_label']);
    }
    if ($type) {
        $port['graph_type'] = $type;
    }
    if (!isset($port['graph_type'])) {
        $port['graph_type'] = 'port_bits';
    }

    if (!isset($port['os'])) {
        $port = array_merge($port, device_by_id_cache($port['device_id']));
    }

    $content = generate_device_popup_header($port);
    $content .= generate_port_popup_header($port);

    if ($type != "none") {
        $content .= '<div style="width: 700px">';
        //$content .= generate_box_open(array('body-style' => 'width: 700px;'));
        $graph_array['type']   = $port['graph_type'];
        $graph_array['legend'] = "yes";
        $graph_array['height'] = "100";
        $graph_array['width']  = "275";
        $graph_array['to']     = $time['now'];
        $graph_array['from']   = $time['day'];
        $graph_array['id']     = $port['port_id'];
        $content               .= generate_graph_tag($graph_array);
        $graph_array['from']   = $time['week'];
        $content               .= generate_graph_tag($graph_array);
        $graph_array['from']   = $time['month'];
        $content               .= generate_graph_tag($graph_array);
        $graph_array['from']   = $time['year'];
        $content               .= generate_graph_tag($graph_array);
        $content               .= "</div>";
        //$content .= generate_box_close();
    }

    return $content;
}

/**
 * Returns an HTML port page link with mouse-over popup to permitted users or a text label to non-permitted users
 *
 * @param array  $port array
 * @param string $text text to be used as port label
 * @param string $type graph type to be used in graphs (bits, nupkts, etc)
 *
 * @return string HTML link or text string
 */
function generate_port_link($port, $text = NULL, $type = NULL, $escape = FALSE, $short = FALSE)
{
    humanize_port($port);

    // Fixme -- does this function even need alternative $text? I think not. It's a hangover from before label.
    // Sometime in $text included html
    if (empty($text)) {
        $text   = $short ? $port['port_label_short'] : $port['port_label'];
        $escape = TRUE; // FORCE label escaping
    }

    if (port_permitted($port['port_id'], $port['device_id'])) {
        $url = generate_port_url($port);
        if ($escape) {
            $text = escape_html($text);
        }

        return '<a href="' . $url . '" class="entity-popup ' . $port['html_class'] . '" data-eid="' . $port['port_id'] . '" data-etype="port">' . $text . '</a>';
    } else {
        return escape_html($text);
    }
}

// Just simplify function call, instead generate_port_link($port, NULL, NULL, TRUE, TRUE)
function generate_port_link_short($port, $text = NULL, $type = NULL, $escape = FALSE, $short = TRUE)
{
    return generate_port_link($port, $text, $type, $escape, $short);
}

/**
 * Returns a string containing a page URL built from a $port array and an array of optional variables
 *
 * @param array $port array
 * @param array optional variables used when building the URL
 *
 * @return string port page URL
 */
function generate_port_url($port, $vars = [])
{
    return generate_url(['page' => 'device', 'device' => $port['device_id'], 'tab' => 'port', 'port' => $port['port_id']], $vars);
}

/**
 * Returns or echos a port graph thumbnail
 *
 * @param array   $args of arguments used to build the graph image tag URL
 * @param boolean $echo variable defining wether output should be returned or echoed
 *
 * @return string HTML port popup contents
 */
function generate_port_thumbnail($args, $echo = TRUE)
{
    if (!$args['bg']) {
        $args['bg'] = "FFFFFF";
    }

    $graph_array           = [];
    $graph_array['from']   = $args['from'];
    $graph_array['to']     = $args['to'];
    $graph_array['id']     = $args['port_id'];
    $graph_array['type']   = $args['graph_type'];
    $graph_array['width']  = $args['width'];
    $graph_array['height'] = $args['height'];
    $graph_array['bg']     = 'FFFFFF00';

    $mini_graph = generate_graph_tag($graph_array);

    $img = generate_port_link($args, $mini_graph);

    if ($echo) {
        echo($img);
    } else {
        return $img;
    }
}

function print_port_row($port, $vars = [])
{
    echo generate_port_row($port, $vars);
}

function generate_port_row($port, $vars = [])
{
    global $config, $cache;

    $device = device_by_id_cache($port['device_id']);

    humanize_port($port);

    if (!isset($vars['view'])) {
        $vars['view'] = "basic";
    }

    // Populate $port_adsl if the port has ADSL-MIB data
    if (!isset($cache['ports_option']['ports_adsl']) || in_array($port['port_id'], $cache['ports_option']['ports_adsl'])) {
        $port_adsl = dbFetchRow("SELECT * FROM `ports_adsl` WHERE `port_id` = ?", [$port['port_id']]);
    }

    // Populate $port['tags'] with various tags to identify port statuses and features
    // Port Errors
    if ($port['ifInErrors_delta'] > 0 || $port['ifOutErrors_delta'] > 0) {
        $port['tags'] .= generate_port_link($port, '<span class="label label-important">Errors</span>', 'port_errors');
    }

    // Port Deleted
    if ($port['deleted']) {
        $port['tags'] .= '<a href="' . generate_url(['page' => 'deleted-ports']) . '"><span class="label label-important">Deleted</span></a>';
    }
    if ($port['ignore']) {
        $port['tags'] .= '<span class="label label-suppressed">Ignored</span>';
    }
    if ($port['disabled']) {
        $port['tags'] .= '<span class="label label-disabled">Poll disabled</span>';
    }

    // Port IPv6
    if (isset($port['attribs']['ipv6-octets'])) {
        $port['tags'] .= '<span class="label label-primary">IPv6</span>';
    }

    // Port CBQoS
    if (isset($cache['ports_option']['ports_cbqos'])) {
        if (in_array($port['port_id'], $cache['ports_option']['ports_cbqos'])) {
            $port['tags'] .= '<a href="' . generate_port_url($port, ['view' => 'cbqos']) . '"><span class="label label-info">CBQoS</span></a>';
        }
    } elseif (dbExist('ports_cbqos', '`port_id` = ?', [$port['port_id']])) {
        $port['tags'] .= '<a href="' . generate_port_url($port, ['view' => 'cbqos']) . '"><span class="label label-info">CBQoS</span></a>';
    }

    // Port MAC Accounting
    if (isset($cache['ports_option']['mac_accounting'])) {
        if (in_array($port['port_id'], $cache['ports_option']['mac_accounting'])) {
            $port['tags'] .= '<a href="' . generate_port_url($port, ['view' => 'macaccounting']) . '"><span class="label label-info">MAC</span></a>';
        }
    } elseif (dbExist('mac_accounting', '`port_id` = ?', [$port['port_id']])) {
        $port['tags'] .= '<a href="' . generate_port_url($port, ['view' => 'macaccounting']) . '"><span class="label label-info">MAC</span></a>';
    }

    // Populated formatted versions of port rates.
    $port['bps_in']  = format_bps($port['ifInOctets_rate'] * 8);
    $port['bps_out'] = format_bps($port['ifOutOctets_rate'] * 8);

    $port['pps_in']  = format_si($port['ifInUcastPkts_rate']) . "pps";
    $port['pps_out'] = format_si($port['ifOutUcastPkts_rate']) . "pps";

    $string = '';

    if ($vars['view'] === "basic" || $vars['view'] === "graphs") {
        // Print basic view table row
        $table_cols = '8';

        $string .= '<tr class="' . $port['row_class'] . '">
            <td class="state-marker"></td>
            <td style="width: 1px;"></td>';

        if ($vars['page'] !== "device" && !get_var_true($vars['popup'])) {
            // Print device name link if we're not inside the device page hierarchy.

            $table_cols++; // Increment table columns by one to make sure graph line draws correctly

            $string .= '    <td style="width: 200px;"><span class="entity">' . generate_device_link_short($device, [], 20) . '</span><br />
                <span class="em">' . escape_html(truncate($port['location'], 32, "")) . '</span></td>';
        }


        $string .=
          '    <td><span class="entity">' . generate_port_link($port) . '</span> <span class="pull-right">' . $port['tags'] . '</span><br />' .
          '        <span class="em">' . escape_html(truncate($port['ifAlias'], 50, '')) . '</span></td>' .

          '<td style="width: 110px;">' . get_icon('arrow-down', NULL, ['style' => $port['bps_in_style']]) . ' <span class="small" style="' . $port['bps_in_style'] . '">' . format_bps($port['in_rate']) . '</span><br />' .
          get_icon('arrow-up', NULL, ['style' => $port['bps_out_style']]) . ' <span class="small" style="' . $port['bps_out_style'] . '">' . format_bps($port['out_rate']) . '</span><br /></td>' .

          '<td style="width: 90px;">' . get_icon('arrow-down', NULL, ['style' => $port['bps_in_style']]) . ' <span class="small" style="' . $port['bps_in_style'] . '">' . $port['ifInOctets_perc'] . '%</span><br />' .
          get_icon('arrow-up', NULL, ['style' => $port['bps_out_style']]) . ' <span class="small" style="' . $port['bps_out_style'] . '">' . $port['ifOutOctets_perc'] . '%</span><br /></td>' .

          '<td style="width: 110px;">' . get_icon('arrow-down', NULL, ['style' => $port['pps_in_style']]) . ' <span class="small" style="' . $port['pps_in_style'] . '">' . format_bi($port['ifInUcastPkts_rate']) . 'pps</span><br />' .
          get_icon('arrow-up', NULL, ['style' => $port['pps_out_style']]) . ' <span class="small" style="' . $port['pps_out_style'] . '">' . format_bi($port['ifOutUcastPkts_rate']) . 'pps</span></td>' .

          '<td style="width: 110px;"><small>' . $port['human_speed'] . '<br />' . $port['ifMtu'] . '</small></td>
            <td ><small>' . $port['human_type'] . '<br />' . $port['human_mac'] . '</small></td>
          </tr>';
    } elseif ($vars['view'] === "details" || $vars['view'] === "detail") {
        // Print detailed view table row

        $table_cols = '9';

        $string .= '<tr class="' . $port['row_class'] . '"';
        if ($vars['tab'] !== "port") {
            $string .= ' onclick="openLink(\'' . generate_port_url($port) . '\')" style="cursor: pointer;"';
        }
        $string .= '>';
        $string .= '         <td class="state-marker"></td>
         <td style="width: 1px;"></td>';

        if ($vars['page'] !== "device" && !get_var_true($vars['popup'])) {
            // Print device name link if we're not inside the device page hierarchy.

            $table_cols++; // Increment table columns by one to make sure graph line draws correctly

            $string .= '    <td width="200"><span class="entity">' . generate_device_link_short($device, [], 20) . '</span><br />
                <span class="em">' . escape_html(truncate($port['location'], 32, "")) . '</span></td>';
        }

        $string .= '
         <td style="min-width: 250px;">';

        $string .= '        <span class="entity-title">
              ' . generate_port_link($port) . '</span> <span class="pull-right">' . $port['tags'] . '
           </span><br /><span class="small">' . escape_html($port['ifAlias']) . '</span>';

        if ($port['ifAlias']) {
            $string .= '<br />';
        }

        unset($break);

        $ignore_type = $GLOBALS['config']['ip-address']['ignore_type'];
        if (!isset($cache['ports_option']['ipv4_addresses']) || in_array($port['port_id'], $cache['ports_option']['ipv4_addresses'])) {
            $sql = "SELECT * FROM `ipv4_addresses` WHERE `port_id` = ?";
            // Do not exclude IPv4 link-local
            $sql .= generate_query_values_and(array_diff($ignore_type, ['link-local']), 'ipv4_type', '!='); // Do not show ignored ip types
            foreach (dbFetchRows($sql, [$port['port_id']]) as $ip) {
                $string .= $break . generate_popup_link('ip', $ip['ipv4_address'] . '/' . $ip['ipv4_prefixlen'], NULL, 'small');
                $break  = "<br />";
            }
        }
        if (!isset($cache['ports_option']['ipv6_addresses']) || in_array($port['port_id'], $cache['ports_option']['ipv6_addresses'])) {
            $sql = "SELECT * FROM `ipv6_addresses` WHERE `port_id` = ?";
            $sql .= generate_query_values_and($ignore_type, 'ipv6_type', '!='); // Do not show ignored ip types
            foreach (dbFetchRows($sql, [$port['port_id']]) as $ip6) {
                $string .= $break . generate_popup_link('ip', $ip6['ipv6_address'] . '/' . $ip6['ipv6_prefixlen'], NULL, 'small');
                $break  = "<br />";
            }
        }

        //$string .= '</span>';

        $string .= '</td>';

        // Print port graph thumbnails
        $string             .= '<td style="width: 147px;">';
        $port['graph_type'] = "port_bits";

        $graph_array           = [];
        $graph_array['to']     = $config['time']['now'];
        $graph_array['id']     = $port['port_id'];
        $graph_array['type']   = $port['graph_type'];
        $graph_array['width']  = 100;
        $graph_array['height'] = 20;
        $graph_array['bg']     = 'ffffff00';
        $graph_array['from']   = $config['time']['day'];

        $string .= generate_port_link($port, generate_graph_tag($graph_array));

        $port['graph_type']  = "port_upkts";
        $graph_array['type'] = $port['graph_type'];
        $string              .= generate_port_link($port, generate_graph_tag($graph_array));

        $port['graph_type']  = "port_errors";
        $graph_array['type'] = $port['graph_type'];
        $string              .= generate_port_link($port, generate_graph_tag($graph_array));

        $string .= '</td>';

        $string .= '<td style="width: 100px; white-space: nowrap;">';

        if ($port['ifOperStatus'] === "up" || $port['ifOperStatus'] === "monitoring") {
            // Colours generated by humanize_port
            $string .= '<i class="icon-circle-arrow-down" style="' . $port['bps_in_style'] . '"></i> <span class="small" style="' . $port['bps_in_style'] . '">' . format_bps($port['in_rate']) . '</span><br />
      <i class="icon-circle-arrow-up"   style="' . $port['bps_out_style'] . '"></i> <span class="small" style="' . $port['bps_out_style'] . '">' . format_bps($port['out_rate']) . '</span><br />
      <i class="icon-circle-arrow-down" style="' . $port['pps_in_style'] . '"></i> <span class="small" style="' . $port['pps_in_style'] . '">' . format_bi($port['ifInUcastPkts_rate']) . 'pps</span><br />
      <i class="icon-circle-arrow-up"   style="' . $port['pps_out_style'] . '"></i> <span class="small" style="' . $port['pps_out_style'] . '">' . format_bi($port['ifOutUcastPkts_rate']) . 'pps</span>';
        }

        $string .= '</td><td style="width: 110px;">';
        if ($port['ifType'] && $port['ifType'] != "") {
            $string .= '<span class="small">' . $port['human_type'] . '</span>';
        } else {
            $string .= '-';
        }
        $string .= '<br />';
        if ($port['ifSpeed']) {
            $string .= '<span class="small">' . humanspeed($port['ifSpeed']) . '</span>';
        }
        if ($port['ifDuplex'] && $port['ifDuplex'] !== "unknown") {
            $string .= '<span class="small"> (' . str_replace("Duplex", "", $port['ifDuplex']) . ')</span>';
        }
        $string .= '<br />';
        if ($port['ifMtu'] && $port['ifMtu'] != "") {
            $string .= '<span class="small">MTU ' . $port['ifMtu'] . '</span>';
        } else {
            $string .= '<span class="small">Unknown MTU</span>';
        }

        //$string .= '<br />';

        // Set VLAN data if the port has ifTrunk populated
        if (strlen($port['ifTrunk']) &&
            !in_array($port['ifTrunk'], ['access', 'routed'])) { // Skip on routed (or access)
            if ($port['ifVlan']) {
                // Native VLAN
                if (!isset($cache['ports_vlan'])) {
                    $native_state = dbFetchCell('SELECT `state` FROM `ports_vlans` WHERE `device_id` = ? AND `port_id` = ?', [$device['device_id'], $port['port_id']]);
                    $native_name  = dbFetchCell('SELECT `vlan_name` FROM `vlans` WHERE `device_id` = ? AND `vlan_vlan` = ?', [$device['device_id'], $port['ifVlan']]);
                } else {
                    $native_state = $cache['ports_vlan'][$port['port_id']][$port['ifVlan']]['state'];
                    $native_name  = $cache['ports_vlan'][$port['port_id']][$port['ifVlan']]['vlan_name'];
                }
                switch ($native_state) {
                    case 'blocking':
                        $class = 'text-danger';
                        break;
                    case 'forwarding':
                        $class = 'text-success';
                        break;
                    default:
                        $class = 'muted';
                }
                if (empty($native_name)) {
                    $native_name = 'VLAN' . str_pad($port['ifVlan'], 4, '0', STR_PAD_LEFT);
                }
                $native_tooltip = 'NATIVE: <strong class=' . $class . '>' . $port['ifVlan'] . ' [' . $native_name . ']</strong><br />';
            }

            if (!isset($cache['ports_vlan'])) {
                $vlans = dbFetchRows('SELECT * FROM `ports_vlans` AS PV
                         LEFT JOIN vlans AS V ON PV.`vlan` = V.`vlan_vlan` AND PV.`device_id` = V.`device_id`
                         WHERE PV.`port_id` = ? AND PV.`device_id` = ? ORDER BY PV.`vlan`;', [$port['port_id'], $device['device_id']]);
            } else {
                $vlans = $cache['ports_vlan'][$port['port_id']];
            }
            $vlans_count = safe_count($vlans);
            $rel         = ($vlans_count || $native_tooltip) ? 'tooltip' : ''; // Hide tooltip for empty
            $string      .= '<p class="small"><a class="label label-info" data-rel="' . $rel . '" data-tooltip="<div class=\'small\' style=\'max-width: 320px; text-align: justify;\'>' . $native_tooltip;
            if ($vlans_count) {
                $string     .= 'ALLOWED: ';
                $vlans_aggr = [];
                foreach ($vlans as $vlan) {
                    if ($vlans_count > 20) {
                        // Aggregate VLANs
                        $vlans_aggr[] = $vlan['vlan'];
                    } else {
                        // List VLANs
                        switch ($vlan['state']) {
                            case 'blocking':
                                $class = 'text-danger';
                                break;
                            case 'forwarding':
                                $class = 'text-success';
                                break;
                            default:
                                $class = 'muted';
                        }
                        if (empty($vlan['vlan_name'])) {
                            $vlan['vlan_name'] = 'VLAN' . str_pad($vlan['vlan'], 4, '0', STR_PAD_LEFT);
                        }
                        $string .= '<strong class=' . $class . '>' . $vlan['vlan'] . ' [' . $vlan['vlan_name'] . ']</strong><br />';
                    }
                }
                if ($vlans_count > 20) {
                    // End aggregate VLANs
                    $string .= '<strong>' . range_to_list($vlans_aggr, ', ') . '</strong>';
                }
            }
            $string .= '</div>">' . $port['ifTrunk'] . '</a></p>';
        } elseif ($port['ifVlan']) {
            if (!isset($cache['ports_vlan'])) {
                $native_state = dbFetchCell('SELECT `state` FROM `ports_vlans` WHERE `device_id` = ? AND `port_id` = ?', [$device['device_id'], $port['port_id']]);
                $native_name  = dbFetchCell('SELECT `vlan_name` FROM `vlans` WHERE `device_id` = ? AND `vlan_vlan` = ?', [$device['device_id'], $port['ifVlan']]);
            } else {
                $native_state = $cache['ports_vlan'][$port['port_id']][$port['ifVlan']]['state'];
                $native_name  = $cache['ports_vlan'][$port['port_id']][$port['ifVlan']]['vlan_name'];
            }
            switch ($native_state) {
                case 'blocking':
                    $class = 'label-error';
                    break;
                case 'forwarding':
                    $class = 'label-success';
                    break;
                default:
                    $class = '';
            }
            $rel       = ($native_name) ? 'tooltip' : ''; // Hide tooltip for empty
            $vlan_name = ($port['ifTrunk'] !== 'access') ? nicecase($port['ifTrunk']) . ' ' : '';
            $vlan_name .= 'VLAN ' . $port['ifVlan'];
            $string    .= '<br /><span data-rel="' . $rel . '" class="label ' . $class . '"  data-tooltip="<strong class=\'small\'>' . $port['ifVlan'] . ' [' . $native_name . ']</strong>">' . $vlan_name . '</span>';
        } elseif ($port['ifVrf']) { // Print the VRF name if the port is assigned to a VRF
            $vrf_name = dbFetchCell("SELECT `vrf_name` FROM `vrfs` WHERE `vrf_id` = ?", [$port['ifVrf']]);
            $string   .= '<br /><span class="label label-success" data-rel="tooltip" data-tooltip="VRF">' . $vrf_name . '</span>';
        }

        $string .= '</td>';

        // If the port is ADSL, print ADSL port data.
        if ($port_adsl['adslLineCoding']) {
            $string .= '<td style="width: 200px;"><span class="small">';
            $string .= '<span class="label">' . $port_adsl['adslLineCoding'] . '</span> <span class="label">' . rewrite_adslLineType($port_adsl['adslLineType']) . '</span>';
            $string .= '<br />';
            $string .= 'SYN <i class="icon-circle-arrow-down green"></i> ' . format_bps($port_adsl['adslAtucChanCurrTxRate']) . ' <i class="icon-circle-arrow-up blue"></i> ' . format_bps($port_adsl['adslAturChanCurrTxRate']);
            $string .= '<br />';
            //$string .= 'Max:'.formatRates($port_adsl['adslAtucCurrAttainableRate']) . '/'. formatRates($port_adsl['adslAturCurrAttainableRate']);
            //$string .= '<br />';
            $string .= 'ATN <i class="icon-circle-arrow-down green"></i> ' . $port_adsl['adslAtucCurrAtn'] . 'dBm <i class="icon-circle-arrow-up blue"></i> ' . $port_adsl['adslAturCurrAtn'] . 'dBm';
            $string .= '<br />';
            $string .= 'SNR <i class="icon-circle-arrow-down green"></i> ' . $port_adsl['adslAtucCurrSnrMgn'] . 'dB <i class="icon-circle-arrow-up blue"></i> ' . $port_adsl['adslAturCurrSnrMgn'] . 'dB';
            $string .= '</span>';
        } else {
            // Otherwise print normal port data
            $string .= '<td style="width: 150px;"><span class="small">';
            if (!safe_empty($port['ifPhysAddress'])) {
                $string .= $port['human_mac'];
            } else {
                $string .= '-';
            }
            $string .= '<br />' . $port['ifLastChange'] . '</span>';
        }

        $string .= '</td>';
        $string .= '<td style="min-width: 200px" class="small">';


        if ($port['ifType'] !== 'softwareLoopback' && !str_contains($port['port_label'], 'oopback') &&
            safe_empty($graph_type)) {
            $br = '';

            // Populate links array for ports with direct links
            if (!isset($cache['ports_option']['neighbours']) || in_array($port['port_id'], $cache['ports_option']['neighbours'])) {
                foreach (dbFetchRows('SELECT * FROM `neighbours` WHERE `port_id` = ? AND `active` = ?', [$port['port_id'], 1]) as $neighbour) {
                    // print_r($neighbour);
                    if ($neighbour['remote_port_id']) {
                        // Do not show some "non-physical" interfaces links,
                        // see: https://jira.observium.org/browse/OBS-2979
                        $remote_port = get_port_by_id_cache($neighbour['remote_port_id']);
                        if (!in_array($remote_port['ifType'], ['propVirtual', 'ieee8023adLag'])) {
                            $int_links[$neighbour['remote_port_id']]      = $neighbour['remote_port_id'];
                            $int_links_phys[$neighbour['remote_port_id']] = $neighbour['protocol'];
                        }
                    } else {
                        $int_links_unknown[] = $neighbour;
                    }
                }
            } // else {  }

            // Populate links array for devices which share an IPv4 subnet
            if (!isset($cache['ports_option']['ipv4_addresses']) || in_array($port['port_id'], $cache['ports_option']['ipv4_addresses'])) {
                $ignore_type   = $GLOBALS['config']['ip-address']['ignore_type'];
                $ignore_type[] = 'loopback'; // Always ignore loopback on links

                $sql = 'SELECT DISTINCT(`ipv4_network_id`), `ipv4_network` FROM `ipv4_addresses` ' .
                       'LEFT JOIN `ipv4_networks` USING(`ipv4_network_id`) ' .
                       'WHERE `port_id` = ?';
                $sql .= generate_query_values_and($ignore_type, 'ipv4_type', '!=');
                if (!safe_empty($config['ignore_common_subnet'])) {
                    // If it's still exist (non default list)
                    $sql .= generate_query_values_and($config['ignore_common_subnet'], 'ipv4_network', '!=');
                }
                foreach (dbFetchRows($sql, [ $port['port_id'] ]) as $network) {
                    //r($network);
                    $link_sql = 'SELECT `device_id`, `port_id` FROM `ipv4_addresses` ' .
                                'LEFT JOIN `ports` USING (`device_id`, `port_id`) ' .
                                'WHERE `device_id` != ? AND `ipv4_network_id` = ? AND `ifAdminStatus` = "up"';
                    $params = [ $device['device_id'], $network['ipv4_network_id'] ];

                    foreach (dbFetchRows($link_sql, $params) as $new) {
                        //r($new);
                        if (!$config['web_show_disabled'] &&
                            in_array($new['device_id'], $cache['devices']['disabled'])) {
                            continue;
                        }

                        $int_links[$new['port_id']]      = $new['port_id'];
                        $int_links_v4[$new['port_id']][] = $network['ipv4_network'];
                    }
                }
            }

            // Populate links array for devices which share an IPv6 subnet
            if (!isset($cache['ports_option']['ipv6_addresses']) || in_array($port['port_id'], $cache['ports_option']['ipv6_addresses'])) {
                $ignore_type   = $GLOBALS['config']['ip-address']['ignore_type'];
                $ignore_type[] = 'loopback'; // Always ignore loopback on links

                $sql = 'SELECT DISTINCT(`ipv6_network_id`), `ipv6_network` FROM `ipv6_addresses` ' .
                       'LEFT JOIN `ipv6_networks` USING(`ipv6_network_id`) ' .
                       'WHERE `port_id` = ?';
                $sql .= generate_query_values_and($ignore_type, 'ipv6_type', '!=');
                if (!safe_empty($config['ignore_common_subnet'])) {
                    // If it's still exist (non default list)
                    $sql .= generate_query_values_and($config['ignore_common_subnet'], 'ipv6_network', '!=');
                }
                foreach (dbFetchRows($sql, [$port['port_id']]) as $network) {
                    //r($network);
                    $link_sql = 'SELECT `device_id`, `port_id` FROM `ipv6_addresses` ' .
                                'LEFT JOIN `ports` USING (`device_id`, `port_id`) ' .
                                'WHERE `device_id` != ? AND `ipv6_network_id` = ? AND `ifAdminStatus` = "up"';
                    $link_sql .= generate_query_values_and([ 'linklayer', 'wellknown' ], 'ipv6_origin', '!=');
                    $params = [ $device['device_id'], $network['ipv6_network_id'] ];

                    foreach (dbFetchRows($link_sql, $params) as $new) {
                        //r($new);
                        if (!$config['web_show_disabled'] &&
                            in_array($new['device_id'], $cache['devices']['disabled'])) {
                            continue;
                        }

                        $int_links[$new['port_id']]      = $new['port_id'];
                        $int_links_v6[$new['port_id']][] = $network['ipv6_network'];
                    }
                }
            }

            // Output contents of links array
            foreach ($int_links as $int_link) {
                //r($int_link);
                //r($int_links_phys);
                $link_if = get_port_by_id_cache($int_link);
                if (!device_permitted($link_if['device_id'])) {
                    continue;
                } // Skip not permitted links

                $link_dev = device_by_id_cache($link_if['device_id']);
                $string   .= $br;

                if ($int_links_phys[$int_link]) {
                    $string .= generate_tooltip_link(NULL, NULL, 'Directly connected', $config['icon']['connected']) . ' ';
                } else {
                    $string .= generate_tooltip_link(NULL, NULL, 'Same subnet', $config['icon']['network']) . ' ';
                }

                // for port_label_short - generate_port_link($link_if, NULL,  NULL, TRUE, TRUE)
                $string .= '<b>' . generate_port_link_short($link_if) . ' on ' . generate_device_link_short($link_dev) . '</b>';

                if (isset($int_links_phys[$int_link]) && !is_numeric($int_links_phys[$int_link])) {
                    $string .= '&nbsp;<span class="label">' . nicecase($int_links_phys[$int_link]) . '</span>';
                }
                ## FIXME -- do something fancy here.

                if ($int_links_v6[$int_link]) {
                    $string .= '&nbsp;' . overlib_link('', '<span class="label label-success">IPv6</span>', implode("<br />", $int_links_v6[$int_link]), NULL);
                }
                if ($int_links_v4[$int_link]) {
                    $string .= '&nbsp;' . overlib_link('', '<span class="label label-info">IPv4</span>', implode("<br />", $int_links_v4[$int_link]), NULL);
                }
                $br = "<br />";
            }

            // Output content of unknown links array (where ports don't exist in our database, or they weren't matched)

            foreach ($int_links_unknown as $int_link) {
                //r($int_link);
                $string .= generate_tooltip_link(NULL, NULL, 'Directly connected', $config['icon']['connected']) . ' ';
                $string .= '<b><i>' . short_ifname($int_link['remote_port']) . '</i></b> on ';

                $text = '<div class="small" style="max-width: 500px;">';
                if (!safe_empty($int_link['remote_platform'])) {
                    $text .= '<strong>' . $int_link['remote_platform'] . '</strong><br />';
                }
                $text   .= $int_link['remote_version'] . '</div>';
                $string .= '<i><b>' . generate_tooltip_link(NULL, $int_link['remote_hostname'], $text) . '</b></i>';
                $string .= '&nbsp;<span class="label">' . nicecase($int_link['protocol']) . '</span><br />';
            }
        }

        if (!isset($cache['ports_option']['pseudowires']) || in_array($port['port_id'], $cache['ports_option']['pseudowires'])) {
            foreach (dbFetchRows("SELECT * FROM `pseudowires` WHERE `port_id` = ?", [$port['port_id']]) as $pseudowire) {
                //`port_id`,`peer_device_id`,`peer_ldp_id`,`pwID`,`pwIndex`
                #    $pw_peer_dev = dbFetchRow("SELECT * FROM `devices` WHERE `device_id` = ?", array($pseudowire['peer_device_id']));
                $pw_peer_int = dbFetchRow("SELECT * FROM `ports` AS I, `pseudowires` AS P WHERE I.`device_id` = ? AND P.`pwID` = ? AND P.`port_id` = I.`port_id`", [$pseudowire['peer_device_id'], $pseudowire['pwID']]);

                #    $pw_peer_int = get_port_by_id_cache($pseudowire['peer_device_id']);
                $pw_peer_dev = device_by_id_cache($pseudowire['peer_device_id']);

                if (is_array($pw_peer_int)) {
                    humanize_port($pw_peer_int);
                    $string .= $br . '<i class="' . $config['icon']['cross-connect'] . '"></i> <strong>' . generate_port_link_short($pw_peer_int) . ' on ' . generate_device_link_short($pw_peer_dev) . '</strong>';
                } else {
                    $string .= $br . '<i class="' . $config['icon']['cross-connect'] . '"></i> <strong> VC ' . $pseudowire['pwID'] . ' on ' . $pseudowire['peer_addr'] . '</strong>';
                }
                $string .= ' <span class="label">' . $pseudowire['pwPsnType'] . '</span>';
                $string .= ' <span class="label">' . $pseudowire['pwType'] . '</span>';
                $br     = "<br />";
            }
        }


        /** Disabled pending database rejigging to add it back
         *
         * if (!isset($cache['ports_option']['ports_pagp']) || in_array($port['ifIndex'], $cache['ports_option']['ports_pagp']))
         * {
         * foreach (dbFetchRows("SELECT * FROM `ports` WHERE `pagpGroupIfIndex` = ? AND `device_id` = ?", array($port['ifIndex'], $device['device_id'])) as $member)
         * {
         * humanize_port($member);
         * $pagp[$device['device_id']][$port['ifIndex']][$member['ifIndex']] = TRUE;
         * $string .= $br.'<i class="'.$config['icon']['merge'].'"></i> <strong>' . generate_port_link($member) . ' [PAgP]</strong>';
         * $br = "<br />";
         * }
         * }
         *
         * if ($port['pagpGroupIfIndex'] && $port['pagpGroupIfIndex'] != $port['ifIndex'])
         * {
         * $pagp[$device['device_id']][$port['pagpGroupIfIndex']][$port['ifIndex']] = TRUE;
         * $parent = dbFetchRow("SELECT * FROM `ports` WHERE `ifIndex` = ? and `device_id` = ?", array($port['pagpGroupIfIndex'], $device['device_id']));
         * humanize_port($parent);
         * $string .= $br.'<i class="'.$config['icon']['split'].'"></i> <strong>' . generate_port_link($parent) . ' [PAgP]</strong>';
         * $br = "<br />";
         * }
         **/

        if (!isset($cache['ports_option']['ports_stack_low']) || in_array($port['ifIndex'], $cache['ports_option']['ports_stack_low'])) {
            foreach (dbFetchRows("SELECT * FROM `ports_stack` WHERE `port_id_low` = ? AND `device_id` = ? AND `ifStackStatus` = ?", [$port['ifIndex'], $device['device_id'], 'active']) as $higher_if) {
                if (!$higher_if['port_id_high']) {
                    continue;
                }
                //if (isset($pagp[$device['device_id']][$higher_if['port_id_high']][$port['ifIndex']])) { continue; } // Skip if same PAgP port
                if ($this_port = get_port_by_index_cache($device['device_id'], $higher_if['port_id_high'])) {
                    $label = '';
                    if ($this_port['ifType'] === 'l2vlan') {
                        $label = '<span class="label label-primary">L2 VLAN</span> ';
                    } elseif ($this_port['ifType'] === 'l3ipvlan' || $this_port['ifType'] === 'l3ipxvlan') {
                        $label = '<span class="label label-info">L3 VLAN</span> ';
                    } elseif ($this_port['ifType'] === 'ieee8023adLag') {
                        $label = '<span class="label label-success">LAG</span> ';
                    } elseif (str_starts($this_port['port_label'], 'Stack')) {
                        $label = '<span class="label label-warning">STACK</span> ';
                    } else {
                        $label = '<span class="label label-default">' . $this_port['human_type'] . '</span> ';
                        //r($this_port);
                    }
                    $string .= $br . '<i class="' . $config['icon']['split'] . '"></i> <strong>' . generate_port_link($this_port) . '</strong> ' . $label;
                    $br     = "<br />";
                }
            }
        }

        if (!isset($cache['ports_option']['ports_stack_high']) || in_array($port['ifIndex'], $cache['ports_option']['ports_stack_high'])) {
            foreach (dbFetchRows("SELECT * FROM `ports_stack` 
                              WHERE `port_id_high` = ? AND `device_id` = ? AND `ifStackStatus` = ?",
                                 [$port['ifIndex'], $device['device_id'], 'active']) as $lower_if) {
                if (!$lower_if['port_id_low']) {
                    continue;
                }
                //if (isset($pagp[$device['device_id']][$port['ifIndex']][$lower_if['port_id_low']])) { continue; } // Skip if same PAgP ports
                if ($this_port = get_port_by_index_cache($device['device_id'], $lower_if['port_id_low'])) {
                    $string .= $br . '<i class="' . $config['icon']['merge'] . '"></i> <strong>' . generate_port_link($this_port) . '</strong>';
                    $br     = "<br />";
                }
            }
        }

        unset($int_links, $int_links_v6, $int_links_v4, $int_links_phys, $br);

        $string .= '</td></tr>';
    } // End Detailed View

    // If we're showing graphs, generate the graph and print the img tags

    if ($vars['graph'] === "etherlike") {
        $graph_file = get_port_rrdfilename($port, "dot3", TRUE);
    } else {
        $graph_file = get_port_rrdfilename($port, NULL, TRUE);
    }

    if ($vars['graph'] && is_file($graph_file)) {

        $string .= '<tr><td colspan="' . $table_cols . '">';

        $graph_array         = [];
        $graph_array['to']   = $config['time']['now'];
        $graph_array['id']   = $port['port_id'];
        $graph_array['type'] = 'port_' . $vars['graph'];

        $string .= generate_graph_row($graph_array);

        $string .= '</td></tr>';

    }

    return $string;
}

function print_port_minigraph($port, $graph_type = 'port_bits', $period = 'day')
{
    $port['graph_type'] = $graph_type;

    $graph_array               = [];
    $graph_array['to']         = get_time();
    $graph_array['from']       = get_time($period);
    $graph_array['id']         = $port['port_id'];
    $graph_array['type']       = $port['graph_type'];
    $graph_array['width']      = 400; //100;
    $graph_array['height']     = 150; //20;
    $graph_array['bg']         = 'ffffff00';
    $graph_array['legend']     = "no";
    $graph_array['graph_only'] = "yes";

    $graph_array_zoom           = $graph_array;
    $graph_array_zoom['height'] = "175";
    $graph_array_zoom['width']  = "600";
    unset($graph_array_zoom['legend'], $graph_array_zoom['graph_only']);

    $link_array         = $graph_array;
    $link_array['page'] = "graphs";
    unset($link_array['height'], $link_array['width']);
    $link = generate_url($link_array);

    echo '
  <div class="box box-solid" style="float: left; margin-left: 10px; margin-bottom: 10px;  width:302px; min-width: 302px; max-width:302px; min-height:158px; max-height:158;">
    <div class="box-header with-border">
      <a href="device/device=682/"><h3 class="box-title">' . escape_html($port['port_label_short']) . '</h3></a>
    </div>
  <div class="box-body no-padding">
  ' . overlib_link($link, generate_graph_tag($graph_array), generate_graph_tag($graph_array_zoom), NULL) . '
  </div>
  <div class="box-footer" style="padding: 0px 10px"><span style="font-size: 0.7em">' . short_port_descr($port['ifAlias']) . '</span></div>
  
</div>';

}

function print_port_permission_box($mode, $perms, $params = []) {
    global $config;

    echo generate_box_open([ 'header-border' => TRUE, 'title' => 'Port Permissions' ]);
    if (!safe_empty($perms['port'])) {
        echo('<table class="' . OBS_CLASS_TABLE . '">' . PHP_EOL);

        foreach (array_keys($perms['port']) as $entity_id) {
            $port   = get_port_by_id($entity_id);
            $device = device_by_id_cache($port['device_id']);

            echo('<tr><td style="width: 1px;"></td>
                <td style="width: 200px; overflow: hidden;">' . get_icon($config['devicetypes'][$device['type']]['icon'] ?? $config['entities']['device']['icon']) . generate_entity_link('device', $device) . '</td>
                <td style="overflow: hidden;">' . get_icon($config['entities']['port']['icon']) . generate_entity_link('port', $port) . '
                <small>' . $port['ifDescr'] . '</small></td>
                <td width="25">');

            $form = [];
            $form['type'] = 'simple';

            $action_del = $mode === 'role' ? 'role_entity_del' : 'user_perm_del';
            // Elements
            $form['row'][0]['auth_secret'] = [
                'type'  => 'hidden',
                'value' => $_SESSION['auth_secret']];
            $form['row'][0]['entity_id'] = [
                'type'  => 'hidden',
                'value' => $port['port_id']
            ];
            $form['row'][0]['entity_type'] = [
                'type'  => 'hidden',
                'value' => 'port'
            ];
            $form['row'][0]['submit'] = [
                'type'  => 'submit',
                'name'  => '',
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
        echo('<p class="text-center text-warning bg-warning" style="padding: 10px; margin: 0px;"><strong>This '.$mode.' currently has no permitted ports</strong></p>');
    }

    // Ports
    $permissions_list = array_keys((array)$perms['port']);

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
        'value' => 'port'
    ];
    $form['row'][0]['action'] = [
        'type'  => 'hidden',
        'value' => $action_add
    ];

    $form_items['devices'] = generate_form_values('device', array_keys((array)$perms['device']), NULL,
                                                  [ 'filter_mode' => 'exclude', 'subtext' => '%location%', 'show_disabled' => TRUE, 'show_icon' => TRUE ]);
    $form['row'][0]['device_id'] = [
        'type'     => 'select',
        'name'     => 'Select a device',
        'width'    => '150px',
        'onchange' => "getInterfaceList(this, 'port_entity_id')",
        //'value'    => $vars['device_id'],
        'groups'   => ['', 'UP', 'DOWN', 'DISABLED'], // This is optgroup order for values (if required)
        'values'   => $form_items['devices']
    ];
    $form['row'][0]['port_entity_id'] = [
        'type'   => 'multiselect',
        'name'   => 'Permit Port',
        'width'  => '150px',
        //'value'    => $vars['port_entity_id'],
        'values' => []
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

// EOF
