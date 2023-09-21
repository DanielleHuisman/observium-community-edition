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

# "5 Sec (7.31%),    1 Min (14.46%),   5 Min (10.90%)"

// FIXME. move to definitions
if (isset($oid_cache[$processor['processor_oid']])) {
    $values = $oid_cache[$processor['processor_oid']];
} else {
    $values = snmp_get_oid($device, 'dellLanExtension.6132.1.1.1.1.4.4.0', 'Dell-Vendor-MIB');
}

preg_match('/5 Sec \((.*)%\),.*1 Min \((.*)%\),.*5 Min \((.*)%\)$/', $values, $matches);

$proc = $matches[3];

// EOF
