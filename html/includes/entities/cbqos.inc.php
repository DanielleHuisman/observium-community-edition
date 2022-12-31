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

function build_cbqos_query($vars)
{
  $sql = 'SELECT * FROM `ports_cbqos`';

  //if ($vars['sort'] == 'hostname' || $vars['sort'] == 'device' || $vars['sort'] == 'device_id') {
  $sql .= ' LEFT JOIN `devices` USING(`device_id`)';
  //}

  $sql .= ' LEFT JOIN `ports` USING (`port_id`)';

  // Rewrite `device_id` to use `ports_cbqos` table name to avoid dupe field error.
  $sql .= ' WHERE 1' . str_replace('`device_id`', '`ports_cbqos`.`device_id`', generate_query_permitted(array('device')));

  // Build query
  foreach ($vars as $var => $value) {
    switch ($var) {
      case "policy_name":
      case "object_name":
        $sql .= generate_query_values_and($value, $var);
        break;
      case "group":
      case "group_id":
        $values = get_group_entities($value);
        $sql .= generate_query_values_and($values, 'cbqos_id');
        break;
      case 'device_group_id':
      case 'device_group':
        $values = get_group_entities($value, 'device');
        $sql .= generate_query_values_and($values, 'ports_cbqos.device_id');
        break;
      case "device":
      case "device_id":
        $sql .= generate_query_values_and($value, 'ports_cbqos.device_id');
        break;
    }
  }

  switch ($vars['sort_order']) {
    case 'desc':
      $sort_order = 'DESC';
      $sort_neg = 'ASC';
      break;
    case 'reset':
      unset($vars['sort'], $vars['sort_order']);
    // no break here
    default:
      $sort_order = 'ASC';
      $sort_neg = 'DESC';
  }

  switch ($vars['sort']) {
    case 'policy_name':
    case 'object_name':
    case 'PrePolicyByte_rate':
    case 'PostPolicyByte_rate':
    case 'DropByte_rate':

    $sql .= ' ORDER BY '.$vars['sort'].' ' . $sort_order;
      break;
    default:
      $sql .= ' ORDER BY `hostname` ' . $sort_order . ', `port_label_short` '. $sort_order;
  }

  return $sql;

}


function print_cbqos_table_header($vars)
{

  echo('<table class="' . (get_var_true($vars['graphs']) ? OBS_CLASS_TABLE_STRIPED_TWO : OBS_CLASS_TABLE_STRIPED) . '">');

  $cols[] = array('', 'class="state-marker"');
  if(!isset($vars['device_id'])) { $cols['hostname']    = array('Device', 'style="width: 200px;"'); }
  if(!isset($vars['device_id'])) { $cols['port_label']   = array('Port', 'style="width: 200px;"'); }

  $cols['policy_name']         = array('Policy', 'style="width: 150px;"');
  $cols['object_name']         = array('Object', 'style="width: 200px;"');
  $cols['PrePolicyByte_rate']  = array('Traffic');
  $cols['DropByte_rate']       = array('Dropped');

  $cols[]  = array();


  echo get_table_header($cols, $vars);
  echo '<tbody>' . PHP_EOL;
}

function print_cbqos_table($vars) {
  global $config;

  $sql = build_cbqos_query($vars);
  $entries = dbFetchRows($sql);

  if (!safe_empty($entries)) {

    echo generate_box_open();

    print_cbqos_table_header($vars);

    foreach ($entries as $cbqos_id => $entry) {

      $perc_drop = float_div($entry['DropByte_rate'], $entry['PrePolicyByte_rate']) * 100;

      echo '<tr>';
      echo '<td class="state-marker"></td>';
      echo '<td class="entity">'.generate_device_link($entry).'</td>';
      echo '<td class="entity">'.generate_port_link($entry).'</td>';
      echo '<td class="entity"><a href="'.generate_url(array('page' => 'device', 'device' => $entry['device_id'], 'tab' => 'port', 'port' => $entry['port_id'], 'view' => 'cbqos')).'">'.$entry['policy_name'].'</a></td>';
      echo '<td class="entity"><a href="'.generate_url(array('page' => 'device', 'device' => $entry['device_id'], 'tab' => 'port', 'port' => $entry['port_id'], 'view' => 'cbqos')).'">'.$entry['object_name'].'</a></td>';
      echo '<td>'.format_number($entry['PrePolicyByte_rate']).'bps / '.format_number($entry['PostPolicyByte_rate']).'bps</td>';
      echo '<td>'.format_number($entry['DropByte_rate']).'bps ('.$perc_drop.'%)</td>';

      echo '<td></td>';
      echo '</tr>';

      if (get_var_true($vars['graphs'])) {
        $vars['graph'] = "graph";
      }

      if ($vars['graph']) {
        $graph_array = array();
        $graph_title = $entry['oid_descr'];
        $graph_array['type'] = "cbqos_".$vars['graph'];
        $graph_array['id'] = $entry['cbqos_id'];

        echo '<tr>';
        echo '  <td class="state-marker"></td>';
        echo '  <td colspan=8>';
        print_graph_row($graph_array);
        echo '  </td>';
        echo '</tr>';
      }


    }

    echo '  </table>' . PHP_EOL;
    echo generate_box_close();

  }

}

// EOF