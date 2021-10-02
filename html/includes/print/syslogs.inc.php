<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
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
 * @return null
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
          $value = get_var_csv($value);
          foreach ($value as $k => $v)
          {
            // Rewrite priority strings to numbers
            $value[$k] = priority_string_to_numeric($v);
          }
          $where .= generate_query_values($value, $var);
          break;
        case 'tag':
        case 'program':
          $value = get_var_csv($value);
          $where .= generate_query_values($value, $var);
          break;
        case 'message':
          if (preg_match('/^!(=)?\s*(?<msg>.+)/', $value, $matches)) {
            $where .= generate_query_values($matches['msg'], 'msg', '%!=LIKE%');
          } else {
            $where .= generate_query_values($value, 'msg', '%LIKE%');
          }
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
    dbSetVariable('MAX_EXECUTION_TIME', 500); // Set 0.5 sec maximum query execution time
    // Exactly count, but it's very SLOW on huge tables
    $count = dbFetchCell($query_count, $param);
    dbSetVariable('MAX_EXECUTION_TIME', 0); // Reset maximum query execution time
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

See <a href="'.OBSERVIUM_DOCS_URL.'/syslog/" target="_blank">Syslog Integration</a> guide and <a href="'.OBSERVIUM_DOCS_URL.'/config_options/#syslog-settings" target="_blank">configuration options</a> for more information.');

  } else {
    // Entries have been returned. Print the table.

    $list = array('device' => FALSE, 'priority' => TRUE); // For now (temporarily) priority always displayed
    if (!isset($vars['device']) || empty($vars['device']) || $vars['page'] === 'syslog') { $list['device'] = TRUE; }
    if ($short || !isset($vars['priority']) || empty($vars['priority'])) { $list['priority'] = TRUE; }


    $string = generate_box_open($vars['header']);
    if((isset($vars['short']) && $vars['short'])) {
        $string .= '<table class="' . OBS_CLASS_TABLE_STRIPED_MORE . '">' . PHP_EOL;
    } else {
        $string .= '<table class="' . OBS_CLASS_TABLE_STRIPED . '">' . PHP_EOL;

    }
    // Generate table header
    if (!$short)
    {
      $cols = [];
      $cols[]              = [ NULL, 'class="state-marker"' ];
      //$cols[]              = [ NULL, 'class="no-width"' ]; // Measured entity link
      $cols[]              = [ 'Date' ];
      if ($list['device'])
      {
        //$cols['device']    = [ 'Device' ];
        $cols[]            = [ 'Device' ];
      }
      if ($list['priority'])
      {
        //$cols['priority']  = [ 'Priority' ];
        $cols[]            = [ 'Priority' ];
      }
      $cols[]              = [ '[Program] [Tags] Message' ];

      $string .= get_table_header($cols, $vars);
    }

    // Table body
    $string .= '  <tbody>' . PHP_EOL;
    foreach ($entries as $entry)
    {
      $string .= generate_syslog_row($entry, $vars, $list);
    }
    //print_vars($GLOBALS['cache']['syslog']);
    $string .= '  </tbody>' . PHP_EOL;

    $string .= '</table>' . PHP_EOL;

    $string .= generate_box_close();

    // Print pagination header
    if ($pagination && !$short) { $string = pagination($vars, $count) . $string . pagination($vars, $count); }

    // Print syslog
    echo $string;
  }
}

