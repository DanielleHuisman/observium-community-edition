<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage functions
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

// Include entity specific functions
require_once($config['install_dir'] . "/includes/entities/port.inc.php");
require_once($config['install_dir'] . "/includes/entities/sensor.inc.php");
require_once($config['install_dir'] . "/includes/entities/status.inc.php");
require_once($config['install_dir'] . "/includes/entities/counter.inc.php");

/**
 *
 * Get attribute value for entity
 *
 * @param string $entity_type
 * @param mixed $entity_id
 * @param string $attrib_type
 * @return string
 */
function get_entity_attrib($entity_type, $entity_id, $attrib_type)
{
  if (is_array($entity_id))
  {
    // Passed entity array, instead id
    $translate = entity_type_translate_array($entity_type);
    $entity_id = $entity_id[$translate['id_field']];
  }
  if (!$entity_id) { return NULL; }

  if (isset($GLOBALS['cache']['entity_attribs'][$entity_type][$entity_id][$attrib_type]))
  {
    return $GLOBALS['cache']['entity_attribs'][$entity_type][$entity_id][$attrib_type];
  }

  if ($row = dbFetchRow("SELECT `attrib_value` FROM `entity_attribs` WHERE `entity_type` = ? AND `entity_id` = ? AND `attrib_type` = ?", array($entity_type, $entity_id, $attrib_type)))
  {
    return $row['attrib_value'];
  }

  return NULL;
}

/**
 *
 * Get all attributes for entity
 *
 * @param string $entity_type
 * @param mixed $entity_id
 * @return array
 */
function get_entity_attribs($entity_type, $entity_id)
{
  if (is_array($entity_id))
  {
    // Passed entity array, instead id
    $translate = entity_type_translate_array($entity_type);
    $entity_id = $entity_id[$translate['id_field']];
  }
  if (!$entity_id) { return NULL; }

  if (!isset($GLOBALS['cache']['entity_attribs'][$entity_type][$entity_id]))
  {
    $attribs = array();

    foreach (dbFetchRows("SELECT * FROM `entity_attribs` WHERE `entity_type` = ? AND `entity_id` = ?", array($entity_type, $entity_id)) as $entry)
    {
      $attribs[$entry['attrib_type']] = $entry['attrib_value'];
    }

    $GLOBALS['cache']['entity_attribs'][$entity_type][$entity_id] = $attribs;
  }
  return $GLOBALS['cache']['entity_attribs'][$entity_type][$entity_id];
}

/**
 *
 * Set value for specific attribute and entity
 *
 * @param string $entity_type
 * @param mixed $entity_id
 * @param string $attrib_type
 * @param string $attrib_value
 * @param string $device_id
 * @return boolean
 */
function set_entity_attrib($entity_type, $entity_id, $attrib_type, $attrib_value, $device_id = NULL)
{
  if (is_array($entity_id))
  {
    // Passed entity array, instead id
    $translate = entity_type_translate_array($entity_type);
    $entity = $entity_id;
    $entity_id = $entity[$translate['id_field']];
  }

  if (!$entity_id) { return NULL; }

  // If we're setting a device attribute, use the entity_id as the device_id
  if($entity_type == "device") { $device_id = $entity_id; }


  // If we don't have a device_id, try to work out what it should be
  if(!$device_id)
  {
    if(isset($entity) && isset($entity['device_id']))
    {
      $device_id = $entity['device_id'];
    } else {
      $entity = get_entity_by_id_cache($entity_type, $entity_id);
      $device_id = $entity['device_id'];
    }
  }
  if (!$device_id) { print_error("Enable to set attrib data : $entity_type, $entity_id, $attrib_type, $attrib_value, $device_id");  return NULL; }

  if (isset($GLOBALS['cache']['entity_attribs'][$entity_type][$entity_id]))
  {
    // Reset entity attribs
    unset($GLOBALS['cache']['entity_attribs'][$entity_type][$entity_id]);
  }

  //if (dbFetchCell("SELECT COUNT(*) FROM `entity_attribs` WHERE `entity_type` = ? AND `entity_id` = ? AND `attrib_type` = ?", array($entity_type, $entity_id, $attrib_type)))
  if (dbExist('entity_attribs', '`entity_type` = ? AND `entity_id` = ? AND `attrib_type` = ?', array($entity_type, $entity_id, $attrib_type)))
  {
    $return = dbUpdate(array('attrib_value' => $attrib_value), 'entity_attribs', '`entity_type` = ? AND `entity_id` = ? AND `attrib_type` = ?', array($entity_type, $entity_id, $attrib_type));
  } else {
    $return = dbInsert(array('device_id' => $device_id, 'entity_type' => $entity_type, 'entity_id' => $entity_id, 'attrib_type' => $attrib_type, 'attrib_value' => $attrib_value), 'entity_attribs');
    if ($return !== FALSE) { $return = TRUE; } // Note dbInsert return IDs if exist or 0 for not indexed tables
  }

  return $return;
}

/**
 *
 * Delete specific attribute for entity
 *
 * @param string $entity_type
 * @param mixed $entity_id
 * @param string $attrib_type
 * @return boolean
 */
function del_entity_attrib($entity_type, $entity_id, $attrib_type)
{
  if (is_array($entity_id))
  {
    // Passed entity array, instead id
    $translate = entity_type_translate_array($entity_type);
    $entity_id = $entity_id[$translate['id_field']];
  }
  if (!$entity_id) { return NULL; }

  if (isset($GLOBALS['cache']['entity_attribs'][$entity_type][$entity_id]))
  {
    // Reset entity attribs
    unset($GLOBALS['cache']['entity_attribs'][$entity_type][$entity_id]);
  }

  return dbDelete('entity_attribs', '`entity_type` = ? AND `entity_id` = ? AND `attrib_type` = ?', array($entity_type, $entity_id, $attrib_type));

}

/**
 *
 * Get array of entities (id) linked to device
 *
 * @param mixed $device_id Device array of id
 * @param mixed $entity_types List of entities as array, if empty get all
 * @return array
 */
