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
    $load = $oid_cache[$processor['processor_oid']];
} else {
    $load = snmp_get_oid($device, '.1.3.6.1.4.1.6141.2.60.12.1.7.4.0');
}
$proc = (float)$load / 100;

// EOF
