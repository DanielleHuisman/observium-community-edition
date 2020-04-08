<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

function print_logalert_log($vars)
{

  global $config;

  foreach(dbFetchRows("SELECT * FROM `syslog_rules` ORDER BY `la_name`") AS $la)
  {
    $syslog_rules[$la['la_id']] = $la;
  }

  $entries = get_logalert_log($vars);

  if (!$entries['count'])
  {
    // There have been no entries returned. Print the warning.
    print_warning('<h4>No logging alert entries found!</h4>');
  } else {

    // Entries have been returned. Print the table.
    $list = array('device' => FALSE);
    if (!isset($vars['device']) || empty($vars['device']) || $vars['page'] == 'alert_log') { $list['device'] = TRUE; }
    if (!isset($vars['la_id']) || empty($vars['la_id'])) { $list['la_id'] = TRUE; }

    $string = generate_box_open($vars['header']);

    $string .= '<table class="table table-striped table-hover table-condensed-more">' . PHP_EOL;

    if (!$entries['short'])
    {
      $cols = array();
      $cols[]       = array(NULL,            'class="state-marker"');
      $cols['date'] = array('Date',          'style="width: 140px"');
      if ($list['device'])
      {
        $cols['device'] = array('Device',    'style="width: 150px;"');
      }
      if ($list['la_id'])
      {
        $cols['la_id'] = array('Alert Rule', 'style="width: 150px;"');
      }
      $cols[]         = array('Program',     'style="width: 80px"');
      $cols[]         = 'Message';
      //$cols[]         = array('Notified',    'style="width: 40px"');
      $string .= get_table_header($cols); // , $vars); // Actually sorting is disabled now
    }
    $string   .= '  <tbody>' . PHP_EOL;


    foreach ($entries['entries'] as $entry)
    {
      $string .= '  <tr class="'.$entry['html_row_class'].'">' . PHP_EOL;
      $string .= '<td class="state-marker"></td>' . PHP_EOL;

      if ($entries['short'])
      {
        $string .= '    <td class="syslog" style="white-space: nowrap">';
        $timediff = $GLOBALS['config']['time']['now'] - strtotime($entry['timestamp']);
        $string .= generate_tooltip_link('', format_uptime($timediff, "short-3"), format_timestamp($entry['timestamp']), NULL) . '</td>' . PHP_EOL;
      } else {
        $string .= '    <td>';
        $string .= format_timestamp($entry['timestamp']) . '</td>' . PHP_EOL;
      }

      if ($list['device'])
      {
        $dev = device_by_id_cache($entry['device_id']);
        $device_vars = array('page'    => 'device',
                             'device'  => $entry['device_id'],
                             'tab'     => 'logs',
                             'section' => 'alertlog');
        $string .= '    <td class="entity">' . generate_device_link($dev, short_hostname($dev['hostname']), $device_vars) . '</td>' . PHP_EOL;
      }

      if ($list['la_id']) { $string .= '<td><strong><a href="'.generate_url(array('page' => 'syslog_rules', 'la_id' => $entry['la_id'])).'">' .
                                       (is_array($syslog_rules[$entry['la_id']]) ? $syslog_rules[$entry['la_id']]['la_name'] : 'Rule Deleted')  . '</td>' . PHP_EOL;}
      $string .= '<td>'. (strlen($entry['program']) ? '<span class="label">'.$entry['program'].'</span> ' : '') . '</td>' . PHP_EOL;
      $string .= '<td>'. escape_html($entry['message']) . '</td>' . PHP_EOL;
      if(!$vars['short'])
      {
        //$string .= '<td>' . escape_html($entry['log_type']) . '</td>' . PHP_EOL;
        //$string .= '<td style="text-align: right">'. ($entry['notified'] == '1' ? '<span class="label label-success">YES</span>' : ($entry['notified'] == '-1' ? '<span class="label">SKIP</span>' : '<span class="label label-warning">NO</span>')) . '</td>' . PHP_EOL;
      }

      $string .= '  </tr>' . PHP_EOL;

    }

    $string .= '  </tbody>' . PHP_EOL;
    $string .= '</table>';

    $string .= generate_box_close();

  }
    // Print pagination header
    if ($entries['pagination_html']) { $string = $entries['pagination_html'] . $string . $entries['pagination_html']; }

    // Print events
    echo $string;


}

function get_logalert_log($vars)
{
  $array = array();

  // Short events? (no pagination, small out)
  $array['short'] = (isset($vars['short']) && $vars['short']);

  // With pagination? (display page numbers in header)
  $array['pagination'] = (isset($vars['pagination']) && $vars['pagination']);
  pagination($vars, 0, TRUE); // Get default pagesize/pageno
  $array['pageno']   = $vars['pageno'];
  $array['pagesize'] = $vars['pagesize'];
  $start    = $array['pagesize'] * $array['pageno'] - $array['pagesize'];
  $pagesize = $array['pagesize'];

  // Begin query generate
  $param = array();
  $where = ' WHERE 1 ';
  foreach ($vars as $var => $value)
  {
    if ($value != '')
    {
      switch ($var)
      {
        case 'la_id':
          $where .= generate_query_values($value, 'la_id');
          break;
        case 'device':
        case 'device_id':
          $where .= generate_query_values($value, 'device_id');
          break;
        case 'program':
          $where .= generate_query_values($value, 'program', '%LIKE%');
          break;
        case 'message':
          $where .= generate_query_values($value, 'message', '%LIKE%');
          break;
        case 'timestamp_from':
          $where .= ' AND `timestamp` >= ?';
          $param[] = $value;
          break;
        case 'timestamp_to':
          $where .= ' AND `timestamp` <= ?';
          $param[] = $value;
          break;
      }
    }
  }

  // Show events only for permitted devices
  $query_permitted = generate_query_permitted(); //generate_query_permitted(array('entity'));

  $query = 'FROM `syslog_alerts` ';
  $query .= $where . $query_permitted;
  $query_count = 'SELECT COUNT(`la_id`) '.$query;
  $query_updated = 'SELECT MAX(`timestamp`) '.$query;

  $query = 'SELECT * '.$query;
  $query .= ' ORDER BY `lal_id` DESC ';
  $query .= "LIMIT $start,$pagesize";

  // Query events
  $array['entries'] = dbFetchRows($query, $param);

  // Query events count
  if ($array['pagination'] && !$array['short'])
  {
    $array['count'] = dbFetchCell($query_count, $param);
    $array['pagination_html'] = pagination($vars, $array['count']);
  } else {
    $array['count'] = count($array['entries']);
  }

  // Query for last timestamp
  $array['updated'] = dbFetchCell($query_updated, $param);

//r($array);

  return $array;

}

//EOF
