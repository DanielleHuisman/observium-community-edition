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

$links = 1;

include_once("../includes/sql-config.inc.php");

include($config['html_dir'] . "/includes/functions.inc.php");
include($config['html_dir'] . "/includes/authenticate.inc.php");

if ($_SESSION['authenticated']) {
  // Do various queries which we use in multiple places
  include($config['html_dir'] . "/includes/cache-data.inc.php");

  if (!is_file($config['dot'])) {
    print_error("Package 'graphviz' not installed. Map can not be displayed!");
    return;
  }
} else {
  // not authenticated
  exit;
}

$vars = get_vars('GET');

if (strpos($_SERVER['REQUEST_URI'], "anon")) { $anon = 1; }

// FIXME. WHAT THE HELL THERE?
if (is_array($config['branding']))
{
  if ($config['branding'][$_SERVER['SERVER_NAME']])
  {
    foreach ($config['branding'][$_SERVER['SERVER_NAME']] as $confitem => $confval)
    {
      eval("\$config['" . $confitem . "'] = \$confval;");
    }
  } else {
    foreach ($config['branding']['default'] as $confitem => $confval)
    {
      eval("\$config['" . $confitem . "'] = \$confval;");
    }
  }
}

$sql = 'SELECT `devices`.*, COUNT(`neighbours`.`port_id`) AS `neighbours_count` FROM `devices` LEFT JOIN `neighbours` USING(`device_id`) ';
//'WHERE `neighbours`.`active` = '1' AND `device_id` = 286 GROUP BY `device_id` ORDER BY COUNT(`neighbours`.`port_id`) DESC ';
$where = 'WHERE `neighbours`.`active` = ?';
$params = [ 1 ];
if (isset($vars['device'])) {
  if (device_permitted($vars['device'])) {
    $where .= ' AND `device_id` = ?';
    $params[] = $vars['device'];
  } else {
    // not permitted
    print_error_permission("Device not permitted");
    return;
  }
  //$where = "WHERE D.`device_id` = ".$vars['device'];
} else {
  $where .= $cache['where']['devices_permitted'];
}
//$where .= " AND L.`active` = '1'";

// FIXME this shit probably needs tidied up.

$format = isset($vars['format']) && in_array($vars['format'], [ 'dot', 'png', 'svg' ]) ? $vars['format'] : 'svg';

#  $map = 'digraph G { bgcolor=transparent; splines=true; overlap=scale; concentrate=0; epsilon=0.001; rankdir=LR
  $map = 'digraph G { bgcolor=transparent; splines=true; overlap=scale; rankdir=LR
     node [ fontname="helvetica", fontstyle=bold, style=filled, color=white, fillcolor=lightgrey, overlap=false];
     edge [ bgcolor=white, fontname="helvetica", fontstyle=bold, arrowhead=dot, arrowtail=dot];
     graph [bgcolor=transparent;];

