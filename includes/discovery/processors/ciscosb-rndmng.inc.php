<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

# CISCOSB-rndMng::rlCpuUtilEnable.0 = INTEGER: true(1)
# CISCOSB-rndMng::rlCpuUtilDuringLast5Minutes.0 = INTEGER: 4

$data  = snmp_get_multi_oid($device, 'rlCpuUtilEnable.0 rlCpuUtilDuringLast5Minutes.0', [], $mib);
$usage = $data[0]['rlCpuUtilDuringLast5Minutes'];

if ($data[0]['rlCpuUtilEnable'] == 'true') {
    discover_processor($valid['processor'], $device, ".1.3.6.1.4.1.9.6.1.101.1.9.0", 0, 'ciscosb', 'CPU', 1, $usage);
}

unset($usage, $data);

// EOF