function generate_syslog_row($entry, $vars, $list = NULL)
{
  // Short events? (no pagination, small out)
  $short = (isset($vars['short']) && $vars['short']);
  $priorities = $GLOBALS['config']['syslog']['priorities'];
  $is_alert = isset($entry['la_id']); // This is syslog alert entry?

  // List of displayed columns
  if (is_null($list))
  {
    $list = [ 'device' => FALSE, 'priority' => TRUE ]; // For now (temporarily) priority always displayed
    if (!isset($vars['device']) || empty($vars['device']) || $vars['page'] == 'syslog')
    {
      $list['device'] = TRUE;
    }
    if ($short || !isset($vars['priority']) || empty($vars['priority']))
    {
      $list['priority'] = TRUE;
    }
  }

  $row_class = strlen($entry['html_row_class']) ? $entry['html_row_class'] : $priorities[$entry['priority']]['row-class'];

  $string = '  <tr class="'.$row_class.'">' . PHP_EOL;
  $string .= '<td class="state-marker"></td>' . PHP_EOL;
  $timediff = $GLOBALS['config']['time']['now'] - strtotime($entry['timestamp']);

  if ($short || $timediff < 3600)
  {
    $string .= '    <td class="syslog text-nowrap">';
    $timediff = $GLOBALS['config']['time']['now'] - strtotime($entry['timestamp']);
    $string .= generate_tooltip_link('', format_uptime($timediff, "short-3"), format_timestamp($entry['timestamp']), NULL) . '</td>' . PHP_EOL;
  } else {
    //$string .= '    <td style="width: 130px">';
    $string .= '    <td>';
    $string .= format_timestamp($entry['timestamp']) . '</td>' . PHP_EOL;
  }

  // Device column
  if ($list['device'])
  {
    $dev = device_by_id_cache($entry['device_id']);
    $device_vars = array('page'    => 'device',
                         'device'  => $entry['device_id'],
                         'tab'     => 'logs',
                         'section' => 'syslog');
    if ($is_alert) { $device_vars['section'] = 'logalert'; }
    $string .= '    <td class="entity">' . generate_device_link($dev, short_hostname($dev['hostname']), $device_vars) . '</td>' . PHP_EOL;
  }

  // Alert Rule column (in syslog alerts)
  if ($list['la_id'])
  {
    $syslog_rules = $GLOBALS['cache']['syslog']['syslog_rules']; // Cached syslog rules
    $string .= '<td><strong><a href="'.generate_url(array('page' => 'syslog_rules', 'la_id' => $entry['la_id'])).'">' .
               (is_array($syslog_rules[$entry['la_id']]) ? $syslog_rules[$entry['la_id']]['la_name'] : 'Rule Deleted')  . '</td>' . PHP_EOL;
  }

  // Priority column
  if ($list['priority'])
  {
    if (!$short)
    {
      $string .= '    <td style="width: 95px"><span class="label label-' . $priorities[$entry['priority']]['label-class'] . '">' .
                 nicecase($priorities[$entry['priority']]['name']) . ' (' . $entry['priority'] . ')</span></td>' . PHP_EOL;
    }
  }

  // Program and Tags column
  $entry['program'] = (empty($entry['program'])) ? '[[EMPTY]]' : $entry['program'];
  if ($short)
  {
    $string .= '    <td class="syslog">';
    $string .= '<span class="label label-' . $priorities[$entry['priority']]['label-class'] . '"><strong>' . escape_html($entry['program']) . '</strong></span>';
  } else {
    $string .= '    <td>';
    $string .= '<span class="label label-' . $priorities[$entry['priority']]['label-class'] . '">' . escape_html($entry['program']) . '</span>';

    /* Show tags if not short */
    $tags = array();
    foreach(explode(',', $entry['tag']) as $tag)
    {
      if (!str_istarts($tag, $entry['program']) &&
          !preg_match('/^(\d+\:|[\da-f]{2})$/i', $tag) &&
          !preg_match('/^<(Emer|Aler|Crit|Err|Warn|Noti|Info|Debu)/i', $tag)) // Skip tags same as program or old numeric tags or syslog-ng 2x hex numbers
      {
        $tags[] = escape_html($tag);
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
  if ($list['program'])
  {
    // Program in separate column (from message)
    $string .= $short ? '</td><td class="syslog">' : '</td><td>';
  }

  // Link with syslog ports cache
  if (!isset($GLOBALS['cache']['syslog']['ports_links'])) {
    $GLOBALS['cache']['syslog']['ports_links'] = [];
  }
  $ports_links = &$GLOBALS['cache']['syslog']['ports_links'];

  // Highlight port links
  if (!isset($ports_links[$entry['device_id']])) {
    $ports_links[$entry['device_id']] = [];
    $sql = 'SELECT `port_id`, `port_label_short`, `port_label_base`, `port_label_num`, `ifDescr`, `ifName` FROM `ports` WHERE `device_id` = ? AND `deleted` = ?';
    foreach (dbFetchRows($sql, [ $entry['device_id'], 0 ]) as $port_descr) {
      $search = [ $port_descr['ifDescr'], $port_descr['ifName'], $port_descr['port_label_short'] ];
      // FIXME. Currently as hack for Extreme (should make universal with lots of examples), see:
      // https://jira.observium.org/browse/OBS-3304
      if (preg_match('/\s(port\s*\d.*)/i', $port_descr['ifDescr'], $matches)) {
        $search[] = $matches[1];
      } elseif (strlen($port_descr['port_label_base']) && str_contains($port_descr['port_label_num'], '/')) {
        // Brocade NOS derp interfaces with rbridge ids, ie:
        // TenGigabitEthernet 22/0/20 or Te 22/0/20 -> TenGigabitEthernet 0/20
        $search[] = $port_descr['port_label_base'] . '\d+/' . $port_descr['port_label_num'];
        // and short
        $search[] = short_ifname($port_descr['port_label_base'] . '\d+/' . $port_descr['port_label_num']);
      }
      $ports_links[$entry['device_id']][$port_descr['port_id']] = [
        'search'  => $search,
        'replace' => generate_entity_link('port', $port_descr['port_id'], '$2')
      ];
    }
  }
  $entity_links = $ports_links[$entry['device_id']];

  // Highlight bgp peer links (try only when program match BGP)
  if (str_icontains_array($entry['program'], 'bgp'))
  {

    // Link with syslog bgp cache
    if (!isset($GLOBALS['cache']['syslog']['bgp_links']))
    {
      $GLOBALS['cache']['syslog']['bgp_links'] = [];
    }
    $bgp_links = &$GLOBALS['cache']['syslog']['bgp_links'];

    if (!isset($bgp_links[$entry['device_id']]))
    {
      $bgp_links[$entry['device_id']] = [];
      //SELECT `bgpPeer_id`, `bgpPeerRemoteAs`, `bgpPeerIdentifier`, `bgpPeerRemoteAddr` FROM `bgpPeers` WHERE `device_id` = 2
      foreach (dbFetchRows('SELECT * FROM `bgpPeers` WHERE `device_id` = ?', [ $entry['device_id'] ]) as $bgp_descr)
      {
        $search = [];
        foreach ([ 'bgpPeerIdentifier', 'bgpPeerRemoteAddr' ] as $param)
        {
          if ($bgp_descr[$param] === '0.0.0.0') { continue; }

          $search[] = 'Nbr ' . $bgp_descr[$param];
          $search[] = 'Neighbor ' . $bgp_descr[$param];
          if (get_ip_version($bgp_descr[$param]) == 6)
          {
            // For IPv6 append compressed form
            $bgp_descr[$param] = Net_IPv6::compress($bgp_descr[$param], TRUE);
            $search[] = 'Nbr ' . $bgp_descr[$param];
            $search[] = 'Neighbor ' . $bgp_descr[$param];
          }
        }
        $bgp_links[$entry['device_id']][] = [ 'search'  => $search,
                                              'replace' => generate_entity_link('bgp_peer', $bgp_descr, '$2') ];
        // Additionally append AS text
        if ($bgp_descr['astext'] && !isset($bgp_links[$entry['device_id']]['as'.$bgp_descr['bgpPeerRemoteAs']]))
        {
          $bgp_links[$entry['device_id']]['as'.$bgp_descr['bgpPeerRemoteAs']] = [
            'search'  => [ 'AS ' . $bgp_descr['bgpPeerRemoteAs'], 'AS: ' . $bgp_descr['bgpPeerRemoteAs'], 'AS' . $bgp_descr['bgpPeerRemoteAs'] ],
            'replace' => generate_tooltip_link('', '$2', $bgp_descr['astext'])
          ];
        }
      }
    }
    $entity_links = array_merge($entity_links, $bgp_links[$entry['device_id']]);
  }

  // Linkify entities in syslog messages
  if (isset($entry['msg']) && !isset($entry['message']))
  {
    // Different field in syslog alerts and syslog
    $entry['message'] = $entry['msg'];
  }

  // Restore escaped quotes (for old entries)
  $entry['message'] = str_replace([ '\"', "\'" ], [ '"', "'" ], $entry['message']);

  $string .= ' ' . html_highlight(escape_html($entry['message']), $entity_links, NULL, TRUE) . '</td>' . PHP_EOL;
  //$string .= ' ' . escape_html($entry['msg']) . '</td>' . PHP_EOL;

  // if (!$short)
  // {
  //   //$string .= '<td>' . escape_html($entry['log_type']) . '</td>' . PHP_EOL;
  //   //$string .= '<td style="text-align: right">'. ($entry['notified'] == '1' ? '<span class="label label-success">YES</span>' : ($entry['notified'] == '-1' ? '<span class="label">SKIP</span>' : '<span class="label label-warning">NO</span>')) . '</td>' . PHP_EOL;
  // }

  $string .= '  </tr>' . PHP_EOL;

  return $string;
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
        if ($p > 7)                                 { continue; }

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
