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

$scale_min = 0;

include($config['html_dir'] . "/includes/graphs/common.inc.php");

$graph_max = 1;

$ds = "bsnApIfNoOfUsers";

$colour_area = "B0C4DE";
$colour_line = "191970";

$colour_area_max = "FFEE99";

$nototal   = 1;
$unit_text = "Conns";

include($config['html_dir'] . "/includes/graphs/generic_simplex.inc.php");

// EOF
//<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 *
 *
 * $rrdfile = get_rrd_path($device, "lwappmember-". $member['ap_index_member']. ".rrd");
 *
 * $rrd_list[0]['filename'] = $rrdfile;
 * $rrd_list[0]['descr'] = "Connected Clients";
 * $rrd_list[0]['ds'] = "bsnApIfNoOfUsers";
 *
 * $unit_text = "Clients";
 *
 * $units='';
 * $total_units='';
 * $colours='mixed';
 *
 * $scale_min = "0";
 *
 * $nototal = 1;
 *
 * if ($rrd_list)
 * {
 * include($config['html_dir']."/includes/graphs/generic_multi_line.inc.php");
 * }
 */
// EOF


