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

echo generate_box_open(['box-class' => 'hidden-xl']);

echo('<table class="table table-condensed table-striped table-hover">');

if ($config['overview_show_sysDescr']) {
    echo('<tr><td colspan=2 style="padding: 10px;"><strong><i>' . escape_html($device['sysDescr']) . "</i></strong></td></tr>");
}

if ($device['purpose']) {
    echo('<tr>
        <td class="entity">Description</td>
        <td>' . escape_html($device['purpose']) . '</td>
      </tr>');
}

if ($device['hardware']) {
    if ($device['vendor']) {
        echo('<tr>
          <td class="entity">Vendor/Hardware</td>
          <td>' . escape_html($device['vendor'] . ' ' . $device['hardware']) . '</td>
        </tr>');
    } else {
        echo('<tr>
          <td class="entity">Hardware</td>
          <td>' . escape_html($device['hardware']) . '</td>
        </tr>');
    }
} elseif ($device['vendor']) {
    // Only Vendor exist
    echo('<tr>
        <td class="entity">Vendor</td>
        <td>' . escape_html($device['vendor']) . '</td>
      </tr>');
}

if ($device['os'] !== 'generic') {
    echo('<tr>
        <td class="entity">Operating system</td>
        <td>' . escape_html($device['os_text']) . ' ' . escape_html($device['version']) . ($device['features'] ? ' (' . escape_html($device['features']) . ')' : '') . ' </td>
      </tr>');
}

if ($device['sysName']) {
    echo('<tr>
        <td class="entity">System name</td>');
    echo('
        <td>' . escape_html($device['sysName']) . '</td>
      </tr>');
}

if ($_SESSION['userlevel'] >= 5 && $device['ip']) {
    echo('<tr>
        <td class="entity">Cached IP</td>');
    echo('
        <td>' . escape_html($device['ip']) . '</td>
      </tr>');
}

if ($device['sysContact']) {
    echo('<tr>
        <td class="entity">Contact</td>');
    if (get_dev_attrib($device, 'override_sysContact_bool')) {
        echo('
        <td>' . escape_html(get_dev_attrib($device, 'override_sysContact_string')) . '</td>
      </tr>
      <tr>
        <td class="entity">SNMP Contact</td>');
    }
    echo('
        <td>' . escape_html($device['sysContact']) . '</td>
      </tr>');
}

if ($device['location']) {
    echo('<tr>
        <td class="entity">Location</td>
        <td>' . escape_html($device['location']) . '</td>
      </tr>');
    if (get_dev_attrib($device, 'override_sysLocation_bool') && !empty($device['real_location'])) {
        echo('<tr>
        <td class="entity">SNMP Location</td>
        <td>' . escape_html($device['real_location']) . '</td>
      </tr>');
    }
}

if ($device['asset_tag']) {
    echo('<tr>
        <td class="entity">Asset tag</td>
        <td>' . escape_html($device['asset_tag']) . '</td>
      </tr>');
}

if ($device['serial']) {
    echo('<tr>
        <td class="entity">Serial</td>
        <td>' . escape_html($device['serial']) . '</td>
      </tr>');
}

if ($device['state']['la']['5min']) {
    if ($device['state']['la']['5min'] > 10) {
        $la_class = 'text-danger';
    } elseif ($device['state']['la']['5min'] > 4) {
        $la_class = 'text-warning';
    } else {
        $la_class = '';
    }
    echo('<tr>
        <td class="entity">Load average</td>
        <td class="' . $la_class . '">' . number_format((float)$device['state']['la']['1min'], 2) . ', ' .
         number_format((float)$device['state']['la']['5min'], 2) . ', ' .
         number_format((float)$device['state']['la']['15min'], 2) . '</td>
      </tr>');
}

if ($device['uptime']) {
    echo('<tr>
        <td class="entity">Uptime</td>
        <td>' . device_uptime($device) . '</td>
      </tr>');
}
/*
if ($device['status_type'] && $device['status_type'] != 'ok')
{
  if ($device['status_type'] == 'ping')
  {
    $reason = 'not Pingable';
  }
  else if ($device['status_type'] == 'snmp')
  {
    $reason = 'not SNMPable';
  }
  else if ($device['status_type'] == 'dns')
  {
    $reason = 'DNS hostname unresolved';
  }

  echo('<tr>
        <td class="entity">Down reason</td>
        <td>' . $reason . '</td>
      </tr>');
}
*/

if ($device['last_rebooted']) {
    echo('<tr>
        <td class="entity">Last reboot</td>
        <td>' . format_unixtime($device['last_rebooted']) . '</td>
      </tr>');
}

echo("</table>");
echo generate_box_close();

// EOF
