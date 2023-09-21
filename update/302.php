<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage update
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$devices = dbFetchRows("SELECT * FROM `devices` WHERE os='deltaups'");

foreach ($devices as $device)
{
  $sensors = dbFetchRows("SELECT * FROM `sensors` WHERE device_id=?", array($device['device_id']));
  foreach ($sensors as $sensor)
  {
    switch ($sensor['sensor_oid'])
    {	
      case '1.3.6.1.4.1.2254.2.4.7.7.0':
        rename_rrd($device, 'sensor-current-DeltaUPS-7.7.0.rrd', 'sensor-current-DeltaUPS-MIB-dupsBatteryCurrent-0.rrd');
        echo ('.');
        break;
      case '1.3.6.1.4.1.2254.2.4.5.5.0':
        rename_rrd($device, 'sensor-current-DeltaUPS-5.5.0.rrd', 'sensor-current-DeltaUPS-MIB-dupsOutputCurrent1-0.rrd');
        echo ('.');
        break;
      case '1.3.6.1.4.1.2254.2.4.4.4.0':
        rename_rrd($device, 'sensor-current-DeltaUPS-4.4.0.rrd', 'sensor-current-DeltaUPS-MIB-dupsInputCurrent1-0.rrd');
        echo ('.');
        break;
      case '1.3.6.1.4.1.2254.2.4.6.4.0':
        rename_rrd($device, 'sensor-current-DeltaUPS-6.4.0.rrd', 'sensor-current-DeltaUPS-MIB-dupsBypassCurrent1-0.rrd');
        echo ('.');
        break;
      case '1.3.6.1.4.1.2254.2.4.7.8.0':
        rename_rrd($device, 'sensor-capacity-DeltaUPS-7.8.0.rrd', 'sensor-capacity-DeltaUPS-MIB-dupsBatteryCapacity-0.rrd');
        echo ('.');
        break;
      case '1.3.6.1.4.1.2254.2.4.7.5.0':
        rename_rrd($device, 'sensor-runtime-DeltaUPS-7.5.0.rrd', 'sensor-runtime-DeltaUPS-MIB-dupsBatteryEstimatedTime-0.rrd');
        echo ('.');
        break;
      case '1.3.6.1.4.1.2254.2.4.5.7.0':
        rename_rrd($device, 'sensor-capacity-DeltaUPS-5.7.0.rrd', 'sensor-capacity-DeltaUPS-MIB-dupsOutputLoad1-0.rrd');
        echo ('.');
        break;
      case '1.3.6.1.4.1.2254.2.4.5.2.0':
        rename_rrd($device, 'sensor-frequency-DeltaUPS-5.2.0.rrd', 'sensor-frequency-DeltaUPS-MIB-dupsOutputFrequency-0.rrd');
        echo ('.');
        break;
      case '1.3.6.1.4.1.2254.2.4.4.2.0':
        rename_rrd($device, 'sensor-frequency-DeltaUPS-4.2.0.rrd', 'sensor-frequency-DeltaUPS-MIB-dupsInputFrequency-0.rrd');
        echo ('.');
        break;
      case '1.3.6.1.4.1.2254.2.4.10.2.0':
        rename_rrd($device, 'sensor-humidity-DeltaUPS-10.2.0.rrd', 'sensor-humidity-DeltaUPS-MIB-dupsEnvHumidity-0.rrd');
        echo ('.');
        break;
      case '1.3.6.1.4.1.2254.2.4.10.1.0':
        rename_rrd($device, 'sensor-temperature-DeltaUPS-10.1.0.rrd', 'sensor-temperature-DeltaUPS-MIB-dupsEnvTemperature-0.rrd');
        echo ('.');
        break;
      case '1.3.6.1.4.1.2254.2.4.7.9.0':
        rename_rrd($device, 'sensor-temperature-DeltaUPS-7.9.0.rrd', 'sensor-temperature-DeltaUPS-MIB-dupsTemperature-0.rrd');
        echo ('.');
        break;
      case '1.3.6.1.4.1.2254.2.4.7.6.0':
        rename_rrd($device, 'sensor-voltage-DeltaUPS-7.6.0.rrd', 'sensor-voltage-DeltaUPS-MIB-dupsBatteryVoltage-0.rrd');
        echo ('.');
        break;
      case '1.3.6.1.4.1.2254.2.4.5.4.0':
        rename_rrd($device, 'sensor-voltage-DeltaUPS-5.4.0.rrd', 'sensor-voltage-DeltaUPS-MIB-dupsOutputVoltage1-0.rrd');
        echo ('.');
        break;
      case '1.3.6.1.4.1.2254.2.4.4.3.0':
        rename_rrd($device, 'sensor-voltage-DeltaUPS-4.3.0.rrd', 'sensor-voltage-DeltaUPS-MIB-dupsInputVoltage1-0.rrd');
        echo ('.');
        break;
      case '1.3.6.1.4.1.2254.2.4.6.3.0':
        rename_rrd($device, 'sensor-voltage-DeltaUPS-6.3.0.rrd', 'sensor-voltage-DeltaUPS-MIB-dupsBypassVoltage1-0.rrd');
        echo ('.');
        break;
    }
    
    force_discovery($device, array('sensors'));
  }
}

// EOF
