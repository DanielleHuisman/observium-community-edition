<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// FIXME. Move to polling graphs
$workq_depth = snmp_get($device, 'workQueueMessages.0', '-Ovq', 'ASYNCOS-MAIL-MIB');
if (is_numeric($workq_depth)) {
    rrdtool_update_ng($device, 'asyncos-workq', ['DEPTH' => $workq_depth]);
    //echo("Work Queue: $workq_depth\n");
    $graphs['asyncos_workq'] = TRUE;
}

// EOF
