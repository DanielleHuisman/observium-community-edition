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

//ServersCheck::sensor1name.0 = STRING: "Temp env01"
//ServersCheck::sensor1Value.0 = STRING: "18.28"
//ServersCheck::sensor1LastErrMsg.0 = STRING: "WARNING"
//ServersCheck::sensor1LastErrTime.0 = STRING: "23 May 2017,21:02:27"
//ServersCheck::sensor2name.0 = STRING: "Ping"
//ServersCheck::sensor2Value.0 = STRING: "1.00"
//ServersCheck::sensor2LastErrMsg.0 = STRING: "-"
//ServersCheck::sensor2LastErrTime.0 = STRING: "-"
//ServersCheck::sensor1Name.0 = STRING: "-"
//ServersCheck::sensor1value.0 = STRING: "-"
//ServersCheck::sensor1ErrState.0 = STRING: "-"
//ServersCheck::sensor1lastErrTime.0 = STRING: "-"
//ServersCheck::sensor1lastErrMsg.0 = STRING: "-"

$oid_list = array('1' => '.1.3.6.1.4.1.17095.3.2.0',
                  '2' => '.1.3.6.1.4.1.17095.3.6.0',
                  '3' => '.1.3.6.1.4.1.17095.3.10.0',
                  '4' => '.1.3.6.1.4.1.17095.3.14.0',
                  '5' => '.1.3.6.1.4.1.17095.3.18.0');

foreach ($oid_list as $index => $oid)
{
  $value = snmp_get_oid($device, $oid);

  if (is_numeric($value))
  {

    $descr_oid = 'sensor'.$index.'name.0';
    $descr = snmp_get_oid($device, $descr_oid, 'ServersCheck');

    if ($descr != "-")
    {

      unset($unit);
      $type = "temperature";

      if     (strpos($descr, "Temp")      !== FALSE) { $type = "temperature"; }
      elseif (strpos($descr, "Humidity")  !== FALSE) { $type = "humidity"; }
      elseif (strpos($descr, "Dew Point") !== FALSE) { $type = "dewpoint"; }
      elseif (strpos($descr, "Airflow")   !== FALSE) { $type = "airflow"; }
      elseif (strpos($descr, "Dust")      !== FALSE) { $type = "dust"; }
      elseif (strpos($descr, "Sound")     !== FALSE) { $type = "sound"; }

      // If the global setting is set telling us all of our serverscheck devices are F, set the unit as F.
      if ($type == "temperature" && $config['devices']['serverscheck']['temp_f'] == TRUE)
      {
        $options = array('sensor_unit' => 'F');
      }
      else if ($type == "airflow")
      {
        $options = array('sensor_unit' => 'CFM');
      }

      discover_sensor($type, $device, $oid, $index, 'serverscheck_sensor', $descr, 1, $value, $options);
    }

  }

  unset($options);
  unset($unit);
  unset($type);
  unset($descr);
  unset($descr_oid);

}


// enterprises.serverscheck.input.input1name.0 = STRING: "UndefineIO 1"
// enterprises.serverscheck.input.input1Value.0 = STRING: "Triggered"
// enterprises.serverscheck.input.input1LastErrMsg.0 = STRING: "Triggered,01 January 2012,00:26:44"
// enterprises.serverscheck.input.input2name.0 = STRING: "UndefineIO 2"
// enterprises.serverscheck.input.input2Value.0 = STRING: "OK"
// enterprises.serverscheck.input.input2LastErrMsg.0 = STRING: "-"

// .1.3.6.1.4.1.17095.6.1.0 = "UndefineIO 1"
// .1.3.6.1.4.1.17095.6.2.0 = "Triggered"
// .1.3.6.1.4.1.17095.6.3.0 = "Triggered,01 January 2012,00:28:15"
// .1.3.6.1.4.1.17095.6.4.0 = "UndefineIO 2"
// .1.3.6.1.4.1.17095.6.5.0 = "OK"
// .1.3.6.1.4.1.17095.6.6.0 = "-"

$data = snmpwalk_cache_oid_num($device, '.1.3.6.1.4.1.17095.6', array());

// Loop OIDs skipping 3 at a time.
for($i = 1; $i < 48; $i += 3)
{
  $descr_oid = '.1.3.6.1.4.1.17095.6.'.$i.'.0';
  $value_oid = '.1.3.6.1.4.1.17095.6.' . ($i+1) . '.0';
  $index = (($i + 2) / 3);
  if(isset($data[$value_oid]) && isset($data[$descr_oid]) && strpos($data[$descr_oid], 'UndefineIO') === FALSE)
  {
    discover_status($device, $value_oid, $index, 'serverscheck-input', $data[$descr_oid] . ' ('.$index.')', $data[$value_oid], array('entPhysicalClass' => 'other'));
  }
}
