<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2022 Observium Limited
 *
 */

// Global read only because no permissions checking right now
if ($_SESSION['userlevel'] < 5) {
  print_error_permission();
  return;
}

  $vlans = [];
  if (!isset($vars['vlan_id'])) {


    $vlans = get_vlans($vars);

    echo generate_box_open();
    echo '<table class="table table-striped table-hover">';
    echo '<tr>';
    echo '<th>VLAN ID</th><th>VLAN Name</th>';
    echo '<th>Devices</th>';
    echo '<th>Tagged / Untagged</th>';
    echo '<th>Unique MACs</th>';
    echo '</tr>';

    foreach ($vlans as $vlan_id => $vlan) {

      if ($vlan_id === '') {
        continue;
      }

      if (is_array($vlan['names'])) {
        ksort($vlan['names']);
        $first_vlan = array_keys($vlan['names'])[0];
      } else {
        $first_vlan = "VLAN $vlan_id";
      }

      // Fixme - improve method of choosing which name to use.
      //r($vlan['names']);

      echo '<tr>';
      echo '<td>' . $vlan_id . '</td>';
      echo '<td>' . generate_link($first_vlan, array('page' => 'vlan', 'vlan_id' => $vlan_id)) . '</td>';
      echo '<td><span class="label label-primary">' . $vlan['counts']['devices'] . '</span></td>';
      echo '<td><span class="label label-suppressed">' . $vlan['counts']['ports_tagged'] . '</span>
                <span class="label label-success">' . $vlan['counts']['ports_untagged'] . '</span></td>';
      echo '<td><span class="label label-warning">' . $vlan['counts']['macs'] . '</span></td>';
      echo '</tr>';

    }

    echo '</table>';
    echo generate_box_close();

    //r($vlans);

  } else {

    // Per-VLAN page

    $vls = dbFetchRows("SELECT * FROM `vlans`");
    foreach ($vls as $vlan) {
      $vlans[$vlan['vlan_vlan']]['names'][$vlan['vlan_name']]++;
    }

    $count_untagged = dbFetchCell("SELECT COUNT(*) FROM `ports` WHERE `ifVlan` = ?", [$vars['vlan_id']]);
    $count_device   = dbFetchCell("SELECT COUNT(DISTINCT(`device_id`)) FROM `vlans` WHERE `vlan_vlan` = ?", [$vars['vlan_id']]);
    $count_tagged   = dbFetchCell("SELECT COUNT(*) FROM `ports_vlans` WHERE `vlan` = ?", [$vars['vlan_id']]);
    $count_mac      = dbFetchCell("SELECT COUNT(DISTINCT(`mac_address`)) AS `count` FROM `vlans_fdb` WHERE `vlan_id` = ?", [$vars['vlan_id']]);

    echo generate_box_open();
    echo '
<table class="table table-hover table-striped vertical-align">
  <tbody>
    <tr class="up">
      <td class="state-marker"></td>
      <td style="padding: 10px 14px;"><span style="font-size: 20px; color: #193d7f;">VLAN ' . $vars['vlan_id'] . '</span>
      </td>
      <td>

      </td>

      <td style="text-align: right;">

        <div class="btn-group" style="margin: 5px;">
          <div class="btn btn-sm btn-default"><strong>Devices</strong></div>
          <div class="btn btn-sm btn-default">' . $count_device . '</div>
        </div>

        <div class="btn-group" style="margin: 5px;">
          <div class="btn btn-sm btn-default"><strong>Tagged Ports</strong></div>
          <div class="btn btn-sm btn-danger">' . $count_tagged . '</div>
        </div>

        <div class="btn-group" style="margin: 5px;">
          <div class="btn btn-sm btn-default"><strong>Untagged Ports</strong></div>
          <div class="btn btn-sm btn-info">' . $count_untagged . '</div>
        </div>

        <div class="btn-group" style="margin: 5px;">
          <div class="btn btn-sm btn-default"><strong>MACs</strong></div>
          <div class="btn btn-sm btn-primary">' . $count_mac . '</div>
        </div>
      </td>

     </tr>
   </tbody>
</table>';

    echo generate_box_close();

    $navbar = array('brand' => "VLAN " . $vars['vlan_id'], 'class' => "navbar-narrow");

    $navbar['options']['devices']['text']  = 'Devices';
    //$navbar['options']['untagged']['text'] = 'Untagged Ports';
    //$navbar['options']['tagged']['text']   = 'Tagged Ports';
    $navbar['options']['macs']['text']     = 'MAC Addresses';

    foreach ($navbar['options'] as $option => $array) {
      if (!isset($vars['view'])) {
        $vars['view'] = "devices";
      }
      if ($vars['view'] == $option) {
        $navbar['options'][$option]['class'] .= " active";
      }
      $navbar['options'][$option]['url'] = generate_url($vars, array('view' => $option));
    }

    $navbar['options']['vlans']['text'] = "Back to VLANs";
    $navbar['options']['vlans']['icon'] = "icon-angle-left";
    $navbar['options']['vlans']['url'] = generate_url(['page' => 'vlan']);
    $navbar['options']['vlans']['right'] = TRUE;


    print_navbar($navbar);
    unset($navbar);

    switch ($vars['view']) {

      case "tagged":



        break;

      case "untagged":

        break;

      case "macs":

        echo generate_box_open();
        echo '<table class="table table-striped table-hover">';
        echo '<tr>';
        echo '<th width="200">MAC Address</th>';
        echo '<th width="80">Devices</th>';
        echo '<th width="80">Entries</th>';
        echo '<th>Device</th>';
        echo '<th>Port</th>';

        echo '</tr>';

        $macs = dbFetchRows("SELECT *, COUNT(DISTINCT(`mac_address`)) AS `count`, COUNT(DISTINCT(`device_id`)) AS `device_count` FROM `vlans_fdb` WHERE `vlan_id` = ? GROUP BY `mac_address`", [ $vars['vlan_id'] ]);

        $mac_list = array_column($macs, 'mac_address');
        $sql = "SELECT * FROM ports, devices WHERE 1 ";
        $sql .= generate_query_values_and($mac_list, 'ifPhysAddress');
        $sql .= " AND ports.device_id = devices.device_id";

        $ports = [];
        foreach(dbFetchRows($sql) as $p) { $ports[$p['ifPhysAddress']] = $p; }

        foreach ($macs as $mac) {

          echo '<tr>';
          echo '<td>' . format_mac($mac['mac_address']) . '</td>';
          echo '<td><span class="label label-warning">' . $mac['device_count'] . '</span></td>';
          echo '<td><span class="label label-success">' . $mac['count'] . '</span></td>';
          echo '<td>'.(isset($ports[$mac['mac_address']]) ? generate_device_link($ports[$mac['mac_address']]) : '').'</td>';
          echo '<td>'.(isset($ports[$mac['mac_address']]) ?  generate_port_link($ports[$mac['mac_address']]) : '').'</td>';
          echo '<td>'.( (isset($ports[$mac['mac_address']]) && strlen($ports[$mac['mac_address']]['ifAlias'])) ?  '<small>'.$ports[$mac['mac_address']]['ifAlias'].'</small>' : '').'</td>';
          echo '</tr>';
        }

        echo '</table>';

        break;

      case "devices":
      default:
      $devices = dbFetchRows("SELECT * FROM `vlans` LEFT JOIN `devices` ON vlans.device_id = devices.device_id WHERE `vlan_vlan` = ? ORDER BY devices.hostname", [$vars['vlan_id']]);

      echo generate_box_open();
      echo '<table class="table table-striped table-hover">';
      echo '<tr>';
      echo '<th class="state-marker"></th>';
      echo '<th></th>';
      echo '<th width="80">Device</th>';
      echo '<th width="80">Hardware</th>';
      echo '<th>OS</th>';
      echo '<th>Uptime</th>';
      echo '</tr>';


      foreach ($devices AS $device)
      {
        //$device = device_by_id_cache($device_id);
        print_device_row($device, [], ['tab' => 'vlans']);

      }


      echo '</table>';

    }

  }

// EOF
