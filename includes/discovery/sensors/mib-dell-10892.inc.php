<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @author     Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

# MIB-Dell-10892::temperatureProbechassisIndex.1.1 = INTEGER: 1
# MIB-Dell-10892::temperatureProbeIndex.1.1 = INTEGER: 1
# MIB-Dell-10892::temperatureProbeStateCapabilities.1.1 = INTEGER: 0
# MIB-Dell-10892::temperatureProbeStateSettings.1.1 = INTEGER: enabled(2)
# MIB-Dell-10892::temperatureProbeStatus.1.1 = INTEGER: ok(3)
# MIB-Dell-10892::temperatureProbeReading.1.1 = INTEGER: 320
# MIB-Dell-10892::temperatureProbeType.1.1 = INTEGER: temperatureProbeTypeIsAmbientESM(3)
# MIB-Dell-10892::temperatureProbeLocationName.1.1 = STRING: "BMC Planar Temp"
# MIB-Dell-10892::temperatureProbeUpperCriticalThreshold.1.1 = INTEGER: 530
# MIB-Dell-10892::temperatureProbeUpperNonCriticalThreshold.1.1 = INTEGER: 480
# MIB-Dell-10892::temperatureProbeLowerNonCriticalThreshold.1.1 = INTEGER: 70
# MIB-Dell-10892::temperatureProbeLowerCriticalThreshold.1.1 = INTEGER: 30
# MIB-Dell-10892::temperatureProbeProbeCapabilities.1.1 = INTEGER: 0

# MIB-Dell-10892::coolingDevicechassisIndex.1.1 = INTEGER: 1
# MIB-Dell-10892::coolingDeviceIndex.1.1 = INTEGER: 1
# MIB-Dell-10892::coolingDeviceStateCapabilities.1.1 = INTEGER: 0
# MIB-Dell-10892::coolingDeviceStateSettings.1.1 = INTEGER: enabled(2)
# MIB-Dell-10892::coolingDeviceStatus.1.1 = INTEGER: ok(3)
# MIB-Dell-10892::coolingDeviceReading.1.1 = INTEGER: 6240
# MIB-Dell-10892::coolingDeviceType.1.1 = INTEGER: coolingDeviceTypeIsAFan(3)
# MIB-Dell-10892::coolingDeviceLocationName.1.1 = STRING: "Fan 1"
# MIB-Dell-10892::coolingDeviceUpperCriticalThreshold.1.1 = INTEGER: 17040
# MIB-Dell-10892::coolingDeviceUpperNonCriticalThreshold.1.1 = INTEGER: 15000
# MIB-Dell-10892::coolingDeviceLowerNonCriticalThreshold.1.1 = INTEGER: 4320
# MIB-Dell-10892::coolingDeviceLowerCriticalThreshold.1.1 = INTEGER: 3720
# MIB-Dell-10892::coolingDeviceSubType.1.1 = INTEGER: coolingDeviceSubTypeIsAFanThatReadsInRPM(3)
# MIB-Dell-10892::coolingDeviceProbeCapabilities.1.1 = INTEGER: 15

