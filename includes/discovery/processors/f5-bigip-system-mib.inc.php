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

$tmm_processor = snmpwalk_cache_oid($device, 'sysTmmStatTmUsageRatio5m', [], $mib);

foreach ($tmm_processor as $index => $entry) {
    $oid   = ".1.3.6.1.4.1.3375.2.1.8.2.3.1.39." . snmp_string_to_oid($index);
    $descr = "TMM $index Processor";
    $usage = $entry['sysTmmStatTmUsageRatio5m'];

    // FIXME, when will converted to definition-based, note that here used "named" index instead numeric
    discover_processor($valid['processor'], $device, $oid, $index, 'f5-bigip-tmm', $descr, 1, $usage);
}

unset ($tmm_processor, $index, $used);

// EOF
