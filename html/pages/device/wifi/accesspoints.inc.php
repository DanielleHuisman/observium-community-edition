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

if ($device['type'] == 'wireless') {
    if (!$vars['graph']) {
        $graph_type = "device_lwappmember_bits";
    } else {
        $graph_type = "device_lwappmember_" . $vars['graph'];
    }

    $menu_options = ['basic' => 'Basic'];
    if (!$vars['view']) {
        $vars['view'] = "basic";
    }

    $navbar['brand'] = "Accesspoints";
    $navbar['class'] = "navbar-narrow";

    foreach ($menu_options as $option => $text) {
        if ($vars['view'] == $option) {
            $navbar['options'][$option]['class'] = "active";
        }
        $navbar['options'][$option]['text'] = $text;
        $navbar['options'][$option]['url']  = generate_url($vars, ['view' => $option, 'graph' => NULL]);
    }

    // FIXME. This page not exist.. (c) mike
    //$graph_types = array("conns" => "Connections");

    foreach ($graph_types as $type => $descr) {
        if ($vars['graph'] == $type) {
            $navbar['options_right'][$type]['class'] = "active";
        }
        $navbar['options_right'][$type]['text'] = $descr;
        $navbar['options_right'][$type]['url']  = generate_url($vars, ['view' => 'graphs', 'graph' => $type]);
    }

    print_navbar($navbar);
    unset($navbar);

    if ($vars['view'] == "graphs" || $vars['view'] == "services") {
        $table_class = "table-striped-two";
    } else {
        $table_class = "table-striped";
    }

    echo generate_box_open();
    echo '<table class="table table-striped table-condensed">';
    echo '  <thead>';
    echo '     <tr>';
    echo '       <th class="state-marker"></th>';
    echo '       <th>Name</th>';
    echo '       <th>Identifier / MAC</th>';
    echo '       <th>IP Address</th>';
    echo '       <th>Model</th>';
    echo '       <th>Location</th>';
    echo '       <th>Serial/Fingerprint</th>';
    echo '       <th>Admin Status</th>';
    echo '       <th></th>';
    echo '     </tr>';
    echo '  </thead>';

    if (isset($vars['accesspoint'])) {
        $accesspoints_db = dbFetchRows("SELECT * FROM `wifi_aps` WHERE `device_id` = ? AND `wifi_ap_id` = ? ORDER BY `ap_name`", [$device['device_id'], $vars['accesspoint']]);
    } else {
        $accesspoints_db = dbFetchRows("SELECT * FROM `wifi_aps` WHERE `device_id` = ? ORDER BY `ap_name`", [$device['device_id']]);
    }

    foreach ($accesspoints_db as $accesspoint) {

        switch ($accesspoint['ap_status']) {
            case 'up': // Associated
                $row_class = "up";
                break;
            case 'down': //Deassociating
                $row_class = "error";
                break;
            case 'init': //Downloading
                $row_class = "success";
                break;
            case 'disable': //Admin Status Disable
                $row_class = "suppressed";
                break;
            default:  //something else
                $row_class = "ignore";
        }

        switch ($accesspoint['ap_admin_status']) {
            case 'enable':
                $ap_class = "label label-success";
                $ap_state = "Enabled";
                break;
            case 'disable':
                $ap_class  = "label label-error";
                $ap_state  = "Disabled";
                $row_class = "ignore";
                break;
        }


        echo '<tr class="' . $row_class . '">';
        echo '<td class="state-marker"></td>';
        echo '<td class="entity"><a href="' . generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'wifi', 'view' => 'accesspoints', 'accesspoint' => $accesspoint['wifi_ap_id'], 'graph' => NULL]) . '">' . $accesspoint['ap_name'] . '</a></td>';

        echo '<td>' . $accesspoint['ap_index'] . '</td>';
        echo '<td>' . $accesspoint['ap_address'] . '</td>';
        echo '<td>' . $accesspoint['ap_model'] . '</td>';
        echo '<td>' . $accesspoint['ap_location'] . '</td>';
        echo '<td>' . $accesspoint['ap_serial'] . (isset($accesspoint['fingerprint']) ? ' / ' . $accesspoint['fingerprint'] : '') . '</td>';
        echo '<td><span class="' . $ap_class . '">' . $ap_state . '</span></td>';
        echo '<td>';
        if ($accesspoint['ap_status'] == 'down') //if device is down, offer the possibility to delete it from the DB
        {
            if ($_SESSION['userlevel'] > 9) {
                echo '<a class="pull-right" href="#modal-delete-ap-' . $accesspoint['wifi_ap_id'] . '" data-toggle="modal" title="Delete Current Access Point"><span><i class="sprite-minus"></i></span></a>';

                // Delete AP modal

                $del_modal = ['type'      => 'horizontal',
                              'userlevel' => 9,          // Minimum user level for display form
                              'id'        => 'modal-delete-ap-' . $accesspoint['wifi_ap_id'],
                              'title'     => 'Delete Access Point "' . $accesspoint['ap_name'] . '"',
                              'icon'      => $config['icon']['device-delete'],
                              'url'       => NULL
                ];

                $del_modal['row'][0]['id'] = [
                  'type'     => 'hidden',
                  'fieldset' => 'body',
                  'value'    => $accesspoint['wifi_ap_id']];

                $del_modal['row'][4]['confirm'] = [
                  'type'     => 'checkbox',
                  'fieldset' => 'body',
                  'name'     => 'Confirm AP Deletion',
                  'onchange' => "javascript: toggleAttrib('disabled', 'btn-delete-ap-" . $accesspoint['wifi_ap_id'] . "');",
                  'value'    => 'confirm'];

                $del_modal['row'][9]['btn-delete-ap-' . $accesspoint['wifi_ap_id']] = [
                  'type'      => 'submit',
                  'fieldset'  => 'footer',
                  'div_class' => '', // Clean default form-action class!
                  'name'      => 'Delete AP',
                  'icon'      => 'icon-remove icon-white',
                  'right'     => TRUE,
                  'class'     => 'btn-danger',
                  'onclick'   => "delete_ap(" . $accesspoint['wifi_ap_id'] . "); FALSE;",
                  'disabled'  => TRUE];

                echo generate_form_modal($del_modal);
                unset($del_modal);
            }
        }
        echo '</td>';
        echo '</tr>';

        $ap_members_db = dbFetchRows("SELECT * FROM `wifi_aps_members` WHERE `device_id` = ? AND `ap_name` = ?", [$device['device_id'], $accesspoint['ap_name']]);

        if (isset($vars['accesspoint'])) {
            if (count($ap_members_db)) {
                $uptime_ui             = format_uptime($accesspoint['ap_uptime'], "long");
                $controller_uptime_ui  = format_uptime($accesspoint['ap_control_uptime'], "long");
                $controller_latency_ui = format_uptime($accesspoint['ap_control_latency'], "long");

                echo '<tr><td colspan="1">' . PHP_EOL;
                echo '<th>AP Uptime:</th>';
                echo '<th>' . $uptime_ui . '</th>';
                echo '</tr>' . PHP_EOL;

                echo '<tr><td colspan="1">' . PHP_EOL;
                echo '<th>AP Controller Uptime:</th>';
                echo '<th>' . $controller_uptime_ui . '</th>';
                echo '</tr>' . PHP_EOL;

                echo '<tr><td colspan="1">' . PHP_EOL;
                echo '<th>AP Controller latency:</th>';
                echo '<th>' . $controller_latency_ui . '</th>';
                echo '</tr>' . PHP_EOL;
                echo '</table>';
                echo generate_box_close();


                echo generate_box_open(['title' => 'Radio Interfaces']);
                echo '<tr><td colspan="8">' . PHP_EOL;
                echo '    <table class="table table-striped table-condensed box box-solid">' . PHP_EOL;
                echo '      <thead>' . PHP_EOL;
                echo '        <tr>' . PHP_EOL;
                echo '          <th class="state-marker"></th>' . PHP_EOL;
                echo '          <th>Radio Slot #</th>' . PHP_EOL;
                echo '          <th>Radio Interface Type</th>' . PHP_EOL;
                echo '          <th>Channel #</th>' . PHP_EOL;
                echo '          <th>Connected Devices</th>' . PHP_EOL;
                echo '          <th>&nbsp;</th>' . PHP_EOL;
                echo '          <th>&nbsp;</th>' . PHP_EOL;
                echo '          <th>Oper Status</th>' . PHP_EOL;
                echo '          <th>Admin Status</th>' . PHP_EOL;
                echo '        </tr>' . PHP_EOL;
                echo '      </thead>' . PHP_EOL;

                foreach ($ap_members_db as $member) {
                    switch ($member['ap_member_state']) {
                        case 'up': // Enabled and UP
                            $member_class = "label label-success";
                            $member_state = "Up";
                            break;
                        case 'down': // Down
                            $member_class = "label label-error";
                            $member_state = "Down";
                            $row_class    = "error";
                            break;
                    }

                    switch ($member['ap_member_admin_state']) {
                        case 'enable':
                            $member_admin_class = "label label-success";
                            $member_admin_state = "Enable";
                            break;
                        case 'disable':
                            $member_admin_class = "label label-error";
                            $member_admin_state = "Disable";
                            $row_class          = "ignore";
                            break;
                    }

                    $RadioSlotnum = substr($member['ap_index_member'], -1);

                    echo '<tr class="' . $row_class . '">';
                    echo '<td class="state-marker">';
                    echo '<td class="entity-name">' . $RadioSlotnum . '</td>';
                    echo '<td>' . $member['ap_member_radiotype'] . '</td>';
                    echo '<td>' . $member['ap_member_channel'] . '</td>';
                    echo '<td style="width: 120px">' . $member['ap_member_conns'] . '</td>';

                    echo '<td align="left" style="width: 90px">';
                    foreach ($graph_types as $graph_type => $graph_text) {
                        //$graph_type = "lwappmember_" . $graph_type;
                        $graph_type            = "wifi-ap-member_" . $graph_type;
                        $graph_array['to']     = get_time();
                        $graph_array['from']   = get_time('day');
                        $graph_array['id']     = $member['ap_index_member'];
                        $graph_array['type']   = $graph_type;
                        $graph_array['legend'] = "no";
                        $graph_array['width']  = 80;
                        $graph_array['height'] = 20;
                        $graph_array['bg']     = 'ffffff00';

                        $minigraph       = generate_graph_tag($graph_array);
                        $overlib_content = generate_overlib_content($graph_array, $device['hostname'] . " - " . $member['ap_index_member'] . " - " . $graph_text);
                        echo overlib_link($link, $minigraph, $overlib_content);
                        unset($graph_array);

                    }
                    echo '</td>';
                    echo '<td>&nbsp;</td>';
                    echo '<td><span class="' . $member_class . '">' . $member_state . '</span></td>';
                    echo '<td><span class="' . $member_admin_class . '">' . $member_admin_state . '</span></td>';
                    echo '</tr>' . PHP_EOL;
                }
                echo '    </table>';
                echo '</td></tr>';
                echo generate_box_close();


                echo generate_box_open(['title' => 'Ethernet Interfaces']);
                echo '<tr><td colspan="8">' . PHP_EOL;
                echo '    <table class="table table-striped table-condensed box box-solid">' . PHP_EOL;
                echo '      <thead>';
                echo '<tr><th class="state-marker"></th>';
                echo '<th>Interface Name</th>';
                echo '<th>&nbsp;</th>';
                echo '<th>&nbsp;</th>';
                echo '<th>Oper Status</th>';
                echo '<th>Admin Status</th>';
                echo '</tr>  </thead>' . PHP_EOL;

                //grab the related ports already polled from cisco-lwapp-ap-mib.inc.php on ports folder
                //by using a sql relation from the index id. Also create a link to the port URL, similiar to the F5 virtuals.

                echo '    </table>';
                echo '</td></tr>';
                echo generate_box_close();
            }
        }
    }

    echo('</table>');
    echo generate_box_close();
}

unset($accesspoints_db, $accesspoint, $navbar, $ap_members_db, $member, $member_class, $member_state, $row_class, $RadioSlotnum, $member_admin_class, $member_admin_state);
register_html_title('Access Points');

// EOF
