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

// FIXME this is not working statuses, need move to ports module or separate FC ports module

$atto_fc_ports = snmpwalk_cache_oid($device, 'fcPortPortNumber', [], $mib);

foreach ($atto_fc_ports as $port) {
    $index      = $port['fcPortPortNumber'];
    $sensorName = "FiberChannel Port.$index";
    $oid        = ".1.3.6.1.4.1.4547.2.3.3.1.1.3.$index";

// FIXME why value NULL? Just use the entry from $port['whatevertheoidis'] ?
    discover_status($device, $index, "fcPortOperationalState.$index", 'atto6500n-mib-fcPort', $sensorName, NULL, ['entPhysicalClass' => 'port']);
}

$atto_sas_ports = snmpwalk_cache_oid($device, 'sasPortPortNumber', [], $mib);

foreach ($atto_sas_ports as $port) {
    $index      = $port['sasPortPortNumber'];
    $sensorName = "SAS Port $index";
    $oid        = ".1.3.6.1.4.1.4547.2.3.3.3.1.2.$index";

// FIXME same as above
    discover_status($device, $index, "sasPortOperationalState.$index", 'atto6500n-mib-sasPort', $sensorName, NULL, ['entPhysicalClass' => 'port']);
}

unset($atto_fc_ports, $atto_sas_ports);

// EOF
