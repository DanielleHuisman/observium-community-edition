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

$rrd_filename = get_rrd_path($device, "drum.rrd");

include_once($config['html_dir']."/includes/graphs/common.inc.php");

$rrd_options .= " -l 0 -E ";

if (rrd_is_file($rrd_filename))
{
  $rrd_options .= " COMMENT:'                           Cur   Min  Max\\n'";

  $rrd_options .= " DEF:drum=".$rrd_filename_escape.":drum:AVERAGE ";
  $rrd_options .= " LINE1:drum#CC0000:'Imaging Drum         ' ";
  $rrd_options .= " GPRINT:drum:LAST:%3.0lf%% ";
  $rrd_options .= " GPRINT:drum:MIN:%3.0lf%% ";
  $rrd_options .= " GPRINT:drum:MAX:%3.0lf%%\\\l ";
}

// EOF
