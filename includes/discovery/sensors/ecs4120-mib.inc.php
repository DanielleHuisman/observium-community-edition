<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     definitions
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */
$mib = 'ECS4120-MIB';

$sensors = array(
  'Temperature' => array(
    'descr' => 'Temperature',
    'class' => 'temperature',
    'scale' => 1,
  ),
  'Vcc' => array(
    'descr' => 'Vcc',
    'class' => 'voltage',
    'scale' => 1,
  ),
  'TxBiasCurrent' => array(
    'descr' => 'Tx Bias',
    'class' => 'current',
    'scale' => 0.001, // mA
  ),
  'TxPower' => array(
    'descr' => 'Tx',
    'class' => 'dbm',
    'scale' => 1,
  ),
  'RxPower' => array(
    'descr' => 'Rx',
    'class' => 'dbm',
    'scale' => 1,
  ),
);

$cache = array();
foreach (array_keys($sensors) as $prop)
{
  // The sensor value is a DisplayString in a custom format: "38.98 degrees C normal",
  // "3.30 V normal", "24.74 mA normal", "2.99 dBm normal". Fortunately, in PHP that evaluates
  // to a float without a custom parsing code here and in the polling module.
  $cache = snmpwalk_cache_oid($device, "portOpticalMonitoringInfo{$prop}", $cache, $mib);
  // The thresholds are integers and are multiplied 100 times relative to the sensor.
  $cache = snmpwalk_cache_oid($device, "portTransceiverThresholdInfo{$prop}LowAlarm", $cache, $mib);
  $cache = snmpwalk_cache_oid($device, "portTransceiverThresholdInfo{$prop}LowWarn", $cache, $mib);
  $cache = snmpwalk_cache_oid($device, "portTransceiverThresholdInfo{$prop}HighWarn", $cache, $mib);
  $cache = snmpwalk_cache_oid($device, "portTransceiverThresholdInfo{$prop}HighAlarm", $cache, $mib);
}

foreach ($cache as $ifIndex => $data)
{
  $port = get_port_by_index_cache($device, $ifIndex);
  foreach ($sensors as $prop => $sensor)
  {
    if (array_key_exists("portOpticalMonitoringInfo{$prop}", $data))
    {
      $options = array(
        'measured_class' => 'port',
        'measured_entity' => $port['port_id'],
        'entPhysicalIndex' => $ifIndex,
        'limit_low' => $data["portTransceiverThresholdInfo{$prop}LowAlarm"] * 0.01 * $sensor['scale'],
        'limit_low_warn' => $data["portTransceiverThresholdInfo{$prop}LowWarn"] * 0.01 * $sensor['scale'],
        'limit_high_warn' => $data["portTransceiverThresholdInfo{$prop}HighWarn"] * 0.01 * $sensor['scale'],
        'limit_high' => $data["portTransceiverThresholdInfo{$prop}HighAlarm"] * 0.01 * $sensor['scale'],
      );
      $oid_num = snmp_translate("portOpticalMonitoringInfo{$prop}.{$ifIndex}", $mib);
      $value = $data["portOpticalMonitoringInfo{$prop}"];
      $descr = "{$port['port_label']} {$sensor['descr']}";
      discover_sensor($sensor['class'], $device, $oid_num, $ifIndex, "{$mib}-portOpticalMonitoringInfo{$prop}", $descr, $sensor['scale'], $value, $options);
    }
  } // for each sensor
} // for each port

unset($mib, $sensors, $cache, $prop, $ifIndex, $data, $port, $sensor, $options, $oid_num, $value, $descr);
// EOF
