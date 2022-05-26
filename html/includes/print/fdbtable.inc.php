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

/**
 * Display FDB table.
 *
 * @param array $vars
 * @return none
 *
 */
function print_fdbtable($vars) {
  $entries = get_fdbtable_array($vars);

  if (!$entries['count']) {
    // There have been no entries returned. Print the warning.
    print_warning('<h4>No FDB entries found!</h4>');
    return;
  }

  $list = array('device' => FALSE, 'port' => FALSE);
  if (!isset($vars['device']) || empty($vars['device']) || $vars['page'] === 'search') { $list['device'] = TRUE; }
  if (!isset($vars['port'])   || empty($vars['port'])   || $vars['page'] === 'search') { $list['port'] = TRUE; }

  $string = generate_box_open();

  $string .= '<table class="table  table-striped table-hover table-condensed">' . PHP_EOL;

  $cols = [
    'device'         => 'Device',
    'mac'            => [ 'MAC Address', 'style="width: 160px;"' ],
    'status'         => [ 'Status', 'style="width: 100px;"' ],
    'port'           => 'Port',
    'trunk'          => 'Trunk/Type',
    'vlan_id'        => 'VLAN ID',
    'vlan_name'      => 'VLAN NAME',
    'changed'        => [ 'Changed', 'style="width: 100px;"' ]
  ];

  if (!$list['device'])  { unset($cols['device']); }
  if (!$list['port'])    { unset($cols['port']); }

  if (!$short) {
    $string .= get_table_header($cols, $vars); // Currently, sorting is not available
  }

  //print_vars($entries['entries']);
  foreach ($entries['entries'] as $entry) {
    if ($entry['deleted']) {
      $port = [];

      $string .= '  <tr class="ignore">' . PHP_EOL;
    } else {
      $port = get_port_by_id_cache($entry['port_id']);

      $string .= '  <tr>' . PHP_EOL;
    }
    if ($list['device']) {
      $dev = device_by_id_cache($entry['device_id']);
      $string .= '    <td class="entity" style="white-space: nowrap;">' . generate_device_link($dev) . '</td>' . PHP_EOL;
    }
    $string .= '    <td>' . generate_popup_link('mac', format_mac($entry['mac_address'])) . '</td>' . PHP_EOL;
    $string .= '    <td>' . $entry['fdb_status'] . '</td>' . PHP_EOL;
    if ($list['port']) {
      $string .= '    <td class="entity">' . generate_port_link_short($port) . ' ' . $port_error . '</td>' . PHP_EOL;
    }
    $string .= '    <td><span class="label">' . ($port['ifType'] === 'l2vlan' && empty($port['ifTrunk']) ? $port['human_type'] : $port['ifTrunk']) . '</span></td>' . PHP_EOL;
    $string .= '    <td>' . ($entry['vlan_vlan'] ? 'Vlan' . $entry['vlan_vlan'] : '') . '</td>' . PHP_EOL;
    $string .= '    <td>' . $entry['vlan_name'] . '</td>' . PHP_EOL;
    $string .= '    <td>' . generate_tooltip_link(NULL, format_uptime((get_time() - $entry['fdb_last_change']), 'short-2') . ' ago', format_unixtime($entry['fdb_last_change'])) . '</td>' . PHP_EOL;
    $string .= '  </tr>' . PHP_EOL;
  }

  $string .= '  </tbody>' . PHP_EOL;
  $string .= '</table>';

  $string .= generate_box_close();

  // Print pagination header
  if ($entries['pagination_html']) {
    $string = $entries['pagination_html'] . $string . $entries['pagination_html'];
  }

  // Print FDB table
  echo $string;
}

/**
 * Fetch FDB table array
 *
 * @param array $vars
 * @return array
 *
 */
