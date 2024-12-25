<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) Adam Armstrong
 *
 */

$mempool['perc'] = snmp_get_oid($device, "pmEqptShelfPm-recordTime-period-indexPm-data-value.$index", 'FSS-PM');

// EOF
