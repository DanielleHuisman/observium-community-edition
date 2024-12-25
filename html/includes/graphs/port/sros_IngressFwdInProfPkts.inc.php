<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     graphs
 * @copyright  (C) Adam Armstrong
 *
 */

$total_units = "B";
$multiplier  = "8";

$graph_title = "Queued Bytes";
$colours     = 'purples';

$ds = 'FwdInProfPkts';

$dir = 'ingress';

include("sros_queues_common.inc.php");

