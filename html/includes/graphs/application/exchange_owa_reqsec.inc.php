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
$unit_text    = "req/sec";
$rrd_filename = get_rrd_path($device, "wmi-app-exchange-owa.rrd");

if (rrd_is_file($rrd_filename))
{
  $rrd_list[0]['filename'] = $rrd_filename;
  $rrd_list[0]['descr']    = "Requests Per Second";
  $rrd_list[0]['ds']       = "requestspersecond";

} else {
  echo("file missing: $rrd_filename");
}

include($config['html_dir']."/includes/graphs/generic_multi_line.inc.php");

// EOF
