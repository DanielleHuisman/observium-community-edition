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

$devices = dbFetchRows("SELECT * FROM `devices` WHERE os='pcoweb'");

foreach ($devices as $device)
{
  $sensors = dbFetchRows("SELECT * FROM `sensors` WHERE device_id=?", array($device['device_id']));
  foreach ($sensors as $sensor)
  {
    switch ($sensor['sensor_oid'])
    {	
      case '.1.3.6.1.4.1.9839.2.1.2.6.0':
        rename_rrd($device, "sensor-humidity-pcoweb-2.1.2.6.0.rrd", "sensor-humidity-CAREL-ug40cdz-MIB-roomRH-0.rrd");
        echo('.');
        break;
      case '.1.3.6.1.4.1.9839.2.1.3.12.0':
        rename_rrd($device, "sensor-humidity-pcoweb-2.1.3.12.0.rrd", "sensor-humidity-CAREL-ug40cdz-MIB-dehumPband-0.rrd");
        echo('.');
        break;
      case '.1.3.6.1.4.1.9839.2.1.3.13.0':
        rename_rrd($device, "sensor-humidity-pcoweb-2.1.3.13.0.rrd", "sensor-humidity-CAREL-ug40cdz-MIB-humidPband-0.rrd");
        echo('.');
        break;
      case '.1.3.6.1.4.1.9839.2.1.3.16.0':
        rename_rrd($device, "sensor-humidity-pcoweb-2.1.3.16.0.rrd", "sensor-humidity-CAREL-ug40cdz-MIB-dehumSetp-0.rrd");
        echo('.');
        break;
      case '.1.3.6.1.4.1.9839.2.1.3.17.0':
        rename_rrd($device, "sensor-humidity-pcoweb-2.1.3.17.0.rrd", "sensor-humidity-CAREL-ug40cdz-MIB-humidSetp-0.rrd");
        echo('.');
        break;

      case '.1.3.6.1.4.1.9839.2.1.2.1.0':
        rename_rrd($device, "sensor-temperature-pcoweb-2.1.2.1.0.rrd", "sensor-temperature-CAREL-ug40cdz-MIB-roomTemp-0.rrd");
        echo('.');
        break;
      case '.1.3.6.1.4.1.9839.2.1.2.2.0':
        rename_rrd($device, "sensor-temperature-pcoweb-2.1.2.2.0.rrd", "sensor-temperature-CAREL-ug40cdz-MIB-outdoorTemp-0.rrd");
        echo('.');
        break;
      case '.1.3.6.1.4.1.9839.2.1.2.3.0':
        rename_rrd($device, "sensor-temperature-pcoweb-2.1.2.3.0.rrd", "sensor-temperature-CAREL-ug40cdz-MIB-deliveryTemp-0.rrd");
        echo('.');
        break;
      case '.1.3.6.1.4.1.9839.2.1.2.4.0':
        rename_rrd($device, "sensor-temperature-pcoweb-2.1.2.4.0.rrd", "sensor-temperature-CAREL-ug40cdz-MIB-cwTemp-0.rrd");
        echo('.');
        break;
      case '.1.3.6.1.4.1.9839.2.1.2.5.0':
        rename_rrd($device, "sensor-temperature-pcoweb-2.1.2.5.0.rrd", "sensor-temperature-CAREL-ug40cdz-MIB-hwTemp-0.rrd");
        echo('.');
        break;
      case '.1.3.6.1.4.1.9839.2.1.2.7.0':
        rename_rrd($device, "sensor-temperature-pcoweb-2.1.2.7.0.rrd", "sensor-temperature-CAREL-ug40cdz-MIB-cwoTemp-0.rrd");
        echo('.');
        break;
      case '.1.3.6.1.4.1.9839.2.1.2.10.0':
        rename_rrd($device, "sensor-temperature-pcoweb-2.1.2.10.0.rrd", "sensor-temperature-CAREL-ug40cdz-MIB-suctTemp1-0.rrd");
        echo('.');
        break;
      case '.1.3.6.1.4.1.9839.2.1.2.11.0':
        rename_rrd($device, "sensor-temperature-pcoweb-2.1.2.11.0.rrd", "sensor-temperature-CAREL-ug40cdz-MIB-sucTemp2-0.rrd");
        echo('.');
        break;
      case '.1.3.6.1.4.1.9839.2.1.2.12.0':
        rename_rrd($device, "sensor-temperature-pcoweb-2.1.2.12.0.rrd", "sensor-temperature-CAREL-ug40cdz-MIB-evapTemp1-0.rrd");
        echo('.');
        break;
      case '.1.3.6.1.4.1.9839.2.1.2.13.0':
        rename_rrd($device, "sensor-temperature-pcoweb-2.1.2.13.0.rrd", "sensor-temperature-CAREL-ug40cdz-MIB-evapTemp2-0.rrd");
        echo('.');
        break;
      case '.1.3.6.1.4.1.9839.2.1.2.14.0':
        rename_rrd($device, "sensor-temperature-pcoweb-2.1.2.14.0.rrd", "sensor-temperature-CAREL-ug40cdz-MIB-ssh1-0.rrd");
        echo('.');
        break;
      case '.1.3.6.1.4.1.9839.2.1.2.15.0':
        rename_rrd($device, "sensor-temperature-pcoweb-2.1.2.15.0.rrd", "sensor-temperature-CAREL-ug40cdz-MIB-ssh2-0.rrd");
        echo('.');
        break;
      case '.1.3.6.1.4.1.9839.2.1.2.20.0':
        rename_rrd($device, "sensor-temperature-pcoweb-2.1.2.20.0.rrd", "sensor-temperature-CAREL-ug40cdz-MIB-coolSetP-0.rrd");
        echo('.');
        break;
      case '.1.3.6.1.4.1.9839.2.1.2.21.0':
        rename_rrd($device, "sensor-temperature-pcoweb-2.1.2.21.0.rrd", "sensor-temperature-CAREL-ug40cdz-MIB-coolDiff-0.rrd");
        echo('.');
        break;
      case '.1.3.6.1.4.1.9839.2.1.2.22.0':
        rename_rrd($device, "sensor-temperature-pcoweb-2.1.2.22.0.rrd", "sensor-temperature-CAREL-ug40cdz-MIB-cool2SetP-0.rrd");
        echo('.');
        break;
      case '.1.3.6.1.4.1.9839.2.1.2.23.0':
        rename_rrd($device, "sensor-temperature-pcoweb-2.1.2.23.0.rrd", "sensor-temperature-CAREL-ug40cdz-MIB-heatSetP-0.rrd");
        echo('.');
        break;
      case '.1.3.6.1.4.1.9839.2.1.2.24.0':
        rename_rrd($device, "sensor-temperature-pcoweb-2.1.2.24.0.rrd", "sensor-temperature-CAREL-ug40cdz-MIB-heat2SetP-0.rrd");
        echo('.');
        break;
      case '.1.3.6.1.4.1.9839.2.1.2.25.0':
        rename_rrd($device, "sensor-temperature-pcoweb-2.1.2.25.0.rrd", "sensor-temperature-CAREL-ug40cdz-MIB-heatDiff-0.rrd");
        echo('.');
        break;
    }
  }
}

// EOF
