<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) Adam Armstrong
 *
 */

echo("Juniper Firewall Counters");


$fws = [];
$fws = snmpwalk_cache_threepart_oid($device, "jnxFWCounterDisplayType", $fws, "JUNIPER-FIREWALL-MIB");
if (count($fws)) {
    $oid = 'jnxFWCounterDisplayType';
}

$array = [];

foreach ($fws as $filter => $counters) {
    foreach ($counters as $counter => $types) {
        foreach ($types as $type => $data) {
            $array[$filter][$counter][$type] = 1;
        }
    }
}

echo("\n");

if (!safe_empty($array)) {
    // FIXME. Adama, this array is very big for attrib table
    set_entity_attrib('device', $device['device_id'], 'juniper-firewall-mib', str_compress(safe_json_encode($array)));
}

unset($fws, $filter, $counters, $counter, $data);

// EOF