function get_device_entities($device_id, $entity_types = NULL)
{
  if (is_array($device_id))
  {
    // Passed device array, instead id
    $device_id = $device_id['device_id'];
  }
  if (!$device_id) { return NULL; }

  if (!is_array($entity_types) && strlen($entity_types))
  {
    // Single entity type passed, convert to array
    $entity_types = array($entity_types);
  }
  $all = empty($entity_types);
  $entities = array();
  foreach (array_keys($GLOBALS['config']['entities']) as $entity_type)
  {
    if ($all || in_array($entity_type, $entity_types))
    {
      $translate = entity_type_translate_array($entity_type);
      if (!$translate['device_id_field']) { continue; }
      $query = 'SELECT `' . $translate['id_field'] . '` FROM `' . $translate['table'] . '` WHERE `' . $translate['device_id_field'] . '` = ?;';
      $entity_ids = dbFetchColumn($query, array($device_id));
      if (is_array($entity_ids) && count($entity_ids))
      {
        $entities[$entity_type] = $entity_ids;
      }
    }
  }
  return $entities;
}

/**
 *
 * Get all attributes for all entities from device
 *
 * @param string $entity_type
 * @param mixed $entity_id
 * @return array
 */
function get_device_entities_attribs($device_id, $entity_types = NULL)
{
  $attribs = array();

  $query = "SELECT * FROM `entity_attribs` WHERE `device_id` = ?";
  if($entity_types)
  {
    if(!is_array($entity_types)) { $entity_types = array($entity_types); }
    $query .= " AND `entity_type` IN ('".implode("', '", $entity_types)."')";
  }

  foreach (dbFetchRows($query, array($device_id)) as $entry)
  {
    $attribs[$entry['entity_type']][$entry['entity_id']][$entry['attrib_type']] = $entry['attrib_value'];
  }

  return $attribs;
}

/* MIB & object specific functions */

/**
 * Check if MIB available and permitted for device
 * if $check_permissions is TRUE, check permissions by config option $config['mibs'][$mib]
 * and from the enable/disable panel in the device configuration in the web interface
 * if $check_sysORID is TRUE, we fetch the device's supplied list as well - should only be FALSE in the sysORID code
 *
 * @param array $device Device array
 * @param string $mib MIB name
 * @param boolean $check_permissions Check device specific MIB permissions (if FALSE ignores it)
 * @param boolean $check_sysORID Check if MIB exist in sysOROID table
 *
 * @return boolean MIB is permitted for device or not.
 */
function is_device_mib($device, $mib, $check_permissions = TRUE, $check_sysORID = TRUE)
{
  global $config;

  $mib_permitted = in_array($mib, get_device_mibs($device, $check_sysORID)); // Check if mib available for device

  if ($check_permissions && $mib_permitted)
  {
    // Check if MIB permitted by config
    $mib_permitted = $mib_permitted && (!isset($config['mibs'][$mib]['enable']) || $config['mibs'][$mib]['enable']);

    // Check if MIB disabled on device by web interface or polling process
    $mibs_disabled = get_device_mibs_disabled($device);
    $mib_permitted = $mib_permitted && !in_array($mib, $mibs_disabled);
  }

  return $mib_permitted;
}

/**
 * Return cached MIBs array available for device (from os definitions)
 * By default includes sysORID-supplied MIBs unless check_sysORID is false
 * When requesting list without sysORID, result will NOT be cached
 *
 * @param array|integer $device Device array
 * @param boolean $check_sysORID Check or not mibs from sysORID table
 * @param array $mibs_order Order how load available mibs. Default: ['model', 'os', 'group', 'default']
 * @return array List of supported MIBs
 */
function get_device_mibs($device, $check_sysORID = TRUE, $mibs_order = NULL)
{
  global $config, $cache;

  if (is_numeric($device))
  {
    $device_id = $device;
    $device    = device_by_id_cache($device_id);
  } else {
    $device_id = $device['device_id'];
  }

  // Set custom mibs order
  $mibs_order_default = array('model', 'os', 'group', 'default');
  if      (empty($mibs_order))
  {
    // Default order: per-model mibs (if model set) -> os mibs -> os group mibs -> default mibs
    $mibs_order = $mibs_order_default;
  }
  else if (!is_array($mibs_order))
  {
    // Order can passed as string with comma: 'model,os,group,default'
    $mibs_order = explode(',', $mibs_order);
  }

  // Check if custom order used, than set first from passed argument, second from default
  if ($mibs_order_custom = $mibs_order !== $mibs_order_default)
  {
    // Set first from passed argument, second
    $mibs_order = array_unique(array_merge($mibs_order, $mibs_order_default));
  }

  // Do not cache MIBs if custom order used, unknown $device_id or in PHPUNIT
  $use_cache = $device_id && !$mibs_order_custom && !defined('__PHPUNIT_PHAR__');

  // Cache main device MIBs list
  if (!isset($cache['devices']['mibs'][$device_id]))
  {
    $mibs = array();
    $model_array = get_model_array($device);
    foreach ($mibs_order as $order)
    {
      switch ($order)
      {
        case 'model':
          if (is_array($model_array) && isset($model_array['mibs']))
          {
            $mibs = array_merge($mibs, (array)$model_array['mibs']);
          }
          break;
        case 'os':
          $mibs = array_merge((array)$mibs, (array)$config['os'][$device['os']]['mibs']);
          break;
        case 'group':
          $os_group = $config['os'][$device['os']]['group'];
          $mibs = array_merge((array)$mibs, (array)$config['os_group'][$os_group]['mibs']);
          break;
        case 'default':
          //var_dump($config['os_group']['default']['mibs']);
          $mibs = array_merge((array)$mibs, (array)$config['os_group']['default']['mibs']);
          break;
      }
    }
    $mibs = array_unique($mibs);

    //$mibs = array_unique(array_merge((array)$mibs, (array)$config['os'][$device['os']]['mibs'],
    //                                 (array)$config['os_group'][$config['os'][$device['os']]['group']]['mibs'],
    //                                 (array)$config['os_group']['default']['mibs']));

    // Remove blacklisted MIBs from array
    $mibs = array_diff($mibs, get_device_mibs_blacklist($device));

    if ($use_cache)
    {
      $cache['devices']['mibs'][$device_id] = $mibs;
    }
  } else {
    $mibs = $cache['devices']['mibs'][$device_id];
  }
  //print_error('$cache[\'devices\'][\'mibs\'][$device_id]');
  //print_vars($cache['devices']['mibs'][$device_id]);
  //print_vars($mibs);

  // Add and cache sysORID supplied MIBs if any
  if ($check_sysORID)
  {
    if (!isset($cache['devices']['mibs_sysORID'][$device_id]))
    {
      $sysORID = json_decode(get_entity_attrib('device', $device, 'sysORID'), TRUE);
      if (is_array($sysORID))
      {
        // Leave only not exist in main MIBs and blacklist
        $sysORID = array_diff($sysORID, get_device_mibs_blacklist($device), $mibs);
        //print_vars($sysORID);
      } else {
        $sysORID = array(); // Leave empty
      }
      if ($use_cache)
      {
        $cache['devices']['mibs_sysORID'][$device_id] = $sysORID;
      }
    } else {
      $sysORID = $cache['devices']['mibs_sysORID'][$device_id];
    }
    // Attach sysORID MIBs
    $mibs = array_merge($mibs, (array)$sysORID);
  }

  //print_error('$mibs');
  return $mibs;
}

