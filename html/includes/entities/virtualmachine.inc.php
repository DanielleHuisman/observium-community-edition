<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

function generate_vm_query($vars)
{
  $sql = 'SELECT * FROM `vminfo` WHERE 1 ';

  // Build query
  foreach($vars as $var => $value)
  {
    switch ($var)
    {
      case "group":
      case "group_id":
        $values = get_group_entities($value);
        $sql .= generate_query_values($values, 'vm_id');
        break;
      case 'device_group_id':
      case 'device_group':
        $values = get_group_entities($value, 'device');
        $sql .= generate_query_values($values, 'device_id');
        break;
      case "device":
      case "device_id":
        $sql .= generate_query_values($value, 'device_id');
        break;
      case "os":
        $sql .= generate_query_values($value, 'vm_guestos');
        break;
      case "state":
        $sql .= generate_query_values($value, 'vm_state');
        break;
      case "memory":
        $sql .= generate_query_values($value, 'vm_memory');
        break;
      case "cpu":
        $sql .= generate_query_values($value, 'vm_cpucount');
        break;
    }
  }
  $sql .= $GLOBALS['cache']['where']['devices_permitted'];

  return $sql;
}

function print_vm_table_header($vars)
{
  $stripe_class = "table-striped";

  echo('<table class="table ' . $stripe_class . ' table-condensed ">' . PHP_EOL);
  $cols = array(
//                array(NULL, 'class="state-marker"'), // FIXME useful when we start polling VM status
    'device' => array('Device', 'style="width: 250px;"'),
    'name'   => array('Name'),
    'state'  => array('State'),
    'os'     => array('Operating System'),
    'memory' => array('Memory'),
    'cpu'    => array('CPU'),
  );

  if ($vars['page'] == "device" || $vars['popup'] == TRUE )
  {
    unset($cols['device']);
  }

  echo(get_table_header($cols, $vars));
  echo('<tbody>' . PHP_EOL);

}

function print_vm_table($vars)
{
  $sql = generate_vm_query($vars);

  $vms = array();
  foreach(dbFetchRows($sql) as $vm)
  {
    if (isset($GLOBALS['cache']['devices']['id'][$vm['device_id']]))
    {
      $vm['hostname'] = $GLOBALS['cache']['devices']['id'][$vm['device_id']]['hostname'];
      $vms[] = $vm;
    }
  }

  // Sorting
  // FIXME. Sorting can be as function, but in must before print_table_header and after get table from db
  switch ($vars['sort_order'])
  {
    case 'desc':
      $sort_order = SORT_DESC;
      $sort_neg   = SORT_ASC;
      break;
    case 'reset':
      unset($vars['sort'], $vars['sort_order']);
      // no break here
    default:
      $sort_order = SORT_ASC;
      $sort_neg   = SORT_DESC;
  }
  switch ($vars['sort'])
  {
    case 'name':
      $vms = array_sort_by($vms, 'vm_name', $sort_order, SORT_STRING);
      break;
    case 'os':
      $vms = array_sort_by($vms, 'vm_os', $sort_order, SORT_STRING);
      break;
    case 'state':
      $vms = array_sort_by($vms, 'vm_state',  $sort_order, SORT_STRING);
      break;
    case 'memory':
      $vms = array_sort_by($vms, 'vm_memory', $sort_order, SORT_NUMERIC);
      break;
    case 'cpu':
      $vms = array_sort_by($vms, 'vm_cpucount', $sort_order, SORT_NUMERIC);
      break;
    default:
      // Not sorted
  }
  $vms_count = count($vms);

  // Pagination
  $pagination_html = pagination($vars, $vms_count);
  echo $pagination_html;

  if ($vars['pageno'])
  {
    $vms = array_chunk($vms, $vars['pagesize']);
    $vms = $vms[$vars['pageno'] - 1];
  }
  // End Pagination

  echo generate_box_open();

  print_vm_table_header($vars);

  foreach($vms as $vm)
  {
    print_vm_row($vm, $vars);
  }

  echo '</tbody></table>';

  echo generate_box_close();

  echo $pagination_html;
}

function print_vm_row($vm, $vars)
{
  echo generate_vm_row($vm, $vars);
}

function generate_vm_row($vm, $vars)
{
  global $config;

  $table_cols = "8";

  $out =  '<tr class="' . $vm['row_class'] . '">'; // <td class="state-marker"></td>'; // FIXME useful when we start polling VM state
  if ($vars['page'] != "device" && $vars['popup'] != TRUE )
  {
    $out .= '<td class="entity">' . generate_device_link($vm) . '</td>';
    $table_cols++;
  }
  $out .= '<td class="entity">'. generate_entity_link('virtualmachine', $vm) .'</td>';
  $out .= '<td>'. nicecase($vm['vm_state']) .'</td>';

  switch ($vm['vm_guestos'])
  {
    case 'E: tools not installed':
      $out .= '  <td class="small">Unknown (VMware Tools not installed)</td>';
      break;
    case 'E: tools not running':
      $out .= '  <td class="small">Unknown (VMware Tools not running)</td>';
      break;
    case '':
      $out .= '  <td class="small"><i>(Unknown)</i></td>';
      break;
    default:
      if (isset($config['vmware_guestid'][$vm['vm_guestos']]))
      {
        $out .= '  <td>' . $config['vmware_guestid'][$vm['vm_guestos']] . '</td>';
      } else {
        $out .= '  <td>' . $vm['vm_guestos'] . '</td>';
      }
      break;
  }

  $out .= '<td>'. format_bi($vm['vm_memory'] * 1024 * 1024, 3, 3) .'B</td>';
  $out .= '<td>'. $vm['vm_cpucount'] .'</td>';
  $out .= '</tr>';

  return $out;
}

// EOF
