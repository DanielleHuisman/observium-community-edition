<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$mysql_rrd = get_rrd_path($device, "app-mysql-" . $app['app_id'] . ".rrd");

if (rrd_is_file($mysql_rrd)) {
    $rrd_filename = $mysql_rrd;
}

include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

$rrd_options .= ' DEF:a=' . $rrd_filename_escape . ':IDBLBSe:AVERAGE ';
$rrd_options .= ' DEF:b=' . $rrd_filename_escape . ':IBLFh:AVERAGE ';
$rrd_options .= ' DEF:c=' . $rrd_filename_escape . ':IBLWn:AVERAGE ';

$rrd_options .= 'COMMENT:"            Current    Average   Maximum\n" ';

$rrd_options .= 'AREA:a#FAFD9E:"Buffer Size  "      ';
$rrd_options .= 'GPRINT:a:LAST:"%6.2lf %s"  ';
$rrd_options .= 'GPRINT:a:AVERAGE:"%6.2lf %s"  ';
$rrd_options .= 'GPRINT:a:MAX:"%6.2lf %s\\n"  ';

$rrd_options .= 'LINE1:b#22FF22:"KB Flushed "  ';
$rrd_options .= 'GPRINT:b:LAST:"%6.2lf %s"  ';
$rrd_options .= 'GPRINT:b:AVERAGE:"%6.2lf %s"  ';
$rrd_options .= 'GPRINT:b:MAX:"%6.2lf %s\\n"  ';

$rrd_options .= 'LINE1:c#0022FF:"KB Written  "  ';
$rrd_options .= 'GPRINT:c:LAST:"%6.2lf %s"  ';
$rrd_options .= 'GPRINT:c:AVERAGE:"%6.2lf %s"  ';
$rrd_options .= 'GPRINT:c:MAX:"%6.2lf %s\\n"  ';

// EOF