/**
 * Return array of permitted MIBs for device
 *
 * @param array $device
 * @param mixed $mibs_order
 *
 * @return array All MIB permitted for device
 */
function get_device_mibs_permitted($device, $mibs_order = NULL)
{
  $mibs = [];
  foreach (get_device_mibs($device, TRUE, $mibs_order) as $mib)
  {
    if (is_device_mib($device, $mib))
    {
      $mibs[] = $mib;
    }
  }

  return $mibs;
}

/**
 * Return array with blacklisted MIBs for current device
 *
 * @param array $device Device array
 * @return array Blacklisted MIBs
 */
function get_device_mibs_blacklist(array $device)
{
  global $config;
  $blacklist = array_unique(array_merge((array)$config['os'][$device['os']]['mib_blacklist'],
                                        (array)$config['os_group'][$config['os'][$device['os']]['group']]['mib_blacklist']));
  return $blacklist;
}

/**
 * Return array from DB with disabled mibs for device
 *
 * @param array|integer $device Device array
 * @return array List of disabled MIBs for device
 */
function get_device_mibs_disabled($device)
{
  global $cache;

  if (is_numeric($device))
  {
    $device_id = $device;
    $device    = device_by_id_cache($device_id);
  } else {
    $device_id = $device['device_id'];
  }

  // Return cached
  if (isset($cache['devices']['mibs_disabled'][$device_id]))
  {
    return $cache['devices']['mibs_disabled'][$device_id];
  }

  // CLEANME. Compatibility, remove in r10000, but not before CE 0.19.4 (Apr, 2019)
  if (get_db_version() < 405)
  {
    $disabled = [];
    // Old disabled mibs stored in device attribs
    foreach (get_entity_attribs('device', $device['device_id']) as $attrib => $value)
    {
      if (str_starts($attrib, 'mib_') && $value)
      {
        $disabled[] = substr($attrib, 4);
      }
    }

    $cache['devices']['mibs_disabled'][$device_id] = $disabled;
    return $disabled;
  }

  $params = [$device['device_id'], 'mib', '1'];
  $where  = "`device_id` = ? AND `use` = ? AND `disabled` = ?";

  if ($disabled = dbFetchColumn("SELECT `mib` FROM `devices_mibs` WHERE $where", $params))
  {
    $cache['devices']['mibs_disabled'][$device_id] = $disabled;
  } else {
    // If none disabled, cache anyway for do not query db again
    $cache['devices']['mibs_disabled'][$device_id] = [];
  }

  return $cache['devices']['mibs_disabled'][$device_id];
}

/**
 * Set mib disabled in DB for device.
 * Return IDs added or changed in DB.
 *
 * @param array $device Device array
 * @param string $mib MIB name
 * @param boolean $remove Remove MIB db entry complete
 * @param boolean $disabled TRUE for disable, FALSE for enable
 *
 * @return integer ID of db entry for disabled MIB
 */
function set_device_mib_disable($device, $mib, $remove = FALSE, $disabled = TRUE)
{
  if (empty($mib))
  {
    // MIB name required
    print_debug(__FUNCTION__ . "() required non empty mib name.");
    return FALSE;
  }

  if (is_numeric($device))
  {
    $device_id = $device;
    $device    = device_by_id_cache($device_id);
  }

  // Fetch/validate if MIB in db
  $mib_db = dbFetchRow("SELECT `mib_id`, `disabled` FROM `devices_mibs` WHERE `device_id` = ? AND `use` = ? AND `mib` = ?", [$device['device_id'], 'mib', $mib]);

  // Just delete from DB if remove requested
  if ($remove)
  {
    if ($mib_db['mib_id'])
    {
      return dbDelete('devices_mibs', '`mib_id` = ?', [$mib_db['mib_id']]);
    } else {
      return FALSE;
    }
  }

  // Convert to sql boolean
  $disabled = ($disabled ? '1' : '0');

  if (!$mib_db['mib_id'])
  {
    // Not exist, insert
    return dbInsert(array('device_id' => $device['device_id'], 'mib' => $mib,
                          'use' => 'mib', 'disabled' => '1'), 'devices_mibs');
  }
  else if ($mib_db['disabled'] != $disabled)
  {
    // Exist, but changed
    dbUpdate(array('disabled' => $disabled), 'devices_mibs', '`mib_id` = ?', array($mib_db['mib_id']));
  }

  return $mib_db['mib_id'];
}

/**
 * Set mib enabled in DB for device.
 * Return IDs added or changed in DB.
 *
 * @param array $device Device array
 * @param string $mib MIB name
 * @param boolean $remove Remove MIB db entry complete
 * @param boolean $disabled TRUE for disable, FALSE for enable
 *
 * @return integer ID of db entry for disabled MIB
 */
function set_device_mib_enable($device, $mib, $remove = FALSE, $disabled = FALSE)
{
  // Call to disable, just with different default
  return set_device_mib_disable($device, $mib, $remove, $disabled);
}

/**
 * Return array from DB with disabled Objects for device and specified MIB name
 *
 * @param array|integer $device Device array
 * @param string $mib MIB name
 *
 * @return array List of disabled MIBs for device
 */
