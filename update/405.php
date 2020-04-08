<?php

echo "Convert MIB permissions [";

// Global MIB permissions to SQL config
foreach (get_obs_attribs('mib_') as $param => $value)
{
  $mib = substr($param, 4); // mib_QQ -> QQ
  $key = 'mibs|'.$mib.'|enable'; // $config['mibs'][$mib]['enable'] => mibs|$mib|enable
  //print_message("$param -> $mib : $key = $value");
  set_sql_config($key, $value);
  del_obs_attrib($param);
  echo '.';
}

// Per device MIB permissions to mibs table
$sql = 'SELECT * FROM `entity_attribs` WHERE `entity_type` = ? AND `attrib_type` LIKE ?';
foreach (dbFetchRows($sql, ['device', 'mib_%']) as $entry)
{
  $entry['device_id'];
  $mib   = substr($entry['attrib_type'], 4); // mib_QQ -> QQ
  if ($entry['attrib_value'])
  {
    // enabled
    set_device_mib_enable($entry['device_id'], $mib);
  } else {
    // disabled
    set_device_mib_disable($entry['device_id'], $mib);
  }
  del_entity_attrib('device', $entry['device_id'], $entry['attrib_type']);
  echo '.';
}

echo ']';

// EOF
