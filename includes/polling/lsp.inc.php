<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2022 Observium Limited
 *
 */

$table_rows = array();
$query      = 'SELECT * FROM `lsp` WHERE `device_id` = ?';

foreach (dbFetchRows($query, array($device['device_id'])) as $lsp)
{
   // save some previous values
   $lsp_octets       = $lsp['lsp_octets'];
   $lsp_packets      = $lsp['lsp_packets'];
   $lsp_bandwidth    = $lsp['lsp_bandwidth'];
   $lsp_polled       = $lsp['lsp_polled'];
   $lsp_transitions  = $lsp['lsp_transitions'];
   $lsp_path_changes = $lsp['lsp_path_changes'];

   $file = $config['install_dir'] . "/includes/polling/lsp/" . $lsp['lsp_mib'] . ".inc.php";
   if (is_file($file))
   {
      include($file);
   }
   else
   {
      continue;
   }

   if (OBS_DEBUG && count($lsp))
   {
      print_r($lsp);
   }

   $polled_period = $polled - $lsp_polled;
   $octets_diff   = $lsp['lsp_octets'] - $lsp_octets;
   $packets_diff  = $lsp['lsp_packets'] - $lsp_packets;
   $octets_rate   = 0;
   $packets_rate  = 0;
   if ($octets_diff > 0)
   {
      $octets_rate = $octets_diff / $polled_period;
   }
   if ($packets_diff > 0)
   {
      $packets_rate = $packets_diff / $polled_period;
   }

   // rrd naming
   $rrd_filename = "lsp-" . $lsp['lsp_mib'] . '-' . $lsp['lsp_id'] . ".rrd";
   $rrd_uptime   = "lsp-" . $lsp['lsp_mib'] . '-uptime-' . $lsp['lsp_id'] . ".rrd";
   $rrd_stats    = "lsp-" . $lsp['lsp_mib'] . '-stats-' . $lsp['lsp_id'] . ".rrd";

   // uptime graph
   $rrd_ds        = "";
   $uptime_values = array();
   foreach (array('uptime', 'total_uptime', 'primary_uptime') as $ds)
   {
      $rrd_ds          .= "DS:" . $ds . ":GAUGE:600:0:U ";
      $uptime_values[] = $lsp["lsp_{$ds}"];
   }
   if (count($uptime_values))
   {
      rrdtool_create($device, $rrd_uptime, $rrd_ds);
      rrdtool_update($device, $rrd_uptime, $uptime_values);
      $graphs['lsp_uptime'] = TRUE;
   }
   unset($uptime_values);

   // stats graph
   $rrd_ds       = "";
   $stats_values = array();
   foreach (array('transitions', 'path_changes') as $ds)
   {
      $rrd_ds         .= "DS:" . $ds . ":COUNTER:600:0:U ";
      $stats_values[] = $lsp["lsp_{$ds}"];
   }
   if (count($stats_values))
   {
      rrdtool_create($device, $rrd_stats, $rrd_ds);
      rrdtool_update($device, $rrd_stats, $stats_values);
      $graphs['lsp_stats'] = TRUE;
   }
   unset($stats_values);

   // octets/packets/bandwidth graph
   $lsp_values   = array();
   $rrd_ds       = "DS:bandwidth:GAUGE:600:0:U ";
   $lsp_values[] = $lsp['lsp_bandwidth']; // should always get here in bps
   foreach (array('octets', 'packets') as $ds)
   {
      $rrd_ds       .= "DS:" . $ds . ":COUNTER:600:0:" . $config['max_port_speed'] . ' ';
      $lsp_values[] = $lsp["lsp_{$ds}"];
   }
   if (count($lsp_values))
   {
      rrdtool_create($device, $rrd_filename, $rrd_ds);
      rrdtool_update($device, $rrd_filename, $lsp_values);
      $graphs['lsp_bits'] = TRUE;
      $graphs['lsp_pkts'] = TRUE;
   }
   unset($lsp_values);

   $update = dbUpdate(array('lsp_polled'         => $polled,
                            'lsp_octets'         => $lsp['lsp_octets'],
                            'lsp_packets'        => $lsp['lsp_packets'],
                            'lsp_bandwidth'      => $lsp['lsp_bandwidth'],
                            'lsp_transitions'    => $lsp['lsp_transitions'],
                            'lsp_path_changes'   => $lsp['lsp_path_changes'],
                            'lsp_uptime'         => $lsp['lsp_uptime'],
                            'lsp_total_uptime'   => $lsp['lsp_total_uptime'],
                            'lsp_primary_uptime' => $lsp['lsp_primary_uptime'],
                            'lsp_octets_rate'    => $octets_rate,
                            'lsp_packets_rate'   => $packets_rate), 'lsp', '`lsp_id` = ?', array($lsp['lsp_id']));

   // event logs
   if ($lsp_bandwidth != $lsp['lsp_bandwidth'])
   {
      log_event($lsp['lsp_proto'] . ' LSP bandwidth changed: ' . formatRates($lsp_bandwidth) . ' -> ' . formatRates($lsp['lsp_bandwidth']) . ' (' . $lsp['lsp_name'] . ')', $device, 'lsp', $lsp['lsp_id']);
   }
   if ($lsp_transitions < $lsp['lsp_transitions'])
   {
      log_event($lsp['lsp_proto'] . ' LSP transitioned (' . $lsp['lsp_name'] . ')', $device, 'lsp', $lsp['lsp_id']);
   }
   if ($lsp_path_changes < $lsp['lsp_path_changes'])
   {
      log_event($lsp['lsp_proto'] . ' LSP changed path (' . $lsp['lsp_name'] . ')', $device, 'lsp', $lsp['lsp_id']);
   }

   // Check alerts
   check_entity('lsp', $lsp, array('lsp_octets_rate' => $octets_rate, 'lsp_packets_rate' => $packets_rate, 'lsp_bandwidth' => $lsp['lsp_bandwidth']));

   $table_row    = array();
   $table_row[]  = $lsp['lsp_name'];
   $table_row[]  = strtoupper($lsp['lsp_mib']);
   $table_row[]  = $lsp['lsp_proto'];
   $table_row[]  = $lsp['lsp_index'];
   $table_rows[] = $table_row;
   unset($table_row);
}

$headers = array('%WName%n', '%WMIB%n', '%WProto%n', '%WIndex%n');
print_cli_table($table_rows, $headers);

unset($lsps_cache, $lsp, $table, $table_row, $table_rows);

// EOF
