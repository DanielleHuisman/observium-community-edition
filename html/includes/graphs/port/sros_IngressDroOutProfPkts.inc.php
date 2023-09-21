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

$total_units = "B";
$multiplier  = "8";

$graph_title = "Queued Bytes";
$colours     = 'purples';

$ds = 'DroOutProfPkts';

$dir = 'ingress';

include("sros_queues_common.inc.php");