if (strstr($device['hardware'], "Dell"))
{
  $scale = 0.1;
  $oids = snmp_walk($device, "temperatureProbeStateSettings", "-Osqn", 'MIB-Dell-10892');

  $oids = trim($oids);
  if ($oids) echo("Dell OMSA ");
  foreach (explode("\n", $oids) as $data)
  {
    $data = trim($data);
    if ($data)
    {
      list($oid,$status) = explode(" ", $data, 2);
      if (OBS_DEBUG > 1) { echo("status : ".$status."\n"); }
      if ($status == "enabled")
      {
        $split_oid        = explode('.',$oid);
        $index   = $split_oid[count($split_oid)-2].".".$split_oid[count($split_oid)-1];
        $descr_oid        = ".1.3.6.1.4.1.674.10892.1.700.20.1.8.$index";
        $temperature_oid  = ".1.3.6.1.4.1.674.10892.1.700.20.1.6.$index";
        $limit_oid        = ".1.3.6.1.4.1.674.10892.1.700.20.1.10.$index";
        $warnlimit_oid    = ".1.3.6.1.4.1.674.10892.1.700.20.1.11.$index";
        $lowwarnlimit_oid = ".1.3.6.1.4.1.674.10892.1.700.20.1.12.$index";
        $lowlimit_oid     = ".1.3.6.1.4.1.674.10892.1.700.20.1.13.$index";

        $oid_name         = "temperatureProbeReading";

        $descr        = trim(snmp_get($device, $descr_oid, "-Oqv", 'MIB-Dell-10892'),'"');
        $value  = snmp_get($device, $temperature_oid, "-Oqv", 'MIB-Dell-10892');
        $options       = array('limit_high'      => snmp_get($device, $limit_oid, "-Oqv", 'MIB-Dell-10892') * $scale,
                               'limit_low'       => snmp_get($device, $lowlimit_oid,     "-Oqv", 'MIB-Dell-10892') * $scale,
                               'limit_high_warn' => snmp_get($device, $warnlimit_oid,    "-Oqv", 'MIB-Dell-10892') * $scale,
                               'limit_low_warn'  => snmp_get($device, $lowwarnlimit_oid, "-Oqv", 'MIB-Dell-10892') * $scale);

        $options['rename_rrd'] = "dell-$index";

        discover_sensor_ng($device, 'temperature', $mib, $oid_name, $temperature_oid, $index, NULL, $descr, $scale, $value, $options);
      }
    }
  }

  $oids = snmp_walk($device, "coolingDeviceStateSetting", "-Osqn", 'MIB-Dell-10892');

  $oids = trim($oids);
  if ($oids) echo("Dell OMSA ");
  foreach (explode("\n", $oids) as $data)
  {
    $data = trim($data);
    if ($data)
    {
      list($oid,$status) = explode(" ", $data, 2);
      if (OBS_DEBUG > 1) { echo("status : ".$status."\n"); }
      if ($status == "enabled")
      {
        $split_oid        = explode('.',$oid);
        $index      = $split_oid[count($split_oid)-2].".".$split_oid[count($split_oid)-1];
        $descr_oid        = ".1.3.6.1.4.1.674.10892.1.700.12.1.8.$index";
        $fanspeed_oid     = ".1.3.6.1.4.1.674.10892.1.700.12.1.6.$index";
        $limit_oid        = ".1.3.6.1.4.1.674.10892.1.700.12.1.10.$index";
        $warnlimit_oid    = ".1.3.6.1.4.1.674.10892.1.700.12.1.11.$index";
        $lowwarnlimit_oid = ".1.3.6.1.4.1.674.10892.1.700.12.1.12.$index";
        $lowlimit_oid     = ".1.3.6.1.4.1.674.10892.1.700.12.1.13.$index";
        $subtype_oid      = ".1.3.6.1.4.1.674.10892.1.700.12.1.16.$index";
        $oid_name         = 'coolingDeviceReading';

        $subtype      = snmp_get($device, $subtype_oid, "-Oqv", 'MIB-Dell-10892');
        if (strstr($subtype, "RPM")) # exclude on/off fans; 1rpm doesn't make much sense :) FIXME they could be status sensors though!
        {
          $descr        = trim(snmp_get($device, $descr_oid, "-Oqv", 'MIB-Dell-10892'),'"');
          $value     = snmp_get($device, $fanspeed_oid, "-Oqv", 'MIB-Dell-10892');
          $options       = array('limit_high'      => snmp_get($device, $limit_oid, "-Oqv", 'MIB-Dell-10892'),
                                 'limit_low'       => snmp_get($device, $lowlimit_oid,     "-Oqv", 'MIB-Dell-10892'),
                                 'limit_high_warn' => snmp_get($device, $warnlimit_oid,    "-Oqv", 'MIB-Dell-10892'),
                                 'limit_low_warn'  => snmp_get($device, $lowwarnlimit_oid, "-Oqv", 'MIB-Dell-10892'));

          $options['rrd_rename'] = "dell-$index";

          discover_sensor_ng($device, 'fanspeed', $mib, $oid_name, $fanspeed_oid, $index, NULL, $descr, 1, $value, $options);
        }
      }
    }
  }
}

// EOF
