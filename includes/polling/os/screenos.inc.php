<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) Adam Armstrong
 *
 */

// FIXME move to graph definitions
$snmpdata = snmp_get_multi_oid($device, 'nsResSessAllocate.0 nsResSessMaxium.0 nsResSessFailed.0', [], 'NETSCREEN-RESOURCE-MIB');

rrdtool_update_ng($device, 'screenos-sessions', [
  'allocate' => $snmpdata[0]['nsResSessAllocate'],
  'max'      => $snmpdata[0]['nsResSessMaxium'],
  'failed'   => $snmpdata[0]['nsResSessFailed'],
]);

$graphs['screenos_sessions'] = TRUE;

// EOF
