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

// ExtendAir r5000
// ExtendAir rc13005
// ExtendAir G2 rc11020
// EX-5i
//$hardware = $poll_device['sysDescr'];

$data = snmpwalk_cache_oid($device, 'radioInfo', [], 'ExaltComProducts');

$hardware = $data[0]['modelName'];
[$version] = explode(' ', $data[0]['firmwareVersion']);
$features = $data[0]['interfaceType'];

// EOF
