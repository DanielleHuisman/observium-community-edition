<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) Adam Armstrong
 *
 */

$scale_min = "0";
$scale_max = "1";

$rrd_filename = get_rrd_path($device, "status.rrd");

include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

$rrd_options .= " COMMENT:'         '";
$rrd_options .= " COMMENT:'Current'";
$rrd_options .= " COMMENT:'Historical\\j'";

$rrd_options .= " DEF:status=$rrd_filename_escape:status:AVERAGE";
$rrd_options .= " CDEF:percent=status,100,*";
$rrd_options .= " CDEF:down=status,1,LT,status,UNKN,IF";
$rrd_options .= " CDEF:percentdown=down,100,*";
$rrd_options .= " AREA:percent#00FF0060";
$rrd_options .= " AREA:percentdown#FF000060";
$rrd_options .= " LINE1.5:percent#009900:Availability";
$rrd_options .= " LINE1.5:percentdown#cc0000";
$rrd_options .= " GPRINT:status:LAST:%1.0lf";
$rrd_options .= " GPRINT:percent:AVERAGE:%3.5lf%%\\j";

// EOF
