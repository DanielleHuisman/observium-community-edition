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

/**
 * Display syslog messages.
 *
 * Display pages with device syslog messages.
 * Examples:
 * print_syslogs() - display last 10 syslog messages from all devices
 * print_syslogs(array('pagesize' => 99)) - display last 99 syslog messages from all device
 * print_syslogs(array('pagesize' => 10, 'pageno' => 3, 'pagination' => TRUE)) - display 10 syslog messages from page 3 with pagination header
 * print_syslogs(array('pagesize' => 10, 'device' = 4)) - display last 10 syslog messages for device_id 4
 * print_syslogs(array('short' => TRUE)) - show small block with last syslog messages
 *
 * @param array $vars
 * @return none
 *
 */
function print_syslogs($vars)
{
  // Short events? (no pagination, small out)
  $short = (isset($vars['short']) && $vars['short']);
  // With pagination? (display page numbers in header)
  $pagination = (isset($vars['pagination']) && $vars['pagination']);
  pagination($vars, 0, TRUE); // Get default pagesize/pageno
  $pageno   = $vars['pageno'];
  $pagesize = $vars['pagesize'];
  $start = $pagesize * $pageno - $pagesize;

  $priorities = $GLOBALS['config']['syslog']['priorities'];

  $device_single = FALSE; // Show syslog entries for single device or multiple (use approximate counts for multiple)

  $param = array();
  $where = ' WHERE 1 ';
  foreach ($vars as $var => $value)
  {
    if ($value != '')
    {
      $cond = array();
      switch ($var)
      {
        case 'device':
        case 'device_id':
          $device_single = is_numeric($value);
          $where .= generate_query_values($value, 'device_id');
          break;
        case 'priority':
          if (!is_array($value)) { $value = explode(',', $value); }
          foreach ($value as $k => $v)
          {
            // Rewrite priority strings to numbers
            $value[$k] = priority_string_to_numeric($v);
          }
          $where .= generate_query_values($value, $var);
          break;
        case 'tag':
          if (!is_array($value)) { $value = explode(',', $value); }
          $where .= generate_query_values($value, $var);
          break;
        case 'program':
          $where .= generate_query_values($value, $var);
          break;
        case 'message':
          $where .= generate_query_values($value, 'msg', '%LIKE%');
          break;
        case 'timestamp_from':
          $where .= ' AND `timestamp` > ?';
          $param[] = $value;
          break;
        case 'timestamp_to':
          $where .= ' AND `timestamp` < ?';
          $param[] = $value;
          break;
      }
    }
  }

  // Show entries only for permitted devices
  $query_permitted = generate_query_permitted();
  /*
  // Convert NOT IN to IN for correctly use indexes
  $devices_permitted = dbFetchColumn('SELECT DISTINCT `device_id` FROM `syslog` WHERE 1 '.$query_permitted, NULL, TRUE);
  $query_permitted = generate_query_values($devices_permitted, 'device_id');
  //r($devices_permitted);
  */

  $query = 'FROM `syslog` ';
  $query .= $where . $query_permitted;
  $query_count = 'SELECT COUNT(*) ' . $query;
  $query_count_approx = 'EXPLAIN SELECT * ' . $query; // Fast approximate count

  $query = 'SELECT * ' . $query;
  $query .= ' ORDER BY `seq` DESC ';
  $query .= "LIMIT $start,$pagesize";

  // Query syslog messages
  $entries = dbFetchRows($query, $param);
  // Query syslog count
  if ($pagination && !$short)
  {
    dbQuery('SET SESSION MAX_EXECUTION_TIME=300;'); // Set 0.3 sec maximum query execution time
    // Exactly count, but it's very SLOW on huge tables
    $count = dbFetchCell($query_count, $param);
    dbQuery('SET SESSION MAX_EXECUTION_TIME=0;'); // Reset maximum query execution time
    //r($count);
    if (!is_numeric($count))
    {
      // Approximate count correctly around 100-80%
      dbQuery('ANALYZE TABLE `syslog`;'); // Update INFORMATION_SCHEMA for more correctly count
      $tmp = dbFetchRow($query_count_approx, $param);
      $count = $tmp['rows'];
    }
  } else {
    $count = count($entries);
  }

  if (!$count)
  {
    // There have been no entries returned. Print the warning.

    print_warning('<h4>No syslog entries found!</h4>
Check that the syslog daemon and Observium configuration options are set correctly, that your devices are configured to send syslog to Observium and that there are no firewalls blocking the messages.

See <a href="'.OBSERVIUM_URL.'/wiki/Category:Documentation" target="_blank">documentation</a> and <a href="'.OBSERVIUM_URL.'/wiki/Configuration_Options#Syslog_Settings" target="_blank">configuration options</a> for more information.');

  } else {
    // Entries have been returned. Print the table.

    $list = array('device' => FALSE, 'priority' => TRUE); // For now (temporarily) priority always displayed
    if (!isset($vars['device']) || empty($vars['device']) || $vars['page'] == 'syslog') { $list['device'] = TRUE; }
    if ($short || !isset($vars['priority']) || empty($vars['priority'])) { $list['priority'] = TRUE; }


    $string = generate_box_open($vars['header']);

    $string .= '<table class="'.OBS_CLASS_TABLE_STRIPED_MORE.'">' . PHP_EOL;
    if (!$short)
    {
      $string .= '  <thead>' . PHP_EOL;
      $string .= '    <tr>' . PHP_EOL;
      $string .= '      <th class="state-marker"></th>' . PHP_EOL;
  #    $string .= '      <th></th>' . PHP_EOL;
      $string .= '      <th>Date</th>' . PHP_EOL;
      if ($list['device']) { $string .= '      <th>Device</th>' . PHP_EOL; }
      if ($list['priority']) { $string .= '      <th>Priority</th>' . PHP_EOL; }
      $string .= '      <th>[Program] [Tags] Message</th>' . PHP_EOL;
      $string .= '    </tr>' . PHP_EOL;
      $string .= '  </thead>' . PHP_EOL;
    }
    $string .= '  <tbody>' . PHP_EOL;

    foreach ($entries as $entry)
    {
      $string .= '  <tr class="'.$priorities[$entry['priority']]['row-class'].'">' . PHP_EOL;
      $string .= '<td class="state-marker"></td>' . PHP_EOL;

      if ($short)
      {
        $string .= '    <td class="syslog text-nowrap">';
        $timediff = $GLOBALS['config']['time']['now'] - strtotime($entry['timestamp']);
        $string .= generate_tooltip_link('', format_uptime($timediff, "short-3"), format_timestamp($entry['timestamp']), NULL) . '</td>' . PHP_EOL;
      } else {
        $string .= '    <td style="width: 130px">';
        $string .= format_timestamp($entry['timestamp']) . '</td>' . PHP_EOL;
      }

      if ($list['device'])
      {
        $dev = device_by_id_cache($entry['device_id']);
        $device_vars = array('page'    => 'device',
                             'device'  => $entry['device_id'],
                             'tab'     => 'logs',
                             'section' => 'syslog');
        $string .= '    <td class="entity">' . generate_device_link($dev, short_hostname($dev['hostname']), $device_vars) . '</td>' . PHP_EOL;
      }
      if ($list['priority'])
      {
        if (!$short) { $string .= '    <td style="width: 95px"><span class="label label-' . $priorities[$entry['priority']]['label-class'] . '">' . nicecase($priorities[$entry['priority']]['name']) . ' (' . $entry['priority'] . ')</span></td>' . PHP_EOL; }
      }
      $entry['program'] = (empty($entry['program'])) ? '[[EMPTY]]' : $entry['program'];
      if ($short)
      {
        $string .= '    <td class="syslog">';
        $string .= '<span class="label label-' . $priorities[$entry['priority']]['label-class'] . '"><strong>' . $entry['program'] . '</strong></span>';
      } else {
        $string .= '    <td>';
        $string .= '<span class="label label-' . $priorities[$entry['priority']]['label-class'] . '">' . $entry['program'] . '</span>';

        /* Show tags if not short */
        $tags = array();
        foreach(explode(',', $entry['tag']) as $tag)
        {
          if (!str_istarts($tag, $entry['program']) &&
              !preg_match('/^(\d+\:|[\da-f]{2})$/i', $tag) &&
              !preg_match('/^<(Emer|Aler|Crit|Err|Warn|Noti|Info|Debu)/i', $tag)) // Skip tags same as program or old numeric tags or syslog-ng 2x hex numbers
          {
            $tags[] = $tag;
          }
        }
        if ($tags)
        {
          $string .= '<span class="label">';
          $string .= implode('</span><span class="label">', $tags);
          $string .= '</span>';
        }
        /* End tags */
      }
      $string .= ' ' . escape_html($entry['msg']) . '</td>' . PHP_EOL;
      $string .= '  </tr>' . PHP_EOL;
    }

    $string .= '  </tbody>' . PHP_EOL;
    $string .= '</table>' . PHP_EOL;

    $string .= generate_box_close();

    // Print pagination header
    if ($pagination && !$short) { $string = pagination($vars, $count) . $string . pagination($vars, $count); }

    // Print syslog
    echo $string;
  }
}

function generate_syslog_form_values($form_filter = FALSE, $column = NULL)
{
  //global $cache;

  $form_items = array();
  $filter = is_array($form_filter); // Use filer or not

  switch ($column)
  {
    case 'priorities':
    case 'priority':
      foreach ($GLOBALS['config']['syslog']['priorities'] as $p => $priority)
      {
        if ($filter && !in_array($p, $form_filter)) { continue; } // Skip filtered entries
        else if ($p > 7)                            { continue; }

        $form_items[$p] = $priority;
        $form_items[$p]['name'] = nicecase($priority['name']);

        switch ($p)
        {
          case 0: // Emergency
          case 1: // Alert
          case 2: // Critical
          case 3: // Error
            $form_items[$p]['class'] = "bg-danger";
            break;
          case 4: // Warning
            $form_items[$p]['class'] = "bg-warning";
            break;
          case 5: // Notification
            $form_items[$p]['class'] = "bg-success";
            break;
          case 6: // Informational
            $form_items[$p]['class'] = "bg-info";
            break;
          case 7: // Debugging
            $form_items[$p]['class'] = "bg-suppressed";
            break;
          default:
            $form_items[$p]['class'] = "bg-disabled";
        }
      }
      krsort($form_items);
      break;
    case 'programs':
    case 'program':
      // Use filter as items
      foreach ($form_filter as $program)
      {
        $name = ($program != '' ? $program : OBS_VAR_UNSET);
        $form_items[$program] = $name;
      }
      break;
  }
  return $form_items;
}

// EOF
