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

// This is Fake mib, based on descriptions from:
// https://netopslife.wordpress.com/2017/10/12/nagios-snmp-denco-pco-web-carel/

$numeric_oids = [
  ['oid_num' => '.1.3.6.1.4.1.9839.2.1.2.1.0', 'class' => 'temperature', 'scale' => 0.1, 'type' => 'denco-analog', 'descr' => 'Temperature'],
  ['oid_num' => '.1.3.6.1.4.1.9839.2.1.2.2.0', 'class' => 'humidity', 'scale' => 0.1, 'type' => 'denco-analog', 'descr' => 'Humidity'],
];

foreach ($numeric_oids as $entry) {
    $value   = snmp_get_oid($device, $entry['oid_num']);
    $scale   = $entry['scale'] ?? 1;
    $options = [ 'sensor_type' => $entry['type'] ];
    if ($value > 0 && $value != 32767) {
        discover_sensor_ng($device, $entry['class'], NULL, NULL, $entry['oid_num'], 0, $entry['descr'], $scale, $value, $options);
    }
}

// EOF

