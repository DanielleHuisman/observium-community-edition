<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

// This is Fake mib, based on descriptions from:
// https://netopslife.wordpress.com/2017/10/12/nagios-snmp-denco-pco-web-carel/

return;

$entity_array   = snmpwalk_cache_oid($device, 'wsSFPTable', array(), 'WAYSTREAM-MIB');
print_debug_vars($entity_array);

foreach ($entity_array as $index => $entry)
{
  $port    = get_port_by_index_cache($device['device_id'], $index);
  $options = array('entPhysicalIndex' => $index);
  if (is_array($port))
  {
    $entry['ifDescr']                     = $port['ifDescr'];
    $options['measured_class']            = 'port';
    $options['measured_entity']           = $port['port_id'];
    $options['entPhysicalIndex_measured'] = $port['ifIndex'];
  } else {
    // Skip?
    continue;
  }

  $temperatureoid = '.1.3.6.1.4.1.9303.4.1.4.1.12.'.$index;
  $voltageoid     = '.1.3.6.1.4.1.9303.4.1.4.1.14.'.$index;
  $rxpoweroid     = '.1.3.6.1.4.1.9303.4.1.4.1.20.'.$index;
  $txpoweroid     = '.1.3.6.1.4.1.9303.4.1.4.1.18.'.$index;

  //Ignore optical sensors with temperature of zero or negative
  if ($entry['wsSFPTemp'] > 0)
  {
    discover_sensor('temperature', $device, $temperatureoid, $index, 'waystream', $entry['ifDescr'] . ' Temperature',          1, $entry['wsSFPTemp'], $options);
    discover_sensor('voltage',     $device, $voltageoid,     $index, 'waystream', $entry['ifDescr'] . ' Voltage',          0.001, $entry['wsSFPVolt'], $options);

    if ($entry['wsSFPRXPower'] >= 0)
    {
      discover_sensor('power', $device, $rxpoweroid, 'wsSFPRXPower.' . $index, 'waystream', $entry['ifDescr'] . ' Rx Power', 0.001, $entry['wsSFPRXPower'], $options);
      discover_sensor('power', $device, $txpoweroid, 'wsSFPTXPower.' . $index, 'waystream', $entry['ifDescr'] . ' Tx Power', 0.001, $entry['wsSFPTXPower'], $options);
    }
  }

}

unset($entity_array);

// EOF

