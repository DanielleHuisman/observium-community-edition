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

// FIXME. move to definitions
if (isset($oid_cache[$processor['processor_oid']])) {
    $proc = $oid_cache[$processor['processor_oid']];
} else {
    $proc = snmp_get_oid($device, 'swCpuUsage.0', 'SW-MIB');
}

// EOF
