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

// EdgeSwitch-SWITCHING-MIB::agentSwitchCpuProcessTotalUtilization.0 = STRING: "    5 Secs ( 99.9999%)   60 Secs ( 99.6358%)  300 Secs ( 99.2401%)"

// FIXME. move to definitions
if (isset($oid_cache[$processor['processor_oid']])) {
    $data = $oid_cache[$processor['processor_oid']];
} else {
    $data = snmp_get_oid($device, 'agentSwitchCpuProcessTotalUtilization.0', 'EdgeSwitch-SWITCHING-MIB');
}

if (preg_match('/300 Secs \(\s*(?<proc>[\d\.]+)%\)/', $data, $matches)) {
    $proc = $matches['proc'];
}

unset($data, $matches);

// EOF
