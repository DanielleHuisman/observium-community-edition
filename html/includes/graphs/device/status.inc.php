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

$scale_min = "0";
$scale_max = "1";

$rrd_filename = get_rrd_path($device, "status.rrd");

include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

$rrd_options .= " COMMENT:'                                Cur    Avail\\n'";
$rrd_options .= " DEF:status=$rrd_filename_escape:status:AVERAGE";
$rrd_options .= " CDEF:percent=status,100,*";
$rrd_options .= " CDEF:down=status,1,LT,status,UNKN,IF";
$rrd_options .= " CDEF:percentdown=down,100,*";
$rrd_options .= " AREA:percent#00FF0060";
$rrd_options .= " AREA:percentdown#FF000060";
$rrd_options .= " LINE1.5:percent#009900:Status";
$rrd_options .= " LINE1.5:percentdown#cc0000";
$rrd_options .= " GPRINT:status:LAST:%3.0lf";
$rrd_options .= " GPRINT:percent:AVERAGE:%3.5lf%%\\l";

// EOF