';

  if (!$_SESSION['authenticated']) {
    // never will happen this if
    $map .= "\"Not authenticated\" [fontsize=20 fillcolor=\"lightblue\", URL=\"/\" shape=box3d]\n";
  } else {
    $loc_count = 1;
    $ranks = [];

    $cache['where']['devices_permitted'] = generate_query_permitted(array('device'), array('device_table' => 'D'));
    foreach (dbFetchRows($sql . $where . ' GROUP BY `device_id` ORDER BY `neighbours_count` DESC', $params) as $device) {
    //foreach (dbFetch("SELECT D.*, COUNT(L.`port_id`) FROM `devices` AS D LEFT JOIN (`ports` AS I, `neighbours` AS L) ON (D.`device_id` = I.`device_id` AND I.port_id = L.port_id) ". $where . $cache['where']['devices_permitted'] . " GROUP BY D.hostname ORDER BY COUNT(L.port_id) DESC", NULL, TRUE) as $device)
    //{

      $links = dbFetchRows('SELECT * FROM `neighbours` LEFT JOIN `ports` USING(`device_id`, `port_id`) WHERE `device_id` = ? AND `active` = ? ORDER BY `remote_hostname`', [ $device['device_id'], 1 ]);
      $links_group = [];
      foreach ($links as $index => $link) {
        //$links_group[$link['device_id']][$link['port_id']][$link['remote_port']][$index] = $link['remote_hostname'] . '[' . nicecase($link['protocol']) . ']';
        $links_group[$link['device_id']][$link['port_id']][$link['remote_port']]++;
      }
      //$links = dbFetch("SELECT * FROM ports AS I, neighbours AS L WHERE I.device_id = ? AND L.port_id = I.port_id ORDER BY L.remote_hostname", array($device['device_id']));
      if (safe_count($links)) {
        $ranktype = substr($device['hostname'], 0, 2);
        //$ranktype2 = substr($device['hostname'], 0, 3);
        //if (!strncmp($device['hostname'], "c", 1) && !strstr($device['hostname'], "kalooga"))
        //{
          $ranks[$ranktype][] = $device['hostname'];
        //} else {
        //  $ranks[$ranktype2][] = $device['hostname'];
        //}
        if ($anon) { $device['hostname'] = md5($device['hostname']); }
        if (!isset($locations[$device['location']])) {
          $locations[$device['location']] = $loc_count;
          $loc_count++;
        }
        #$loc_id = $locations[$device['location']];
        $loc_id = '"'.$ranktype.'"';

        $map .= "\"".$device['hostname']."\" [fontsize=20, fillcolor=\"lightblue\", group=".$loc_id." URL=\"".generate_url(array('page' => 'device', 'device' => $device['device_id'], 'tab' => 'ports', 'view' => 'map'))."\" shape=box3d]\n";
      }

      foreach ($links as $link) {
        $local_port_id = $link['port_id'];
        $remote_port_id = $link['remote_port_id'];

        // skip already done (groupped) links
        // FIXME. Not done
        $group_count = $links_group[$link['device_id']][$link['port_id']][$link['remote_port']];
        if (!$group_count) {
          continue;
        }
        $links_group[$link['device_id']][$link['port_id']][$link['remote_port']]--;
        /*
        if ($group_count > 1) {
          $protocols = implode(",\n", $links_group[$link['device_id']][$link['port_id']][$link['remote_port']]);
        } else {
          $protocols = '';
        }
        unset($links_group[$link['device_id']][$link['port_id']][$link['remote_port']]);
        */

        $i = 0;
        $done = 0;
        if ($linkdone[$remote_port_id][$local_port_id]) {
          $done = 1;
        }

        if (!$done) {
          $linkdone[$local_port_id][$remote_port_id] = TRUE;

          $i++;

          if ($link['ifSpeed'] >= "10000000000") {
            $info = "color=red3 style=\"setlinewidth(4)\"";
          } elseif ($link['ifSpeed'] >= "1000000000") {
            $info = "color=lightblue style=\"setlinewidth(3)\"";
          } elseif ($link['ifSpeed'] >= "100000000") {
            $info = "color=lightgrey style=\"setlinewidth(2)\"";
          } elseif ($link['ifSpeed'] >= "10000000") {
            $info = "style=\"setlinewidth(1)\"";
          } else {
            $info = "style=\"setlinewidth(1)\"";
          }

          $src = $device['hostname'];
          if ($remote_port_id && $remote_device_id = get_device_id_by_port_id($remote_port_id)) {
            $remote_device = device_by_id_cache($remote_device_id);
            //$dst_query = dbFetchRow("SELECT D.`device_id`, `hostname` FROM `devices` AS D, `ports` AS I WHERE I.`port_id` = ? AND D.`device_id` = I.`device_id`".$cache['where']['devices_permitted'], array($remote_port_id));
            $dst       = $remote_device['hostname'];
            $dst_host  = $remote_device['device_id'];
          } else {
            unset($dst_host);
            $dst = $link['remote_hostname'];
          }

          // anonymize
          if ($anon) {
            $dst = md5($dst);
            $src = md5($src);
          }

          if (!port_permitted($link['port_id'])) { continue; }
          $sif = get_port_by_id_cache($link['port_id']);
          if ($remote_port_id && port_permitted($link['remote_port_id'])) {
            $dif = get_port_by_id_cache($link['remote_port_id']);
          } else {
            $dif['port_label'] = $link['remote_port'];
            $dif['port_id'] = $link['remote_hostname'] . '/' . $link['remote_port'];
          }

          if (!is_numeric($device['device_id'])) {
            if (!$ifdone[$dst][$dif['port_id']] && !$ifdone[$src][$sif['port_id']])
            {
              $map .= "\"$src\" -> \"" . $dst . "\" [weight=500000, arrowsize=0, len=0, $info];\n";
            }
            $ifdone[$src][$sif['port_id']] = 1;
          } else {
            $map .= "\"" . $sif['port_id'] . "\" [label=\"" . $sif['port_label'] . "\", fontsize=12, fillcolor=lightblue, URL=\"".generate_url(array('page' => 'device', 'device' => $device['device_id'],'tab' => 'port', 'port' => $local_port_id))."\"]\n";
            if (!$ifdone[$src][$sif['port_id']]) {
              $map .= "\"$src\" -> \"" . $sif['port_id'] . "\" [weight=500000, arrowsize=0, len=0];\n";
              $ifdone[$src][$sif['port_id']] = 1;
            }

            if ($dst_host)
            {
              $map .= "\"$dst\" [URL=\"".generate_url(array('page' => 'device', 'device' => $dst_host,'tab' => 'ports', 'view' => 'map'))."\", fontsize=20, shape=box3d]\n";
            } else {
              $map .= "\"$dst\" [ fontsize=20 shape=box3d]\n";
            }

            if ($dst_host == $device['device_id'] || !is_numeric($device['device_id']))
            {
              $map .= "\"" . $dif['port_id'] . "\" [label=\"" . $dif['port_label'] . "\", fontsize=12, fillcolor=lightblue, URL=\"".generate_url(array('page' => 'device', 'device' => $dst_host,'tab' => 'port', 'port' => $remote_port_id))."\"]\n";
            } else {
              $map .= "\"" . $dif['port_id'] . "\" [label=\"" . $dif['port_label'] . " \", fontsize=12, fillcolor=lightgray";
              if ($dst_host)
              {
                $map .= ", URL=\"".generate_url(array('page' => 'device', 'device' => $dst_host,'tab' => 'port', 'port' => $remote_port_id))."\"";
              }
              $map .= "]\n";
            }

            if (!$ifdone[$dst][$dif['port_id']]) {
              $map .= "\"" . $dif['port_id'] . "\" -> \"$dst\" [weight=500000, arrowsize=0, len=0];\n";
              $ifdone[$dst][$dif['port_id']] = 1;
            }
            $map .= "\"" . $sif['port_id'] . "\" -> \"" . $dif['port_id'] . "\" [weight=1, arrowhead=normal, arrowtail=normal, len=2, $info] \n";
          }
        }
      }

      $done = 0;

    }
  }

  foreach ($ranks as $rank)
  {
    if (substr($rank[0], 0, 2) == "cr")
    {
      $map .= '{ rank=min; "' . implode('"; "', $rank) . "\"};\n";
    } else {
      $map .= '{ rank=same; "' . implode('"; "', $rank) . "\"};\n";
    }
  }

  $map .= "\n};";

