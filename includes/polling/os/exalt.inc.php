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