function get_fdbtable_array($vars) {

  $array = [];

  // With pagination? (display page numbers in header)
  $array['pagination'] = (isset($vars['pagination']) && $vars['pagination']);
  pagination($vars, 0, TRUE); // Get default pagesize/pageno
  $array['pageno']   = $vars['pageno'];
  $array['pagesize'] = $vars['pagesize'];
  $start             = $array['pagesize'] * $array['pageno'] - $array['pagesize'];
  $pagesize          = $array['pagesize'];

  $params = [];
  $where = ' WHERE 1 ';
  $join_ports = FALSE;
  if (!isset($vars['deleted'])) {
    // Do not show deleted entries by default
    $vars['deleted'] = 0;
  }
  //r($vars);
  foreach ($vars as $var => $value) {
    if ($value != '') {
      switch ($var) {
        case 'device':
        case 'device_id':
          $where .= generate_query_values($value, 'F.device_id');
          break;
        case 'port':
        case 'port_id':
          $where .= generate_query_values($value, 'F.port_id');
          break;
        case 'interface':
        case 'port_name':
          $where .= generate_query_values($value, 'I.ifDescr', 'LIKE%');
          $join_ports = TRUE;
          break;
        case 'trunk':
          if (get_var_true($value)) {
            $where .= " AND (`I`.`ifTrunk` IS NOT NULL AND `I`.`ifTrunk` != '')";
            $join_ports = TRUE;
          } elseif (in_array($value, [ 'none', 'no', '0' ])) {
            $where .= " AND (`I`.`ifTrunk` IS NULL OR `I`.`ifTrunk` = '')";
            $join_ports = TRUE;
          }
          break;
        case 'vlan_id':
          $where .= generate_query_values($value, 'F.vlan_id');
          break;
        case 'vlan_name':
          $where .= generate_query_values($value, 'V.vlan_name');
          break;
        case 'address':
          if (str_contains_array($value, [ '*', '?' ])) {
            $like = 'LIKE';
          } else {
            $like = '%LIKE%';
          }
          $where .= generate_query_values(str_replace([ ':', ' ', '-', '.', '0x' ],'', $value), 'F.mac_address', $like);
          break;
        case 'deleted':
          $where .= ' AND `deleted` = ?';
          $params[] = $value;
      }
    }
  }

  if (isset($vars['sort'])) {
    switch($vars['sort']) {
      case "vlan_id":
        $sort = " ORDER BY `V`.`vlan_vlan`";
        break;

      case "vlan_name":
        $sort = " ORDER BY `V`.`vlan_name`";
        break;

      case "port":
        $sort = " ORDER BY `I`.`port_label`";
        $join_ports = TRUE;
        break;

      case "changed":
        $sort = " ORDER BY `F`.`fdb_last_change`";
        break;

      case "mac":
      default:
        $sort = " ORDER BY `mac_address`";

    }
  }

  // Show FDB tables only for permitted ports
  $query_permitted = generate_query_permitted([ 'device', 'port' ], [ 'device_table' => 'F', 'port_table' => 'F', 'port_null' => TRUE ]);

  $query = 'FROM `vlans_fdb` AS F ';
  $query .= 'LEFT JOIN `vlans` as V ON F.`vlan_id` = V.`vlan_vlan` AND F.`device_id` = V.`device_id` ';
  if ($join_ports) {
    $query .= 'LEFT JOIN `ports` AS I ON I.`port_id` = F.`port_id` ';
  }
  $query .= $where . $query_permitted;
  $query_count = 'SELECT COUNT(*) ' . $query;
  $query =  'SELECT F.*, V.`vlan_vlan`, V.`vlan_name` ' . $query;
  $query .= $sort;
  $query .= " LIMIT $start,$pagesize";

  // Query addresses
  //$array['entries'] = dbFetchRows($query, $params, TRUE);
  $array['entries'] = dbFetchRows($query, $params);

  if ($array['pagination']) {
    // Query address count
    $array['count']           = dbFetchCell($query_count, $params);
    $array['pagination_html'] = pagination($vars, $array['count']);
  } else {
    $array['count'] = safe_count($array['entries']);
  }

  return $array;
}

// EOF