function get_device_objects_disabled($device, $mib = NULL)
{
  global $cache;

  if (is_numeric($device))
  {
    $device_id = $device;
    $device    = device_by_id_cache($device_id);
  } else {
    $device_id = $device['device_id'];
  }

  $params = [$device['device_id'], 'oid', '1'];
  $where  = "`device_id` = ? AND `use` = ? AND `disabled` = ?";
  if (empty($mib))
  {
    // For empty mib see NULL or empty string
    // This is common for numeric Oids
    $where .= " AND (`mib` = '' OR `mib` IS NULL)";
    $mib = '__mib'; // Just for caching
  } else {
    $where .= " AND `mib` = ?";
    $params[] = $mib;
  }

  // Return cached
  if (isset($cache['devices']['objects_disabled'][$device_id][$mib]))
  {
    return $cache['devices']['objects_disabled'][$device_id][$mib];
  }

  // Query db for objects
  if ($disabled = dbFetchColumn("SELECT `object` FROM `devices_mibs` WHERE $where", $params))
  {
    $cache['devices']['objects_disabled'][$device_id][$mib] = $disabled;
  } else {
    // If none disabled, cache anyway for do not query db again
    $cache['devices']['objects_disabled'][$device_id][$mib] = [];
  }

  return $cache['devices']['objects_disabled'][$device_id][$mib];
}

/**
 * Set object disabled in DB for device.
 * Return IDs added or changed in DB.
 *
 * @param array $device Device array
 * @param string $object Object name
 * @param string $mib MIB name (optional)
 * @param boolean $remove Remove MIB db entry complete
 * @param boolean $disabled TRUE for disable, FALSE for enable
 *
 * @return integer ID of db entry for disabled MIB
 */
function set_device_object_disable($device, $object, $mib = '', $remove = FALSE, $disabled = TRUE)
{
  if (empty($object))
  {
    // MIB name required
    print_debug(__FUNCTION__ . "() required non empty object name.");
    return FALSE;
  }

  if (is_numeric($device))
  {
    $device_id = $device;
    $device    = device_by_id_cache($device_id);
  }

  // Initial insert array
  $insert_array = [ 'device_id' => $device['device_id'], 'object' => $object,
                    'use' => 'object', 'disabled' => '1' ];

  $where = '`device_id` = ? AND `use` = ? AND `object` = ?';
  $params = [$device['device_id'], 'object', $object];
  if (empty($mib))
  {
    // For empty mib see NULL or empty string
    // This is common for numeric Oids
    $where .= " AND (`mib` = '' OR `mib` IS NULL)";
  } else {
    $where .= " AND `mib` = ?";
    $params[] = $mib;

    // Append mib to insert
    $insert_array['mib'] = $mib;
  }
  // Fetch/validate if MIB in db
  $mib_db = dbFetchRow("SELECT `mib_id`, `disabled` FROM `devices_mibs` WHERE $where", $params);

  // Just delete from DB if remove requested
  if ($remove)
  {
    if ($mib_db['mib_id'])
    {
      return dbDelete('devices_mibs', '`mib_id` = ?', [$mib_db['mib_id']]);
    } else {
      return FALSE;
    }
  }

  // Convert to sql boolean
  $disabled = ($disabled ? '1' : '0');

  if (!$mib_db['mib_id'])
  {
    // Not exist, insert
    return dbInsert($insert_array, 'devices_mibs');
  }
  else if ($mib_db['disabled'] != $disabled)
  {
    // Exist, but changed
    dbUpdate(array('disabled' => $disabled), 'devices_mibs', '`mib_id` = ?', array($mib_db['mib_id']));
  }

  return $mib_db['mib_id'];
}

/**
 * Set object enabled in DB for device.
 * Return IDs added or changed in DB.
 *
 * @param array $device Device array
 * @param string $object Object name
 * @param string $mib MIB name (optional)
 * @param boolean $remove Remove MIB db entry complete
 * @param boolean $disabled TRUE for disable, FALSE for enable
 *
 * @return integer ID of db entry for disabled MIB
 */
function set_device_object_enable($device, $object, $mib = '', $remove = FALSE, $disabled = FALSE)
{
  // Call to disable, just with different default
  return set_device_object_disable($device, $object, $mib, $remove, $disabled);
}

/* End mib functions */

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_entity_by_id_cache($entity_type, $entity_id)
{
  global $cache;

  $translate = entity_type_translate_array($entity_type);

  if (is_array($cache[$entity_type][$entity_id]))
  {

    return $cache[$entity_type][$entity_id];

  } else {

    switch($entity_type)
    {
      case "bill":
        if (function_exists('get_bill_by_id'))
        {
          $entity = get_bill_by_id($entity_id);
        }
        break;

      case "port":
        $entity = get_port_by_id($entity_id);
        break;

      default:
        $sql = 'SELECT * FROM `'.$translate['table'].'`';

        if (isset($translate['state_table']))
        {
          $sql .= ' LEFT JOIN `'.$translate['state_table'].'` USING (`'.$translate['id_field'].'`)';
        }

          if (isset($translate['parent_table']))
          {
              $sql .= ' LEFT JOIN `'.$translate['parent_table'].'` USING (`'.$translate['parent_id_field'].'`)';
          }

        $sql .= ' WHERE `'.$translate['table'].'`.`'.$translate['id_field'].'` = ?';

        $entity = dbFetchRow($sql, array($entity_id));
        if (function_exists('humanize_'.$entity_type)) { $do = 'humanize_'.$entity_type; $do($entity); }
        else if (isset($translate['humanize_function']) && function_exists($translate['humanize_function'])) { $do = $translate['humanize_function']; $do($entity); }
        break;
    }

    if (is_array($entity))
    {
      entity_rewrite($entity_type, $entity);
      $cache[$entity_type][$entity_id] = $entity;
      return $entity;
    }
  }

  return FALSE;
}

/* Network/ARP/MAC specific entity functions */

/**
 * Fetch entity IDs by network. Currently supported entities: device, port, ip (ipv4, ipv6 for force specific IP version)
 *
 * See parse_network() for possible valid network queries.
 *
 * @param string       $entity_type  Entity type (device, port)
 * @param string|array $network      Valid network string (or array)
 * @param string       $add_where    Custom where string
 *
 * @return array       Array with entity specific IDs
 */
