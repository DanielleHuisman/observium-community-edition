<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage definitions
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

// analog1name.0 = "Amps Power Supply"
// analog1val.0 = "0.0 Amps"
// analog2name.0 = "Amps Battery Mode"
// analog2val.0 = "0.0 Amps"
// analog3name.0 = "Volts Power Supply"
// analog3val.0 = "14.1 Volts"
// analog4name.0 = "Volts Battery"
// analog4val.0 = "12.9 Volts"

$oids = snmpwalk_cache_oid($device, "control", array(), "RMCU");
$oids = snmpwalk_cache_oid($device, "control-ints", $oids, "RMCU");

//print_vars($oids);

$i=1;
while($i < 6)
{

  $val     = $oids[0]['analog'.$i.'val'];
  $val_int = $oids[0]['analog'.$i.'val-int'];
  $name    = $oids[0]['analog'.$i.'name'];
  $oid     = 'analog'.$i.'val-int';
  $oid_num = '.1.3.6.1.4.1.15116.3.4.'.$i.'.0';
  $index   = $i;
  unset($class);

  // Guess units from $val
  if     (str_contains_array($val, "Amps"))   { $class = "voltage"; }
  elseif (str_contains_array($val, "Volts"))  { $class = "current"; }

  if(isset($class) && $name !== "00 " ) {
    discover_sensor_ng($device, $class, 'RMCU', $oid, $oid_num, $index, NULL, $name, 0.0001, $val);
  }
  $i++;
}