<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

include_once($config['html_dir']."/includes/graphs/common.inc.php");

$colours      = "mixed";
$nototal      = 1;
$unit_text    = "Queues";
$rrd_filename = get_rrd_path($device, "wmi-app-exchange-smtp.rrd");

if (rrd_is_file($rrd_filename))
{
  $rrd_list[0]['filename'] = $rrd_filename;
  $rrd_list[0]['descr']    = "Current Connections";
  $rrd_list[0]['ds']       = "currentconnections";

  $rrd_list[1]['filename'] = $rrd_filename;
  $rrd_list[1]['descr']    = "Messages Sent per Second";
  $rrd_list[1]['ds']       = "msgsentpersec";

} else {
  echo("file missing: $rrd_filename");
}

include($config['html_dir']."/includes/graphs/generic_multi_line.inc.php");

// EOF