function get_entity_ids_ip_by_network($entity_type, $network, $add_where = '')
{

  // Recursive query for array of networks
  if (is_array($network))
  {
    $ids = array();
    foreach ($network as $entry)
    {
      if ($entry_ids = get_entity_ids_ip_by_network($entity_type, $entry, $add_where))
      {
        $ids = array_merge($ids, $entry_ids);
      }
    }

    return $ids;
  }

  // Parse for valid network string
  $network_array = parse_network($network);
  //print_vars($network_array);
  if (!$network_array)
  {
    // Incorrect network/address string passed
    return FALSE;
  }

  $query = 'SELECT ';
  $join  = '';
  $where = ' WHERE 1 ';
  switch ($entity_type)
  {
    case 'ipv4':
      // Force request IPv6 address
      $network_array['ip_type'] = 'ipv4';
      $query .= ' `ipv4_address_id`';
      break;
    case 'ipv6':
      // Force request IPv6 address
      $network_array['ip_type'] = 'ipv6';
      $query .= ' `ipv6_address_id`';
      break;
    case 'ip':
      $query .= ($network_array['ip_version'] == 4) ? ' `ipv4_address_id`' : ' `ipv6_address_id`';
      break;

    case 'device':
    case 'devices':
      $query .= ' DISTINCT `device_id`';
      break;

    case 'port':
    case 'ports':
      $query .= ' DISTINCT `port_id`';
      break;
  }

  switch ($network_array['ip_type'])
  {
    case 'ipv4':
      $query .= ' FROM `ipv4_addresses`';
      if ($network_array['query_type'] == 'single')
      {
        // Exactly IP match
        $where .= ' AND `ipv4_binary` = ?';
        $param[] = $network_array['address_binary'];
      }
      else if ($network_array['query_type'] == 'network')
      {
        // Match IP in network
        $where .= ' AND `ipv4_binary` >= ? AND `ipv4_binary` <= ?';
        $param[] = $network_array['network_start_binary'];
        $param[] = $network_array['network_end_binary'];
      } else {
        // Match IP addresses by part of string
        $where .=  generate_query_values($network_array['address'], 'ipv4_address', $network_array['query_type']);
      }
      break;
    case 'ipv6':
      $query .= ' FROM `ipv6_addresses`';
      if ($network_array['query_type'] == 'single')
      {
        // Exactly IP match
        $where .= ' AND `ipv6_binary` = ?';
        $param[] = $network_array['address_binary'];
      }
      else if ($network_array['query_type'] == 'network')
      {
        // Match IP in network
        $where .= ' AND `ipv6_binary` >= ? AND `ipv6_binary` <= ?';
        $param[] = $network_array['network_start_binary'];
        $param[] = $network_array['network_end_binary'];
      } else {
        // Match IP addresses by part of string
        $where .= ' AND (' . generate_query_values($network_array['address'], 'ipv6_address',    $network_array['query_type'], FALSE) .
                  ' OR '   . generate_query_values($network_array['address'], 'ipv6_compressed', $network_array['query_type'], FALSE) . ')';
      }
      break;
  }

  if (FALSE)
  {
    // Ignore disabled/deleted/ignored
    $where .= ' AND `device_id` NOT IN (SELECT `device_id` FROM `devices` WHERE `disabled` = "1" OR `ignore` = "1")';
    $where .= ' AND `port_id` NOT IN (SELECT `port_id` FROM `ports` WHERE `deleted` = "1" OR `ignore` = "1")';
  } else {
    // Always ignore deleted ports
    $join = ' LEFT JOIN `ports` USING (`device_id`, `port_id`) ';
    // IS NULL allow to search addresses without associated port
    $where .= ' AND (`ports`.`deleted` != "1" OR `ports`.`deleted` IS NULL)';
  }
  $query .= $join;
  $where .= $add_where; // Additional query, ie limit by device_id or port_id

  $ids = dbFetchColumn($query . $where, $param);
  //$ids = dbFetchColumn($query . $where, $param);
  //print_vars($ids);

  return $ids;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function entity_type_translate($entity_type)
{
  $data = entity_type_translate_array($entity_type);
  if (!is_array($data)) { return NULL; }

  return array($data['table'], $data['id_field'], $data['name_field'], $data['ignore_field'], $data['entity_humanize']);
}

// Returns a text name from an entity type and an id
// A little inefficient.
// DOCME needs phpdoc block
// TESTME needs unit testing
function entity_name($type, $entity)
{
  global $config, $entity_cache;

  if (is_numeric($entity))
  {
    $entity = get_entity_by_id_cache($type, $entity);
  }

  $translate = entity_type_translate_array($type);

  $text = $entity[$translate['name_field']];

  return($text);
}

// Returns a text name from an entity type and an id
// A little inefficient.
// DOCME needs phpdoc block
// TESTME needs unit testing
function entity_short_name($type, $entity)
{
  global $config, $entity_cache;

  if (is_numeric($entity))
  {
    $entity = get_entity_by_id_cache($type, $entity);
  }

  $translate = entity_type_translate_array($type);

  $text = $entity[$translate['name_field']];

  return($text);
}

// Returns a text description from an entity type and an id
// A little inefficient.
// DOCME needs phpdoc block
// TESTME needs unit testing
function entity_descr($type, $entity)
{
  global $config, $entity_cache;

  if (is_numeric($entity))
  {
    $entity = get_entity_by_id_cache($type, $entity);
  }

  $translate = entity_type_translate_array($type);

  $text = $entity[$translate['entity_descr_field']];

  return($text);
}
/**
 * Generate entity description based on their type and discovery definition.
 *
 * @param string $entity_type Entity type
 * @param array $definition Entity discovery definition entry
 * @param array $descr_entry Array with possible descr strings received for example from snmpwalk, also used for tag replaces
 * @param integer $count Optional entity count for current Table, when > 1 descr can use optional index or different descr
 * @return string Parsed entity description
 */
function entity_descr_definition($entity_type, $definition, $descr_entry, $count = 1)
{
  //print_vars($definition);
  //print_vars($descr_entry);

  // Per index definitions
  if (isset($descr_entry['index']) && isset($definition['indexes'][$descr_entry['index']]['descr']))
  {
    // Override with per index descr definition
    $definition['descr'] = $definition['indexes'][$descr_entry['index']]['descr'];
  }

  // Descr contain tags, prefer tag replaces
  $use_tags = isset($definition['descr']) && str_contains($definition['descr'], '%');

  if (isset($definition['oid_descr']) && strpos($definition['oid_descr'], '::'))
  {
    list($mib, $definition['oid_descr']) = explode("::", $definition['oid_descr']);
  }
  if (isset($definition['oid_descr']) && strlen($descr_entry[$definition['oid_descr']]))
  {
    $descr = $descr_entry[$definition['oid_descr']];
    if (!$use_tags)
    {
      // not tags and oid_descr exist, just return it
      if (isset($definition['descr_transform']))
      {
        $descr = string_transform($descr, $definition['descr_transform']);
      }
      return $descr;
    }

    // Append to array for correct replace
    $descr_entry['oid_descr'] = $descr;
  }

  if (isset($definition['descr']))
  {
    // Use definition descr
    $descr = $definition['descr'];

    if ($use_tags)
    {

      // Multipart index: Oid.0.1.2 -> %index0%, %index1%, %index2%
      if (isset($descr_entry['index']) && preg_match('/%index\d+%/', $descr))
      {
        // Multipart index
        foreach (explode('.', $descr_entry['index']) as $k => $k_index)
        {
          $descr_entry['index'.$k] = $k_index; // Multipart indexes
        }
      }

      // Replace tags %tag%
      $descr = array_tag_replace($descr_entry, $descr);
    }
    else if ($count > 1 && isset($descr_entry['index']))
    {
      // For multi append index, but always prefer to USE TAGS!
      $descr .= ' ' . $descr_entry['index'];
    }

  }

  // Use Entity Defaults (not defined descr or oid_descr)
  if (!strlen($descr))
  {
    $translate = entity_type_translate_array($entity_type);
    //print_vars($translate);

    if ($count > 1 && isset($translate['name_multiple']))
    {
      $descr = $translate['name_multiple'];
    }
    else if (isset($translate['name']))
    {
      $descr = $translate['name'];
    } else {
      //$descr = 'Entity';
      $descr = nicecase($entity_type);
    }

    if ($count > 1 && isset($descr_entry['index']))
    {
      // For multi append index
      $descr .= ' ' . $descr_entry['index'];
    }
  }

  // Transform/clean definition
  if (isset($definition['descr_transform']))
  {
    $descr = string_transform($descr, $definition['descr_transform']);
  }

  return $descr;
}

/**
 * Rule-based entity linking.
 *
 * @param $entity_type
 * @param $definition
 * @param $device
 * @param $entity
 *
 * @return array
 */
function entity_measured_match_definition($device, $definition, $entity, $entity_type = NULL)
{
  // $entity_type unused currently

  $options = array();
  if (!isset($definition['measured_match'])) { return $options; }

  // Convert single match array to multi-array
  if (isset($definition['measured_match']['match']))
  {
    $definition['measured_match'] = array($definition['measured_match']);
  }

  $measured_found = FALSE;
  foreach ($definition['measured_match'] as $rule)
  {

    // Allow tests here to allow multiple match rules on the same sensor table (entity-mib, etc)

    // First we make substitutions using %key%, then we run transformations

    $rule['match'] = array_tag_replace($entity, $rule['match']);

    if (is_array($rule['transform'])) { string_transform($rule['match'], $rule['transform']); }

    switch($rule['entity_type'])
    {
      case 'port':

        switch($rule['field'])
        {
          case 'ifDescr':
          case 'label':
            $sql = "SELECT `port_id`, `ifIndex` FROM `ports` WHERE `device_id` = ? AND (`ifDescr` = ? OR `ifName` = ? OR `port_label` = ? OR `port_label_short` = ?) AND `deleted` = ? LIMIT 1";
            $params = array($device['device_id'], $rule['match'], $rule['match'], $rule['match'], $rule['match'], 0);
            if ($measured = dbFetchRow($sql, $params))
            {
              $options['measured_class']            = 'port';
              $options['measured_entity']           = $measured['port_id'];
              $options['port_label']                = $measured['port_label'];
              print_debug('Linked to port '.$measured['port_id'].' via ifDescr');
              $measured_found = TRUE;
            }
            break;

          case 'ifAlias':
            if ($measured = dbFetchRow("SELECT `port_id`, `ifIndex` FROM `ports` WHERE `device_id` = ? AND `ifAlias` = ? AND `deleted` = ? LIMIT 1", array($device['device_id'], $rule['match'], 0)))
            {
              $options['measured_class']            = 'port';
              $options['measured_entity']           = $measured['port_id'];
              $options['port_label']                = $measured['port_label'];
              print_debug('Linked to port '.$measured['port_id'].' via ifAlias');
              $measured_found = TRUE;
            }
            break;

          case 'ifIndex':
            if ($measured = get_port_by_index_cache($device['device_id'], $rule['match']))
            {
              $options['measured_class']            = 'port';
              $options['measured_entity']           = $measured['port_id'];
              //$options['entPhysicalIndex']          = $measured['ifIndex'];
              $options['port_label']                = $measured['port_label'];
              print_debug('Linked to port '.$measured['port_id'].' via ifIndex');
              $measured_found = TRUE;
            }
            break;
        }

        break;
    }
    if ($measured_found) { break; } // Stop foreach if measured match found
  }

  return $options;
}

/**
 * Translate an entity type to the relevant table and the identifier field name
 *
 * @param string entity_type
 * @return string entity_table
 * @return array entity_id
*/
// TESTME needs unit testing
function entity_type_translate_array($entity_type)
{
  $translate = $GLOBALS['config']['entities'][$entity_type];

  // Base fields
  // FIXME, not listed here: agg_graphs, metric_graphs
  $fields = array('name',               // Base entity name
                  'name_multiple',      // (Optional) Plural entity name
                  'table',              // Table name
                  'table_fields',       // Array with table fields
                  //'state_table',        // State table name (deprecated)
                  //'state_fields',       // Array with state fields (deprecated)
                  'humanize_function',  // Humanize function name
                  'parent_type',        // Parent table type
                  'parent_table',       // Parent table name
                  'parent_id_field',    // Main parent id field
                  'where',
                  'icon',               // Entity icon
                  'graph');
  foreach ($fields as $field)
  {
    if (isset($translate[$field]))
    {
      $data[$field] = $translate[$field];
    }
    else if (isset($GLOBALS['config']['entities']['default'][$field]))
    {
      $data[$field] = $GLOBALS['config']['entities']['default'][$field];
    }
  }

  // Table fields
  $fields_table = array(// Common fields
                        'id',
                        'device_id',
                        'index',
                        'mib',
                        'object',
                        'oid',
                        'name',
                        'shortname',
                        'descr',
                        'ignore',
                        'disable',
                        'deleted',
                        // Limits fields
                        'limit_high',       // High critical limit
                        'limit_high_warn',  // High warning  limit
                        'limit_low',        // Low  critical limit
                        'limit_low_warn',   // Low  warning  limit
                        // Value fields
                        'value',            // RAW value
                        'status',           // Entity specific status name (
                        'event',            // Event name (ok, alert, warning, etc..)
                        'uptime',           // Uptime
                        'last_change',      // Last changed time
                        // Measured entity fields
                        'measured_type',    // Measured entity type
                        'measured_id',      // Measured entity id
    );
  if (isset($translate['table_fields']))
  {
    // New definition style
    foreach ($translate['table_fields'] as $field => $entry)
    {
      // Add old style name (ie 'id_field') for compatibility
      $data[$field . '_field'] = $entry;
    }
  }

  return $data;
}

/**
 * Returns TRUE if the logged in user is permitted to view the supplied entity.
 *
 * @param $entity_id
 * @param $entity_type
 * @param $device_id
 * @param $permissions Permissions array, by default used global var $permissions generated by permissions_cache()
 *
 * @return bool
 */
// TESTME needs unit testing
function is_entity_permitted($entity_id, $entity_type, $device_id = NULL, $permissions = NULL)
{
  if (is_null($permissions) && isset($GLOBALS['permissions']))
  {
    // Note, pass permissions array by param used in permissions_cache()
    $permissions = $GLOBALS['permissions'];
  }

  //if (OBS_DEBUG)
  //{
  //  print_vars($permissions);
  //  print_vars($_SESSION);
  //  print_vars($GLOBALS['auth']);
  //  print_vars(is_graph());
  //}

  if (!is_numeric($device_id)) { $device_id = get_device_id_by_entity_id($entity_id, $entity_type); }

  if (isset($_SESSION['user_limited']) && !$_SESSION['user_limited'])
  {
    // User not limited (userlevel >= 5)
    $allowed = TRUE;
  }
  else if (is_numeric($device_id) && device_permitted($device_id))
  {
    $allowed = TRUE;
  }
  else if (isset($permissions[$entity_type][$entity_id]) && $permissions[$entity_type][$entity_id])
  {
    $allowed = TRUE;
  }
  else if (isset($GLOBALS['auth']) && is_graph())
  {
    $allowed = $GLOBALS['auth'];
  } else {
    $allowed = FALSE;
  }

  if (OBS_DEBUG)
  {
    $debug_msg = "PERMISSIONS CHECK. Entity type: $entity_type, Entity ID: $entity_id, Device ID: ".($device_id ? $device_id : 'NULL').", Allowed: ".($allowed ? 'TRUE' : 'FALSE').".";
    if (isset($GLOBALS['notifications']))
    {
      $GLOBALS['notifications'][] = array('text' => $debug_msg, 'severity' => 'debug');
    } else {
      print_debug($debug_msg);
    }
  }
  return $allowed;
}

/**
 * Generates standardised set of array fields for use in entity-generic functions and code.
 * Has no return value, it modifies the $entity array in-place.
 *
 * @param $entity_type string
 * @param $entity array
 *
 */
// TESTME needs unit testing
function entity_rewrite($entity_type, &$entity)
{
  $translate = entity_type_translate_array($entity_type);

  // By default, fill $entity['entity_name'] with name_field contents.
  if (isset($translate['name_field'])) { $entity['entity_name'] = $entity[$translate['name_field']]; }

  // By default, fill $entity['entity_shortname'] with shortname_field contents. Fallback to entity_name when field name is not set.
  if (isset($translate['shortname_field'])) { $entity['entity_shortname'] = $entity[$translate['name_field']]; } else { $entity['entity_shortname'] = $entity['entity_name']; }

  // By default, fill $entity['entity_descr'] with descr_field contents.
  if (isset($translate['descr_field'])) { $entity['entity_descr'] = $entity[$translate['descr_field']]; }

  // By default, fill $entity['entity_id'] with id_field contents.
  if (isset($translate['id_field'])) { $entity['entity_id'] = $entity[$translate['id_field']]; }

  switch($entity_type)
  {
    case "bgp_peer":
      // Special handling of name/shortname/descr for bgp_peer, since it combines multiple elements.

      if (Net_IPv6::checkIPv6($entity['bgpPeerRemoteAddr']))
      {
        $addr = Net_IPv6::compress($entity['bgpPeerRemoteAddr']);
      } else {
        $addr = $entity['bgpPeerRemoteAddr'];
      }

      $entity['entity_name']      = "AS".$entity['bgpPeerRemoteAs'] ." ". $addr;
      $entity['entity_shortname'] = $addr;
      $entity['entity_descr']     = $entity['astext'];
      break;

    case "sla":
      $entity['entity_name']      = 'SLA #' . $entity['sla_index'];
      if (!empty($entity['sla_target']) && ($entity['sla_target'] != $entity['sla_tag']))
      {
        if (get_ip_version($entity['sla_target']) === 6)
        {
          $sla_target = Net_IPv6::compress($entity['sla_target'], TRUE);
        } else {
          $sla_target = $entity['sla_target'];
        }
        $entity['entity_name']   .= ' (' . $entity['sla_tag'] . ': ' . $sla_target . ')';
      } else {
        $entity['entity_name']   .= ' (' . $entity['sla_tag'] . ')';
      }
      $entity['entity_shortname'] = "#". $entity['sla_index'] . " (". $entity['sla_tag'] . ")";
      break;

    case "pseudowire":
      $entity['entity_name']      = $entity['pwID'] . ($entity['pwDescr'] ? " (". $entity['pwDescr'] . ")" : '');
      $entity['entity_shortname'] = $entity['pwID'];
      break;
  }
}

/**
 * Generates a URL to reach the entity's page (or the most specific list page the entity appears on)
 * Has no return value, it modifies the $entity array in-place.
 *
 * @param $entity_type string
 * @param $entity array
 *
 */
// TESTME needs unit testing
function generate_entity_link($entity_type, $entity, $text = NULL, $graph_type = NULL, $escape = TRUE, $options = FALSE)
{
  if (is_numeric($entity))
  {
    $entity = get_entity_by_id_cache($entity_type, $entity);
  }
  // Compat with old boolean $short option
  if (is_array($options))
  {
    $short = isset($options['short']) && $options['short'];
    $icon  = isset($options['icon'])  && $options['icon'];
  } else {
    $short = $options;
    $icon  = FALSE;
  }
  if ($icon)
  {
    // Get entity icon and force do not escape
    $text = get_icon($GLOBALS['config']['entities'][$entity_type]['icon']);
    $escape = FALSE;
  }

  entity_rewrite($entity_type, $entity);

  switch($entity_type)
  {
    case "device":
      if ($icon)
      {
        $link = generate_device_link($entity, $text, [], FALSE);
      } else {
        $link = generate_device_link($entity, short_hostname($entity['hostname'], 16));
      }
      break;
    case "mempool":
      $url = generate_url(array('page' => 'device', 'device' => $entity['device_id'], 'tab' => 'health', 'metric' => 'mempool'));
      break;
    case "processor":
      //r($entity);
      if (isset($entity['id']) && is_array($entity['id']))
      {
        // Multi-processors list
        $ids = implode(',', $entity['id']);
        $entity['entity_id'] = $ids;
      } else {
        $ids = $entity['processor_id'];
      }
      $url = generate_url(array('page' => 'device', 'device' => $entity['device_id'], 'tab' => 'health', 'metric' => 'processor', 'processor_id' => $ids));
      break;
    case "status":
      $url = generate_url(array('page' => 'device', 'device' => $entity['device_id'], 'tab' => 'health', 'metric' => 'status', 'id' => $entity['status_id']));
      break;
    case "sensor":
      $url = generate_url(array('page' => 'device', 'device' => $entity['device_id'], 'tab' => 'health', 'metric' => $entity['sensor_class'], 'id' => $entity['sensor_id']));
      break;
    case "counter":
      $url = generate_url(array('page' => 'device', 'device' => $entity['device_id'], 'tab' => 'health', 'metric' => 'counter', 'id' => $entity['counter_id']));
      break;
    case "printersupply":
      $url = generate_url(array('page' => 'device', 'device' => $entity['device_id'], 'tab' => 'printing', 'supply' => $entity['supply_type']));
      break;
    case "port":
      if ($icon)
      {
        $link = generate_port_link($entity, $text, $graph_type, FALSE, FALSE);
      } else {
        $link = generate_port_link($entity, NULL, $graph_type, $escape, $short);
      }
      break;
    case "storage":
      $url = generate_url(array('page' => 'device', 'device' => $entity['device_id'], 'tab' => 'health', 'metric' => 'storage'));
      break;
    case "bgp_peer":
      $url = generate_url(array('page' => 'device', 'device' => ($entity['peer_device_id'] ? $entity['peer_device_id'] : $entity['device_id']), 'tab' => 'routing', 'proto' => 'bgp'));
      break;
    case "netscalervsvr":
      $url = generate_url(array('page' => 'device', 'device' => $entity['device_id'], 'tab' => 'loadbalancer', 'type' => 'netscaler_vsvr', 'vsvr' => $entity['vsvr_id']));
      break;
    case "netscalersvc":
      $url = generate_url(array('page' => 'device', 'device' => $entity['device_id'], 'tab' => 'loadbalancer', 'type' => 'netscaler_services', 'svc' => $entity['svc_id']));
      break;
    case "netscalersvcgrpmem":
      $url = generate_url(array('page' => 'device', 'device' => $entity['device_id'], 'tab' => 'loadbalancer', 'type' => 'netscaler_servicegroupmembers', 'svc' => $entity['svc_id']));
      break;
    case "p2pradio":
      $url = generate_url(array('page' => 'device', 'device' => $entity['device_id'], 'tab' => 'p2pradios'));
      break;
    case "sla":
      $url = generate_url(array('page' => 'device', 'device' => $entity['device_id'], 'tab' => 'slas', 'id' => $entity['sla_id']));
      break;
    case "pseudowire":
      $url = generate_url(array('page' => 'device', 'device' => $entity['device_id'], 'tab' => 'pseudowires', 'id' => $entity['pseudowire_id']));
      break;
    case "maintenance":
      $url = generate_url(array('page' => 'alert_maintenance', 'maintenance' => $entity['maint_id']));
      break;
    case "group":
      $url = generate_url(array('page' => 'group', 'group_id' => $entity['group_id']));
      break;
    case "virtualmachine":
      // If we know this device by its vm name in our system, create a link to it, else just print the name.
      if (get_device_id_by_hostname($entity['vm_name']))
      {
        $link = generate_device_link(device_by_name($entity['vm_name']));
      } else {
        // Hardcode $link to just show the name, no actual link
        $link = $entity['vm_name'];
      }
      break;
    default:
      $url = NULL;
  }

  if (isset($link))
  {
    return $link;
  }

  if (!isset($text))
  {
    if ($short && $entity['entity_shortname'])
    {
      $text = $entity['entity_shortname'];
    } else {
      $text = $entity['entity_name'];
    }
  }
  if ($escape) { $text = escape_html($text); }
  $link = '<a href="' . $url . '" class="entity-popup ' . $entity['html_class'] . '" data-eid="' . $entity['entity_id'] . '" data-etype="' . $entity_type . '">' . $text . '</a>';

  return($link);
}

/**
 * This function generate link to entity, but without any text descriptions
 *
 * @param $entity_type
 * @param $entity
 *
 * @return string
 */
function generate_entity_icon_link($entity_type, $entity)
{
  return generate_entity_link($entity_type, $entity, NULL, NULL, FALSE, ['icon' => TRUE]);
}

/**
 * Fetch rrd filename for specified Entity ID
 *
 * @param string  $entity_type Entity type
 * @param integer $entity_id   Entity ID
 *
 * @return string      RRD filename
 */
function get_entity_rrd_by_id($entity_type, $entity_id)
{
  $entity = get_entity_by_id_cache($entity_type, $entity_id);
  $device = device_by_id_cache($entity['device_id']);

  switch ($entity_type)
  {
    case 'sensor':
      $filename = get_sensor_rrd($device, $entity);
      break;
    case 'status':
      $filename = get_status_rrd($device, $entity);
      break;
    case 'counter':
      $filename = get_counter_rrd($device, $entity);
      break;
  }

  return $filename;
}

// EOF