/*
  if ($links > 30) // Unflatten if there are more than 10 links. beyond that it gets messy
  {
    $maptool = $config['unflatten'];
  } else {
*/
    $maptool = $config['dot'];
/*
  }

  if ($where == '')
  {
#    $maptool = $config['unflatten'] . ' -f -l 5 | ' . $config['sfdp'] . ' -Gpack -Goverlap=prism -Gcharset=latin1 | dot';
#    $maptool = $config['sfdp'] . ' -Gpack -Goverlap=prism -Gcharset=latin1 -Gsize=20,20';
     $maptool = $config['dot'];
  }
*/

  switch ($format) {
    case 'dot':
      $img = "<pre>\n$map\n</pre>";
      break;
    case 'svg':
      //$img = external_exec($maptool . ' -T' . $format);
      $process = pipe_open($maptool . ' -T' . $format, $pipes);
      if (is_resource($process)) {
        $img = pipe_read($map, $pipes);
        $img = str_replace("<a ", '<a target="_parent" ', $img);
      }
      pipe_close($process, $pipes);
      header("Content-type: image/svg+xml");
      break;
    case 'png':
    default:
      $format = 'png:gd';
      $img = external_exec($maptool . ' -T' . $format);
      $process = pipe_open($maptool . ' -T' . $format, $pipes);
      if (is_resource($process)) {
        $img = pipe_read($map, $pipes);
      }
      pipe_close($process, $pipes);
      header("Content-type: image/png");
      break;
  }

  echo($img);

// EOF
