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

// RITTAL-CMC-III-MIB::cmcIIISetTempUnit.0 = INTEGER: celsius(1)
$temp_unit = snmp_get_oid($device, 'cmcIIISetTempUnit.0', $mib);

//// Aggggrrrrr, this is very "logical"..

//RITTAL-CMC-III-MIB::cmcIIIVarName.1.1 = STRING: Temperature.DescName
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.2 = STRING: Temperature.Value
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.3 = STRING: Temperature.Offset
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.4 = STRING: Temperature.SetPtHighAlarm
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.5 = STRING: Temperature.SetPtHighWarning
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.6 = STRING: Temperature.SetPtLowWarning
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.7 = STRING: Temperature.SetPtLowAlarm
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.8 = STRING: Temperature.Hysteresis
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.9 = STRING: Temperature.Status
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.10 = STRING: Temperature.Category
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.11 = STRING: Access.DescName
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.12 = STRING: Access.Value
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.13 = STRING: Access.Sensitivity
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.14 = STRING: Access.Delay
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.15 = STRING: Access.Status
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.16 = STRING: Access.Category
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.17 = STRING: Input 1.DescName
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.18 = STRING: Input 1.Value
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.19 = STRING: Input 1.Logic
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.20 = STRING: Input 1.Delay
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.21 = STRING: Input 1.Status
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.22 = STRING: Input 1.Category
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.23 = STRING: Input 2.DescName
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.24 = STRING: Input 2.Value
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.25 = STRING: Input 2.Logic
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.26 = STRING: Input 2.Delay
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.27 = STRING: Input 2.Status
//RITTAL-CMC-III-MIB::cmcIIIVarName.1.28 = STRING: Input 2.Category
$oids = snmpwalk_cache_oid($device, "cmcIIIVarTable", array(), $mib);
//print_debug_vars($oids);

// Rearrage this dumb array as more logic
$sensors = array();
foreach ($oids as $index => $entry)
{
  $name_parts = explode('.', $entry['cmcIIIVarName']);
  $param      = array_pop($name_parts);
  $param      = $entry['cmcIIIVarType'];
  $name       = implode(' ', $name_parts);

  $sensors[$name][$param] = $entry;
  $sensors[$name][$param]['index'] = $index;
}
print_debug_vars($sensors);

foreach ($sensors as $name => $sensor)
{
  $descr = $name;
  if (strlen($sensor['description']['cmcIIIVarValueStr']))
  {
    $tmp = str_replace(array('_', 'Sys '),
                       array(' ', 'System '), $sensor['description']['cmcIIIVarValueStr']);
    if (!str_contains($name, $tmp))
    {
      $descr .= ' - ' . $sensor['description']['cmcIIIVarValueStr'];
    }
  }

  if (isset($sensor['value']))
  {
    $entry = $sensor['value'];

    switch($entry['cmcIIIVarScale'][0])
    {
      case '-':
        $scale = 1/(int)substr($entry['cmcIIIVarScale'], 1);
        break;
      case '+':
        $scale = (int)substr($entry['cmcIIIVarScale'], 1);
        break;
      default:
        $scale = 1;
    }

    $index = $entry['index'];
    $unit  = $entry['cmcIIIVarUnit'];
    //$type  = $entry['cmcIIIVarType'];
    //$name  = $entry['cmcIIIVarName'];
    $value = $entry['cmcIIIVarValueInt'];
    $oid_num = '.1.3.6.1.4.1.2606.7.4.2.2.1.11.'.$index;

    $options = array();
    /*
    if ($type == 'outputPWM')
    {
      $t = "power";
    }
    else if ($type == 'rotation')
    {
      $t = "fanspeed";
    }
    else
    */
    $type = NULL;
    if (str_contains($unit, 'degree') || str_ends($unit, array('C', 'F')))
    {
      $type = "temperature";
      if ($temp_unit == 'fahrenheit')
      {
        $options['sensor_unit'] = 'F';
      }
    }
    else if (str_ends($unit, 'V'))
    {
      $type = "voltage";
    }
    else if ($unit == "%")
    {
      if (str_icontains($name, 'RPM'))
      {
        $type = "load";
      }
    }
    else if (str_contains($unit, 'l/min'))
    {
      $type = "waterflow";
      $options['sensor_unit'] = 'l/min';
    }
    else if (str_ends($unit, 'W'))
    {
      $type = "power";
    }
    else if (str_ends($unit, 'A'))
    {
      $type = "current";
    }

    if ($type)
    {
      // Limits
      foreach (array('limit_high' => 'setHigh', 'limit_high_warn' => 'setWarn', 'limit_low_warn' => 'setWarnLow', 'limit_low' => 'setLow') as $limit => $param)
      {
        if (isset($sensor[$param]) && is_numeric($sensor[$param]['cmcIIIVarValueInt']))
        {
          $entry = $sensor[$param];

          switch($entry['cmcIIIVarScale'][0])
          {
            case '-':
              $scale_limit = 1/(int)substr($entry['cmcIIIVarScale'], 1);
              break;
            case '+':
              $scale_limit = (int)substr($entry['cmcIIIVarScale'], 1);
              break;
            default:
              $scale_limit = 1;
          }
          $options[$limit] = $entry['cmcIIIVarValueInt'] * $scale_limit;
        }
      }

      $options['rename_rrd'] = "Rittal-CMC-III-cmcIIIVarTable.$index";
      $object = 'cmcIIIVarValueInt';
      discover_sensor_ng($device, $type, $mib, $object, $oid_num, $index, NULL, $descr, $scale, $value, $options);
    }
  }

  // Not sure about this sensor, converted from old
  if (isset($sensor['rotation']))
  {
    $entry = $sensor['rotation'];

    switch($entry['cmcIIIVarScale'][0])
    {
      case '-':
        $scale = 1/(int)substr($entry['cmcIIIVarScale'], 1);
        break;
      case '+':
        $scale = (int)substr($entry['cmcIIIVarScale'], 1);
        break;
      default:
        $scale = 1;
    }

    $index = $entry['index'];
    $unit  = $entry['cmcIIIVarUnit'];
    //$type  = $entry['cmcIIIVarType'];
    //$name  = $entry['cmcIIIVarName'];
    $value = $entry['cmcIIIVarValueInt'];
    $oid_num = '.1.3.6.1.4.1.2606.7.4.2.2.1.11.'.$index;

    $object = 'cmcIIIVarValueInt';

    discover_sensor_ng($device,'fanspeed', $mib, $object, $oid_num, $index, NULL, $descr, $scale, $value, ['rename_rrd' => "Rittal-CMC-III-cmcIIIVarTable.$index"]);
  }

  if (isset($sensor['status']))
  {
    $entry = $sensor['status'];

    $index = $entry['index'];
    $oid_name = 'cmcIIIVarValueInt';
    $datatype = $entry['cmcIIIVarDataType'];
    $type  = $entry['cmcIIIVarType'];
    //$name  = $entry['cmcIIIVarName'];
    $value = $entry['cmcIIIVarValueInt'];
    $oid_num = '.1.3.6.1.4.1.2606.7.4.2.2.1.11.'.$index;

    if ($datatype == 'enum')
    {
      discover_status($device, $oid_num, "$oid_name.$index", 'cmcIIIMsgStatus', $descr, $value, array('entPhysicalClass' => 'other'));
    }
  }
}

// EOF
