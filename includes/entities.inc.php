<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage entities
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

// Include entity specific functions
require_once($config['install_dir'] . "/includes/entities/device.inc.php");
require_once($config['install_dir'] . "/includes/entities/port.inc.php");
require_once($config['install_dir'] . "/includes/entities/processor.inc.php");
require_once($config['install_dir'] . "/includes/entities/storage.inc.php");
require_once($config['install_dir'] . "/includes/entities/sensor.inc.php");
require_once($config['install_dir'] . "/includes/entities/status.inc.php");
require_once($config['install_dir'] . "/includes/entities/counter.inc.php");
if (OBSERVIUM_EDITION !== 'community') {
    include_once($config['install_dir'] . "/includes/entities/probe.inc.php");
}
require_once($config['install_dir'] . "/includes/entities/sla.inc.php");
require_once($config['install_dir'] . "/includes/entities/ip-address.inc.php");
require_once($config['install_dir'] . "/includes/entities/routing.inc.php");
require_once($config['install_dir'] . "/includes/entities/wifi.inc.php");
//require_once($config['install_dir'] . "/includes/entities/p2p-radio.inc.php");

/**
 *
 * Get attribute value for entity
 *
 * @param string $entity_type
 * @param mixed  $entity_id
 * @param string $attrib_type
 *
 * @return string
 */
function get_entity_attrib($entity_type, $entity_id, $attrib_type)
{
    if (is_array($entity_id)) {
        // Passed entity array, instead id
        $translate = entity_type_translate_array($entity_type);
        $entity_id = $entity_id[$translate['id_field']];
    }
    if (!$entity_id) {
        return NULL;
    }

    if (isset($GLOBALS['cache']['entity_attribs'][$entity_type][$entity_id]) &&
        array_key_exists($attrib_type, $GLOBALS['cache']['entity_attribs'][$entity_type][$entity_id])) {
        return $GLOBALS['cache']['entity_attribs'][$entity_type][$entity_id][$attrib_type];
    }
    if (isset($GLOBALS['cache']['entity_attribs_all'][$entity_type][$entity_id])) {
        // when all entity attribs already cached, but specified attrib not exist, prevent extra db query
        return NULL;
    }

    if ($row = dbFetchRow("SELECT `attrib_value` FROM `entity_attribs` WHERE `entity_type` = ? AND `entity_id` = ? AND `attrib_type` = ?", [$entity_type, $entity_id, $attrib_type])) {
        return $row['attrib_value'];
    }

    return NULL;
}

/**
 *
 * Get all attributes for entity
 *
 * @param string $entity_type
 * @param mixed  $entity_id
 *
 * @return array
 */
function get_entity_attribs($entity_type, $entity_id, $refresh = FALSE)
{
    if (is_array($entity_id)) {
        if (isset($entity_id['device_id']) && is_intnum($entity_id['device_id'])) {
            $device_id = $entity_id['device_id'];

            // Pre-check if entity attribs for device exist
            cache_device_attribs_exist($device_id, $refresh);
            // if ($refresh || !isset($GLOBALS['cache']['devices_attribs'][$device_id][$entity_type])) {
            //   $GLOBALS['cache']['devices_attribs'][$device_id][$entity_type] = dbExist('entity_attribs', '`entity_type` = ? AND `device_id` = ?', [ $entity_type, $device_id ]);
            // }
            // Speedup queries, when not exist attribs
            if (!isset($GLOBALS['cache']['devices_attribs'][$device_id][$entity_type]) ||
                !$GLOBALS['cache']['devices_attribs'][$device_id][$entity_type]) {
                return [];
            }
        }

        // Passed entity array, instead id
        $translate = entity_type_translate_array($entity_type);
        $entity_id = $entity_id[$translate['id_field']];
    }

    if (!$entity_id) {
        return NULL;
    }

    if ($refresh || !isset($GLOBALS['cache']['entity_attribs'][$entity_type][$entity_id])) {

        if (isset($device_id)) {
            foreach (dbFetchRows("SELECT * FROM `entity_attribs` WHERE `entity_type` = ? AND `device_id` = ?", [$entity_type, $device_id]) as $entry) {
                $GLOBALS['cache']['entity_attribs'][$entity_type][$entry['entity_id']][$entry['attrib_type']] = $entry['attrib_value'];

                // Set a logical sign that all attributes are cached, for get_entity_attrib()
                $GLOBALS['cache']['entity_attribs_all'][$entity_type][$entry['entity_id']] = TRUE;
            }

        } else {
            $attribs = [];
            foreach (dbFetchRows("SELECT * FROM `entity_attribs` WHERE `entity_type` = ? AND `entity_id` = ?", [$entity_type, $entity_id]) as $entry) {
                $attribs[$entry['attrib_type']] = $entry['attrib_value'];
            }

            $GLOBALS['cache']['entity_attribs'][$entity_type][$entity_id] = $attribs;
            // Set a logical sign that all attributes are cached, for get_entity_attrib()
            $GLOBALS['cache']['entity_attribs_all'][$entity_type][$entity_id] = TRUE;
        }
    }
    return $GLOBALS['cache']['entity_attribs'][$entity_type][$entity_id];
}

/**
 *
 * Set value for specific attribute and entity
 *
 * @param string $entity_type
 * @param mixed  $entity_id
 * @param string $attrib_type
 * @param string $attrib_value
 * @param string $device_id
 *
 * @return boolean
 */
function set_entity_attrib($entity_type, $entity_id, $attrib_type, $attrib_value, $device_id = NULL)
{
    if (is_array($entity_id)) {
        // Passed entity array, instead id
        $translate = entity_type_translate_array($entity_type);
        $entity    = $entity_id;
        $entity_id = $entity[$translate['id_field']];
    }

    if (!$entity_id) {
        return NULL;
    }

    // If we're setting a device attribute, use the entity_id as the device_id
    if ($entity_type === "device") {
        $device_id = $entity_id;
    }

    // If we don't have a device_id, try to work out what it should be
    if (!$device_id) {
        if (isset($entity) && isset($entity['device_id'])) {
            $device_id = $entity['device_id'];
        } else {
            $entity    = get_entity_by_id_cache($entity_type, $entity_id);
            $device_id = $entity['device_id'];
        }
    }
    if (!$device_id) {
        print_error("Enable to set attrib data : $entity_type, $entity_id, $attrib_type, $attrib_value, $device_id");
        return NULL;
    }

    // Reset cached entity attribs
    if (isset($GLOBALS['cache']['entity_attribs'][$entity_type][$entity_id])) {
        unset($GLOBALS['cache']['entity_attribs'][$entity_type][$entity_id]);
    }
    if (isset($GLOBALS['cache']['entity_attribs_all'][$entity_type][$entity_id])) {
        unset($GLOBALS['cache']['entity_attribs_all'][$entity_type][$entity_id]);
    }

    //if (dbFetchCell("SELECT COUNT(*) FROM `entity_attribs` WHERE `entity_type` = ? AND `entity_id` = ? AND `attrib_type` = ?", array($entity_type, $entity_id, $attrib_type)))
    if (dbExist('entity_attribs', '`entity_type` = ? AND `entity_id` = ? AND `attrib_type` = ?', [$entity_type, $entity_id, $attrib_type])) {
        $return = dbUpdate(['attrib_value' => $attrib_value], 'entity_attribs', '`entity_type` = ? AND `entity_id` = ? AND `attrib_type` = ?', [$entity_type, $entity_id, $attrib_type]);
    } else {
        $return = dbInsert(['device_id' => $device_id, 'entity_type' => $entity_type, 'entity_id' => $entity_id, 'attrib_type' => $attrib_type, 'attrib_value' => $attrib_value], 'entity_attribs');
        if ($return !== FALSE) {
            $return = TRUE;
        } // Note dbInsert return IDs if exist or 0 for not indexed tables
    }

    return $return;
}

/**
 *
 * Delete specific attribute for entity
 *
 * @param string $entity_type
 * @param mixed  $entity_id
 * @param string $attrib_type
 *
 * @return boolean
 */
function del_entity_attrib($entity_type, $entity_id, $attrib_type)
{
    if (is_array($entity_id)) {
        // Passed entity array, instead id
        $translate = entity_type_translate_array($entity_type);
        $entity_id = $entity_id[$translate['id_field']];
    }
    if (!$entity_id) {
        return NULL;
    }

    // Reset cached entity attribs
    if (isset($GLOBALS['cache']['entity_attribs'][$entity_type][$entity_id])) {
        unset($GLOBALS['cache']['entity_attribs'][$entity_type][$entity_id]);
    }
    if (isset($GLOBALS['cache']['entity_attribs_all'][$entity_type][$entity_id])) {
        unset($GLOBALS['cache']['entity_attribs_all'][$entity_type][$entity_id]);
    }

    return dbDelete('entity_attribs', '`entity_type` = ? AND `entity_id` = ? AND `attrib_type` = ?', [$entity_type, $entity_id, $attrib_type]);
}

/**
 *
 * Get array of entities (id) linked to device
 *
 * @param mixed $device_id    Device array of id
 * @param mixed $entity_types List of entities as array, if empty get all
 *
 * @return array
 */
function get_device_entities($device_id, $entity_types = NULL)
{
    if (is_array($device_id)) {
        // Passed device array, instead id
        $device_id = $device_id['device_id'];
    }
    if (!$device_id || $entity_types === FALSE) {
        return [];
    }

    if (!is_array($entity_types) && !safe_empty($entity_types)) {
        // Single entity type passed, convert to array
        $entity_types = [$entity_types];
    }
    $all      = safe_empty($entity_types);
    $entities = [];
    foreach (array_keys($GLOBALS['config']['entities']) as $entity_type) {
        if ($all || in_array($entity_type, $entity_types, TRUE)) {
            $translate = entity_type_translate_array($entity_type);
            if (!$translate['device_id_field']) {
                continue;
            }
            $query      = 'SELECT `' . $translate['id_field'] . '` FROM `' . $translate['table'] . '` WHERE `' . $translate['device_id_field'] . '` = ?;';
            $entity_ids = dbFetchColumn($query, [$device_id]);
            if (safe_count($entity_ids)) {
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
 * @param mixed $device_id
 * @param mixed $entity_types
 *
 * @return array
 */
function get_device_entities_attribs($device_id, $entity_types = NULL)
{
    $attribs = [];

    $query = "SELECT * FROM `entity_attribs` WHERE `device_id` = ?";
    if ($entity_types) {
        $query .= generate_query_values_and($entity_types, 'entity_type');
    }

    foreach (dbFetchRows($query, [$device_id]) as $entry) {
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
 * @param array        $device            Device array
 * @param string|array $mib               MIB name
 * @param boolean      $check_permissions Check device specific MIB permissions (if FALSE ignores it)
 * @param boolean      $check_sysORID     Check if MIB exist in sysOROID table
 *
 * @return boolean MIB is permitted for device or not.
 */
function is_device_mib($device, $mib, $check_permissions = TRUE, $check_sysORID = TRUE)
{
    global $config;

    if (is_array_list($mib)) {
        // Recursive check multiple MIBs
        $return = FALSE;
        foreach ($mib as $entry) {
            if ($return = is_device_mib($device, $entry, $check_permissions, $check_sysORID)) {
                break;
            }
        }
        return $return;
    }

    $mib_permitted = in_array($mib, get_device_mibs($device, $check_sysORID)); // Check if mib available for device

    if ($check_permissions && $mib_permitted) {
        // Check if MIB permitted by config
        //$mib_permitted = $mib_permitted && (!isset($config['mibs'][$mib]['enable']) || $config['mibs'][$mib]['enable']);
        if (isset($config['mibs'][$mib]['enable']) && !$config['mibs'][$mib]['enable']) {
            // Globally disabled MIB with different logic
            $where         = "`device_id` = ? AND `use` = ? AND `mib` = ?";
            $params        = [$device['device_id'], 'mib', $mib];
            $disabled      = dbFetchCell("SELECT `disabled` FROM `devices_mibs` WHERE $where", $params);
            $mib_permitted = $disabled === '0';
            if (!$mib_permitted) {
                print_debug("MIB [$mib] disabled in global config.");
            } else {
                print_debug("MIB [$mib] disabled in global config, but enabled in device config.");
            }
            return $mib_permitted;
        }
        // if (!$mib_permitted) {
        //   print_debug("MIB [$mib] disabled in global config.");
        //   return $mib_permitted;
        // }

        // Check if MIB disabled on device by web interface or polling process
        $mibs_disabled = get_device_mibs_disabled($device);
        $mib_permitted = $mib_permitted && !in_array($mib, $mibs_disabled);
        if (OBS_DEBUG && !$mib_permitted) {
            print_debug("MIB [$mib] disabled in device config.");
        }
    }

    return $mib_permitted;
}

/**
 * Return cached MIBs array available for device (from os definitions)
 * By default includes sysORID-supplied MIBs unless check_sysORID is false
 * When requesting list without sysORID, result will NOT be cached
 *
 * @param array|integer $device        Device array
 * @param bool          $check_sysORID Check or not mibs from sysORID table (Note in 99.99% cases this used as TRUE)
 * @param array|string  $mibs_order    Order how load available mibs. Default: [ 'model', 'os', 'group', 'default' ]
 *
 * @return array List of supported MIBs
 */
function get_device_mibs($device, $check_sysORID = TRUE, $mibs_order = NULL)
{
    global $config, $cache;

    if (is_numeric($device)) {
        $device_id = $device;
        $device    = device_by_id_cache($device_id);
    } else {
        $device_id = $device['device_id'];
    }

    // Set custom mibs order
    $mibs_order_default = ['model', 'os', 'group', 'default'];
    if (empty($mibs_order)) {
        // Default order: per-model mibs (if model set) -> os mibs -> os group mibs -> default mibs
        $mibs_order = $mibs_order_default;
    } elseif (is_string($mibs_order)) {
        // Order can passed as string with comma: 'model,os,group,default'
        $mibs_order = explode(',', $mibs_order);
    }

    // Check if custom order used, than set first from passed argument, second from default
    if ($mibs_order_custom = ($mibs_order !== $mibs_order_default)) {
        // Set first from passed argument, second
        $mibs_order = array_unique(array_merge($mibs_order, $mibs_order_default));
    }
    if ($check_sysORID && !in_array('sysorid', $mibs_order, TRUE)) {
        // Append discovered MIBs after model defined MIBs (of first)
        if (in_array('model', $mibs_order, TRUE)) {
            // [ 'model', 'sysorid', 'os', 'group', 'default' ]
            $mibs_order = array_push_after($mibs_order, array_search('model', $mibs_order, TRUE), 'sysorid');
        } else {
            // [ 'sysorid', 'os', 'group', 'default' ]
            array_unshift($mibs_order, 'sysorid');
        }
    }

    // Do not cache MIBs if custom order used, unknown $device_id or in PHPUNIT
    // When sysORID (and discovered) MIBs not requested, do not use cache!
    $use_cache = $device_id && $check_sysORID && !$mibs_order_custom && !defined('__PHPUNIT_PHAR__');

    // Just return cached MIBs (when exist)
    if ($use_cache && isset($cache['devices']['mibs'][$device_id])) {
        return $cache['devices']['mibs'][$device_id];
    }

    // Cache main device MIBs list
    if (!$use_cache || !isset($cache['devices']['mibs'][$device_id])) {
        $mibs = [];
        foreach ($mibs_order as $order) {
            switch ($order) {
                case 'model':
                    $model_array = get_model_array($device);
                    if (is_array($model_array) && isset($model_array['mibs'])) {
                        $mibs = array_merge($mibs, (array)$model_array['mibs']);
                        print_debug_vars($model_array['mibs']);
                    }
                    break;
                case 'sysorid':
                    // Get sysORID (and mibs discovery) supplied MIBs
                    $sysORID = safe_json_decode(get_entity_attrib('device', $device, 'sysORID'));
                    // Attach sysORID (and discovered) MIBs
                    if (is_array($sysORID) && !safe_empty($sysORID)) {
                        $mibs = array_merge($mibs, (array)$sysORID);
                    }
                    print_debug_vars($sysORID);
                    break;
                case 'os':
                    $mibs = array_merge($mibs, (array)$config['os'][$device['os']]['mibs']);
                    break;
                case 'group':
                    $os_group = $config['os'][$device['os']]['group'];
                    $mibs     = array_merge($mibs, (array)$config['os_group'][$os_group]['mibs']);
                    break;
                case 'default':
                    //var_dump($config['os_group']['default']['mibs']);
                    $mibs = array_merge($mibs, (array)$config['os_group']['default']['mibs']);
                    break;
            }
        }
        $mibs = array_unique($mibs);

        //$mibs = array_unique(array_merge((array)$mibs, (array)$config['os'][$device['os']]['mibs'],
        //                                 (array)$config['os_group'][$config['os'][$device['os']]['group']]['mibs'],
        //                                 (array)$config['os_group']['default']['mibs']));

        // Remove blacklisted MIBs from array
        //print_vars(get_device_mibs_blacklist($device));
        $mibs = array_diff($mibs, get_device_mibs_blacklist($device));

        if ($use_cache) {
            $cache['devices']['mibs'][$device_id] = $mibs;
        }
    } else {
        $mibs = $cache['devices']['mibs'][$device_id];
    }
    //print_error('$cache[\'devices\'][\'mibs\'][$device_id]');
    //print_vars($cache['devices']['mibs'][$device_id]);
    //print_vars($mibs);

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
    foreach (get_device_mibs($device, TRUE, $mibs_order) as $mib) {
        if (is_device_mib($device, $mib)) {
            $mibs[] = $mib;
        }
    }

    return $mibs;
}

/**
 * Return array with exclude MIBs for current device
 *
 * @param array $device Device array
 *
 * @return array Excluded MIBs
 */
function get_device_mibs_blacklist(array $device)
{
    global $config;

    // os_group mib excludes
    if (isset($config['os'][$device['os']]['group'], $config['os_group'][$config['os'][$device['os']]['group']]['mib_blacklist'])) {
        $group_mibs_exclude = (array)$config['os_group'][$config['os'][$device['os']]['group']]['mib_blacklist'];
        if (isset($config['os'][$device['os']]['mibs'])) {
            // if mib defined for os, do not exclude it by group definitions
            $group_mibs_exclude = array_diff($group_mibs_exclude, (array)$config['os'][$device['os']]['mibs']);
        }
    }

    // os mib excludes
    if (isset($config['os'][$device['os']]['mib_blacklist'])) {
        $os_mibs_exclude = (array)$config['os'][$device['os']]['mib_blacklist'];
        if (isset($group_mibs_exclude)) {
            // append defined for os_group
            return array_unique(array_merge($os_mibs_exclude, $group_mibs_exclude));
        }

        return $os_mibs_exclude;
    }

    if (isset($group_mibs_exclude)) {
        return $group_mibs_exclude;
    }

    // no excludes defined
    return [];
}

/**
 * Return array from DB with disabled mibs for device
 *
 * @param array|integer $device Device array
 *
 * @return array List of disabled MIBs for device
 */
function get_device_mibs_disabled($device)
{
    global $cache;

    if (is_numeric($device)) {
        $device_id = $device;
        $device    = device_by_id_cache($device_id);
    } else {
        $device_id = $device['device_id'];
    }

    // Return cached
    if (isset($cache['devices']['mibs_disabled'][$device_id])) {
        return $cache['devices']['mibs_disabled'][$device_id];
    }

    $params = [$device['device_id'], 'mib', '1'];
    $where  = "`device_id` = ? AND `use` = ? AND `disabled` = ?";

    if ($disabled = dbFetchColumn("SELECT `mib` FROM `devices_mibs` WHERE $where", $params)) {
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
 * @param array|integer  $device   Device array
 * @param string         $mib      MIB name
 * @param boolean        $remove   Remove MIB db entry complete
 * @param boolean|string $disabled TRUE for disable, FALSE for enable
 *
 * @return integer ID of db entry for disabled MIB
 */
function set_device_mib_disable($device, $mib, $remove = FALSE, $disabled = TRUE)
{
    if (safe_empty($mib)) {
        // MIB name required
        print_debug(__FUNCTION__ . "() required non empty mib name.");
        return FALSE;
    }

    if (is_numeric($device)) {
        $device_id = $device;
        $device    = device_by_id_cache($device_id);
    }

    // Fetch/validate if MIB in db
    $mib_db = dbFetchRow("SELECT `mib_id`, `disabled` FROM `devices_mibs` WHERE `device_id` = ? AND `use` = ? AND `mib` = ?", [$device['device_id'], 'mib', $mib]);

    // Just delete from DB if remove requested
    if ($remove) {
        if ($mib_db['mib_id']) {
            return dbDelete('devices_mibs', '`mib_id` = ?', [$mib_db['mib_id']]);
        }
        return FALSE;
    }

    // Convert to sql boolean
    $disabled = $disabled ? '1' : '0';

    if (!$mib_db['mib_id']) {
        // Not exist, insert
        return dbInsert(['device_id' => $device['device_id'], 'mib' => $mib,
                         'use'       => 'mib', 'disabled' => $disabled], 'devices_mibs');
    }

    if ($mib_db['disabled'] != $disabled) {
        // Exist, but changed
        dbUpdate(['disabled' => $disabled], 'devices_mibs', '`mib_id` = ?', [$mib_db['mib_id']]);
    }

    return $mib_db['mib_id'];
}

/**
 * Set mib enabled in DB for device.
 * Return IDs added or changed in DB.
 *
 * @param array   $device   Device array
 * @param string  $mib      MIB name
 * @param boolean $remove   Remove MIB db entry complete
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
 * @param string        $mib    MIB name
 *
 * @return array List of disabled MIBs for device
 */
function get_device_objects_disabled($device, $mib = NULL)
{
    global $cache;

    if (is_numeric($device)) {
        $device_id = $device;
        $device    = device_by_id_cache($device_id);
    } else {
        $device_id = $device['device_id'];
    }

    $params = [$device['device_id'], 'oid', '1'];
    $where  = "`device_id` = ? AND `use` = ? AND `disabled` = ?";
    if (empty($mib)) {
        // For empty mib see NULL or empty string
        // This is common for numeric Oids
        $where .= " AND (`mib` = '' OR `mib` IS NULL)";
        $mib   = '__mib'; // Just for caching
    } else {
        $where    .= " AND `mib` = ?";
        $params[] = $mib;
    }

    // Return cached
    if (isset($cache['devices']['objects_disabled'][$device_id][$mib])) {
        return $cache['devices']['objects_disabled'][$device_id][$mib];
    }

    // Query db for objects
    if ($disabled = dbFetchColumn("SELECT `object` FROM `devices_mibs` WHERE $where", $params)) {
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
 * @param array|int $device   Device array
 * @param string    $object   Object name
 * @param string    $mib      MIB name (optional)
 * @param boolean   $remove   Remove MIB db entry complete
 * @param boolean   $disabled TRUE for disable, FALSE for enable
 *
 * @return integer ID of db entry for disabled MIB
 */
function set_device_object_disable($device, $object, $mib = '', $remove = FALSE, $disabled = TRUE)
{
    if (safe_empty($object)) {
        // MIB name required
        print_debug(__FUNCTION__ . "() required non empty object name.");
        return FALSE;
    }

    if (is_numeric($device)) {
        $device_id = $device;
        $device    = device_by_id_cache($device_id);
    }

    // Initial insert array
    $insert_array = ['device_id' => $device['device_id'], 'object' => $object,
                     'use'       => 'object', 'disabled' => '1'];

    $where  = '`device_id` = ? AND `use` = ? AND `object` = ?';
    $params = [$device['device_id'], 'object', $object];
    if (safe_empty($mib)) {
        // For empty mib see NULL or empty string
        // This is common for numeric Oids
        $where .= " AND (`mib` = '' OR `mib` IS NULL)";
    } else {
        $where    .= " AND `mib` = ?";
        $params[] = $mib;

        // Append mib to insert
        $insert_array['mib'] = $mib;
    }
    // Fetch/validate if MIB in db
    $mib_db = dbFetchRow("SELECT `mib_id`, `disabled` FROM `devices_mibs` WHERE $where", $params);

    // Just delete from DB if remove requested
    if ($remove) {
        if ($mib_db['mib_id']) {
            return dbDelete('devices_mibs', '`mib_id` = ?', [$mib_db['mib_id']]);
        }
        return FALSE;
    }

    // Convert to sql boolean
    $disabled = $disabled ? '1' : '0';

    if (!$mib_db['mib_id']) {
        // Not exist, insert
        return dbInsert($insert_array, 'devices_mibs');
    }
    if ($mib_db['disabled'] !== $disabled) {
        // Exist, but changed
        dbUpdate(['disabled' => $disabled], 'devices_mibs', '`mib_id` = ?', [$mib_db['mib_id']]);
    }

    return $mib_db['mib_id'];
}

/**
 * Set object enabled in DB for device.
 * Return IDs added or changed in DB.
 *
 * @param array   $device   Device array
 * @param string  $object   Object name
 * @param string  $mib      MIB name (optional)
 * @param boolean $remove   Remove MIB db entry complete
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

    if (is_array($cache[$entity_type][$entity_id])) {
        return $cache[$entity_type][$entity_id];
    }

    $translate = entity_type_translate_array($entity_type);
    //print_vars($translate);

    switch ($entity_type) {
        case "bill":
            if (function_exists('get_bill_by_id')) {
                $entity = get_bill_by_id($entity_id);
            }
            break;

        default:
            $sql = 'SELECT * FROM `' . $translate['table'] . '`';

            if (isset($translate['state_table'])) {
                $sql .= ' LEFT JOIN `' . $translate['state_table'] . '` USING (`' . $translate['id_field'] . '`)';
            }

            if (isset($translate['parent_table'])) {
                $sql .= ' LEFT JOIN `' . $translate['parent_table'] . '` USING (`' . $translate['parent_id_field'] . '`)';
            }

            $sql .= ' WHERE `' . $translate['table'] . '`.`' . $translate['id_field'] . '` = ?';

            //print_r($entity_type);
            //print_r($entity_id);
            //print_r($translate);
            //print_r($sql.PHP_EOL);

            $entity = dbFetchRow($sql, [$entity_id]);
            //print_r($entity);
            //print_r(dbError());
            break;
    }

    if (is_array($entity)) {
        if (function_exists('humanize_' . $entity_type)) {
            $do = 'humanize_' . $entity_type;
            $do($entity);
        } elseif (isset($translate['humanize_function']) && function_exists($translate['humanize_function'])) {
            $do = $translate['humanize_function'];
            $do($entity);
        }

        entity_rewrite($entity_type, $entity);
        $cache[$entity_type][$entity_id] = $entity;
        return $entity;
    }

    return FALSE;
}

function cache_entities_by_id($entity_type, $entity_ids)
{
    global $cache;

    $translate = entity_type_translate_array($entity_type);
    //print_vars($translate);

    foreach ($entity_ids as $key => $entity_id) {
        // Skip non-numeric values and already cached entities
        if (!is_numeric($entity_id) || is_array($cache[$entity_type][$entity_id])) {
            unset($entity_ids[$key]);
        }
    }
    if (safe_count($entity_ids) == 0) {
        return;
    } // Nothing to do

    switch ($entity_type) {

        default:

            $sql = 'SELECT * FROM `' . $translate['table'] . '`';

            if (isset($translate['state_table'])) {
                $sql .= ' LEFT JOIN `' . $translate['state_table'] . '` USING (`' . $translate['id_field'] . '`)';
            }

            if (isset($translate['parent_table'])) {
                $sql .= ' LEFT JOIN `' . $translate['parent_table'] . '` USING (`' . $translate['parent_id_field'] . '`)';
            }
            //$sql .= ' WHERE `'.$translate['table'].'`.`'.$translate['id_field'].'` IN ?';
            //$sql .= ' WHERE `'.$translate['table'].'`.`'.$translate['id_field'].'` IN ('.implode(',', $entity_ids).')';

            $sql .= ' WHERE 1 ' . generate_query_values_and($entity_ids, '`' . $translate['table'] . '`.`' . $translate['id_field'] . '`');
            //print_r($entity_type);
            //print_r($entity_ids);
            //print_r($translate);
            //print_r($sql.PHP_EOL);

            $entities = dbFetchRows($sql);
            //print_r($entities);
            //print_r(dbError());
            break;
    }

    if (is_array($entities)) {
        foreach ($entities as $entity) {
            if (function_exists('humanize_' . $entity_type)) {
                $do = 'humanize_' . $entity_type;
                $do($entity);
            } elseif (isset($translate['humanize_function']) && function_exists($translate['humanize_function'])) {
                $do = $translate['humanize_function'];
                $do($entity);
            }

            entity_rewrite($entity_type, $entity);

            $entity_id                       = $entity[$translate['id_field']];
            $cache[$entity_type][$entity_id] = $entity;
        }
    }
    return TRUE;
}

/* Network/ARP/MAC specific entity functions */

/**
 * Fetch entity IDs by network. Supported entities: device, port, ip (ipv4, ipv6 for force specific IP version)
 *
 * See parse_network() for possible valid network queries.
 *
 * @param string       $entity_type Entity type (device, port)
 * @param string|array $network     Valid network string (or array)
 * @param string       $add_where   Custom where string
 *
 * @return false|array Array with entity specific IDs
 */
function get_entity_ids_ip_by_network($entity_type, $network, $add_where = '')
{

    // Recursive query for array of networks
    if (is_array($network)) {
        $ids = [];
        foreach ($network as $entry) {
            if ($entry_ids = get_entity_ids_ip_by_network($entity_type, $entry, $add_where)) {
                $ids[] = $entry_ids; //array_merge($ids, $entry_ids);
            }
        }

        return array_merge([], ...$ids);
    }

    // Parse for valid network string
    $network_array = parse_network($network);
    //print_vars($network_array);
    if (!$network_array) {
        // Incorrect network/address string passed
        return FALSE;
    }

    $query = 'SELECT ';
    $join  = '';
    $where = ' WHERE 1 ';
    switch ($entity_type) {
        case 'ipv4':
            // Force request IPv6 address
            $network_array['ip_type'] = 'ipv4';
            $query                    .= ' `ipv4_address_id`';
            break;
        case 'ipv6':
            // Force request IPv6 address
            $network_array['ip_type'] = 'ipv6';
            $query                    .= ' `ipv6_address_id`';
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

    $params = [];
    switch ($network_array['ip_type']) {
        case 'ipv4':
            $query .= ' FROM `ipv4_addresses`';
            if ($network_array['query_type'] === 'single') {
                // Exactly IP match
                //$where .= ' AND BINARY `ipv4_binary` = ?';
                $where .= ' AND `ipv4_binary` = ?';
                //var_dump($network_array['address_binary']);
                $params[] = [binary_to_hex($network_array['address_binary'])];
            } elseif ($network_array['query_type'] === 'network') {
                // Match IP in network
                $where    .= ' AND `ipv4_binary` >= ? AND `ipv4_binary` <= ?';
                $params[] = [binary_to_hex($network_array['network_start_binary'])];
                $params[] = [binary_to_hex($network_array['network_end_binary'])];
            } else {
                // Match IP addresses by part of string
                $where .= generate_query_values_and($network_array['address'], 'ipv4_address', $network_array['query_type']);
            }
            break;
        case 'ipv6':
            $query .= ' FROM `ipv6_addresses`';
            if ($network_array['query_type'] === 'single') {
                // Exactly IP match
                $where    .= ' AND `ipv6_binary` = ?';
                $params[] = [binary_to_hex($network_array['address_binary'])];
            } elseif ($network_array['query_type'] === 'network') {
                // Match IP in network
                $where    .= ' AND `ipv6_binary` >= ? AND `ipv6_binary` <= ?';
                $params[] = [binary_to_hex($network_array['network_start_binary'])];
                $params[] = [binary_to_hex($network_array['network_end_binary'])];
            } else {
                // Match IP addresses by part of string
                $where .= ' AND (' . generate_query_values($network_array['address'], 'ipv6_address', $network_array['query_type']) .
                          ' OR ' . generate_query_values($network_array['address'], 'ipv6_compressed', $network_array['query_type']) . ')';
            }
            break;
    }

    if (FALSE) {
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

    return dbFetchColumn($query . $where, $params);
    //$ids = dbFetchColumn($query . $where, $params);
    //print_vars($ids);

    //return $ids;
}

// DOCME needs phpdoc block
// CLEANME
/* not used anymore
function entity_type_translate($entity_type)
{
  $data = entity_type_translate_array($entity_type);
  if (!is_array($data)) { return NULL; }

  return array($data['table'], $data['id_field'], $data['name_field'], $data['ignore_field'], $data['entity_humanize']);
}
*/

function entity_index_tags($index, $i = NULL) {
    $tags = [];

    $tags['index']      = $index;            // Index in descr
    $tags['index_text'] = snmp_oid_to_string($index);
    foreach (explode('.', $index) as $k => $idx) {
        $tags['index' . $k] = $idx;          // Index parts
    }
    if (is_numeric($i)) {
        // entity counter++
        $tags['i']      = $i;
    }

    return $tags;
}

// Returns a text name from an entity type and an id
// A little inefficient.
// DOCME needs phpdoc block
// TESTME needs unit testing
function entity_name($type, $entity)
{
    global $config, $entity_cache;

    if (is_numeric($entity)) {
        $entity = get_entity_by_id_cache($type, $entity);
    }

    $translate = entity_type_translate_array($type);

    $text = $entity[$translate['name_field']];

    return ($text);
}

// Returns a text name from an entity type and an id
// A little inefficient.
// DOCME needs phpdoc block
// TESTME needs unit testing
function entity_short_name($type, $entity)
{
    global $config, $entity_cache;

    if (is_numeric($entity)) {
        $entity = get_entity_by_id_cache($type, $entity);
    }

    $translate = entity_type_translate_array($type);

    $text = $entity[$translate['name_field']];

    return ($text);
}

// Returns a text description from an entity type and an id
// A little inefficient.
// DOCME needs phpdoc block
// TESTME needs unit testing
function entity_descr($type, $entity)
{
    global $config, $entity_cache;

    if (is_numeric($entity)) {
        $entity = get_entity_by_id_cache($type, $entity);
    }

    $translate = entity_type_translate_array($type);

    $text = $entity[$translate['entity_descr_field']];

    return ($text);
}

function entity_descr_check(&$descr, $entity_type, $entity = [])
{
    global $config;

    if (safe_empty($descr) || is_array($descr)) {
        // Ignore empty (or arrays because currently we check only descr
        print_debug("Skipped by empty description: $descr ");
        return TRUE;
    }
    // Clean description (multiple spaces to single)
    $descr     = trim(preg_replace('/\s{2,}/', ' ', $descr));
    $limit_len = 255;

    // Select entity ignore config
    switch ($entity_type) {
        case 'sensor':
        case 'status':
            $ignore_equals   = $config['ignore_sensor'];
            $ignore_contains = $config['ignore_sensor_string'];
            $ignore_regexp   = $config['ignore_sensor_regexp'];
            break;

        case 'counter':
            $ignore_equals   = $config['ignore_counter'];
            $ignore_contains = $config['ignore_counter_string'];
            $ignore_regexp   = $config['ignore_counter_regexp'];
            break;

        case 'storage':
            $ignore_equals   = $config['ignore_mount'];
            $ignore_contains = $config['ignore_mount_string'];
            $ignore_regexp   = $config['ignore_mount_regexp'];
            $limit_len       = FALSE;
            break;

        case 'processor':
            $ignore_equals   = $config['ignore_processor'];
            $ignore_contains = $config['ignore_processor_string'];
            $ignore_regexp   = $config['ignore_processor_regexp'];
            break;

        case 'mempool':
            $ignore_equals   = $config['ignore_mempool'];
            $ignore_contains = $config['ignore_mempool_string'];
            $ignore_regexp   = $config['ignore_mempool_regexp'];
            break;

        case 'toner':
        case 'printersupply':
            $ignore_equals   = $config['ignore_toner'];
            $ignore_contains = $config['ignore_toner_string'];
            $ignore_regexp   = $config['ignore_toner_regexp'];
            break;

        case 'lsp':
            $ignore_equals   = $config['ignore_lsp'];
            $ignore_contains = $config['ignore_lsp_string'];
            $ignore_regexp   = $config['ignore_lsp_regexp'];
            break;

        default:
            $ignore_equals_key   = 'ignore_' . $entity_type;
            $ignore_contains_key = 'ignore_' . $entity_type . '_string';
            $ignore_regexp_key   = 'ignore_' . $entity_type . '_regexp';
            if (isset($config[$ignore_equals_key]) ||
                isset($config[$ignore_contains_key]) ||
                isset($config[$ignore_regexp_key])) {
                // Generic entity ignore
                $ignore_equals   = $config[$ignore_equals_key];
                $ignore_contains = $config[$ignore_contains_key];
                $ignore_regexp   = $config[$ignore_regexp_key];
            } else {
                // Unknown entity, no checks
                return FALSE;
            }
    }

    foreach ((array)$ignore_equals as $bi) {
        if (strcasecmp($bi, $descr) == 0) {
            print_debug("Skipped by equals: $bi, $descr ");
            return TRUE;
        }
    }
    foreach ((array)$ignore_contains as $bi) {
        if (stripos($descr, $bi) !== FALSE) {
            print_debug("Skipped by contains: $bi, $descr ");
            return TRUE;
        }
    }
    foreach ((array)$ignore_regexp as $bi) {
        if (preg_match($bi, $descr) > 0) {
            print_debug("Skipped by regexp: $bi, $descr ");
            return TRUE;
        }
    }

    // Limit descr to 255 chars accordingly as in DB
    if ($limit_len && strlen($descr) > $limit_len) {
        $descr = check_extension_exists('mbstring') ? mb_substr($descr, 0, $limit_len) : substr($descr, 0, $limit_len);
        print_debug("Description truncated to $limit_len chars: '$descr'");
    }

    return FALSE;
}

/**
 * Generate entity description based on their type and discovery definition.
 *
 * @param string  $entity_type Entity type
 * @param array   $definition  Entity discovery definition entry
 * @param array   $descr_entry Array with possible descr strings received for example from snmpwalk, also used for tag replaces
 * @param integer $count       Optional entity count for current Table, when > 1 descr can use optional index or different descr
 *
 * @return string Parsed entity description
 */
function entity_descr_definition($entity_type, $definition, $descr_entry, $count = 1)
{
    //print_vars($definition);
    //print_vars($descr_entry);

    // Per index definitions
    if (isset($descr_entry['index'], $definition['indexes'][$descr_entry['index']]['descr'])) {
        // Override with per index descr definition
        $definition['descr'] = $definition['indexes'][$descr_entry['index']]['descr'];
    }

    // Descr contain tags, prefer tag replaces
    $use_tags = isset($definition['descr']) && str_contains($definition['descr'], '%');

    if (isset($definition['oid_descr']) && str_contains($definition['oid_descr'], '::')) {
        [$mib, $definition['oid_descr']] = explode("::", $definition['oid_descr']);
    }
    $descr = '';
    if (isset($definition['oid_descr'], $descr_entry[$definition['oid_descr']]) && !safe_empty($descr_entry[$definition['oid_descr']])) {
        $descr = $descr_entry[$definition['oid_descr']];
        if (!$use_tags) {
            // not tags and oid_descr exist, just return it
            if (isset($definition['descr_transform'])) {
                $descr = string_transform($descr, $definition['descr_transform']);
            }
            return $descr;
        }

        // Append to array for correct replace
        $descr_entry['oid_descr'] = $descr;
    }

    if (isset($definition['descr'])) {
        // Use definition descr
        $descr = $definition['descr'];

        if ($use_tags) {

            // Multipart index: Oid.0.1.2 -> %index0%, %index1%, %index2%
            if (isset($descr_entry['index']) && preg_match('/%index\d+%/', $descr)) {
                // Multipart index
                foreach (explode('.', $descr_entry['index']) as $k => $k_index) {
                    $descr_entry['index' . $k] = $k_index; // Multipart indexes
                }
            }
            // Index is snmp string:
            if (isset($descr_entry['index']) && str_contains($descr, '%index_string%')) {
                // SNMP string
                $descr_entry['index_string'] = snmp_oid_to_string($descr_entry['index']);
            }

            // Replace tags %tag%
            $descr = array_tag_replace($descr_entry, $descr);
        } elseif ($count > 1 && isset($descr_entry['index'])) {
            // For multi append index, but always prefer to USE TAGS!
            $descr .= ' ' . $descr_entry['index'];
        }

    }

    // Use Entity Defaults (not defined descr or oid_descr)
    if (safe_empty($descr)) {
        $translate = entity_type_translate_array($entity_type);
        //print_vars($translate);

        if ($count > 1 && isset($translate['name_multiple'])) {
            $descr = $translate['name_multiple'];
        } elseif (isset($translate['name'])) {
            $descr = $translate['name'];
        } else {
            //$descr = 'Entity';
            $descr = nicecase($entity_type);
        }

        if ($count > 1 && isset($descr_entry['index'])) {
            // For multi append index
            $descr .= ' ' . $descr_entry['index'];
        }
    }

    // Transform/clean definition
    if (isset($definition['descr_transform'])) {
        $descr = string_transform($descr, $definition['descr_transform']);
    }

    //print_vars($descr);

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
function entity_measured_match_definition($device, $definition, $entity = [], $entity_type = NULL) {
    // $entity_type unused currently

    $options = [];

    // Just append label for sorting and grouping
    if (isset($definition['measured_label'])) {
        $options['measured_entity_label'] = array_tag_replace($entity, $definition['measured_label']);

        // See ARUBAWIRED-FAN-MIB definition
        if (isset($definition['measured_label_match'])) {
            foreach ((array)$definition['measured_label_match'] as $measured_class => $pattern) {
                if (preg_match($pattern, $options['measured_entity_label'], $matches)) {
                    $options['measured_class'] = $measured_class; // set measured class
                    if (!safe_empty($matches['label'])) {
                        $options['measured_entity_label'] = $matches['label'];
                    } elseif (!safe_empty($matches[1])) {
                        $options['measured_entity_label'] = $matches[1];
                    }
                    break;
                }
            }
        }
    }

    if (isset($definition['entity_match'])) {
        // Just alternative definition key
        $definition['measured_match'] = $definition['entity_match'];
    }
    if (!isset($definition['measured_match'])) {
        if (isset($definition['measured']) && !safe_empty($options['measured_entity_label'])) {
            // Store measured class when entity label not empty
            $options['measured_class'] = $definition['measured'];
        }
        return $options;
    }

    // Convert a single match array to multi-array
    if (isset($definition['measured_match']['match'])) {
        $definition['measured_match'] = [ $definition['measured_match'] ];
    }

    $measured_found = FALSE;
    foreach ($definition['measured_match'] as $rule) {
        // Allow tests here to allow multiple match rules on the same sensor table (entity-mib, etc)

        // First we make substitutions using %key%, then we run transformations

        // Switch search by LIKE or any (default is exactly match)
        $sql_condition = $rule['condition'] ?? NULL;

        $rule['match'] = array_tag_replace($entity, $rule['match']);

        if (is_array($rule['transform'])) {
            $rule['match'] = string_transform($rule['match'], $rule['transform']);
        }

        $where_array = [
            generate_query_values($device['device_id'], 'device_id'),
            '`deleted` = 0'
        ];

        // Associate defined entity type
        switch ($rule['entity_type']) {
            case 'entity':
                // Generic entities
                $sql = "SELECT * FROM `entities`";
                switch (strtolower($rule['field'])) {
                    case 'descr':
                    case 'name':
                    case 'label':
                        $sql_rules   = [];
                        $sql_rules[] = generate_query_values($rule['match'], 'entity_descr', $sql_condition);
                        if (!safe_empty($rule['class'])) {
                            $sql_rules[] = generate_query_values($rule['match'], 'entity_class', $sql_condition);
                        }
                        $sql_rule = '(' . implode(' AND ', $sql_rules) . ')';
                        if ($measured = dbFetchRow($sql . generate_where_clause($where_array, $sql_rule))) {
                            $options['measured_class']            = 'entity';
                            $options['measured_entity']           = $measured['entity_id'];
                            $options['entPhysicalIndex_measured'] = $measured['entity_index'];
                            $options['measured_entity_label']     = $measured['entity_descr'];
                            $options['entity_label']              = $measured['entity_descr'];
                            print_debug('Linked to entity ' . $measured['entity_id'] . ' via descr');
                            $measured_found = TRUE;
                        }
                        break;

                    case 'index':
                        $sql_rules   = [];
                        $sql_rules[] = generate_query_values($rule['match'], 'entity_index', $sql_condition);
                        if (!safe_empty($rule['class'])) {
                            $sql_rules[] = generate_query_values($rule['match'], 'entity_class', $sql_condition);
                        }
                        $sql_rule = '(' . implode(' AND ', $sql_rules) . ')';
                        if ($measured = dbFetchRow($sql . generate_where_clause($where_array, $sql_rule))) {
                            $options['measured_class']            = 'entity';
                            $options['measured_entity']           = $measured['entity_id'];
                            $options['entPhysicalIndex_measured'] = $measured['entity_index'];
                            $options['measured_entity_label']     = $measured['entity_descr'];
                            $options['entity_label']              = $measured['entity_descr'];
                            print_debug('Linked to entity ' . $measured['entity_id'] . ' via index');
                            $measured_found = TRUE;
                        }
                        break;
                }
                break;

            case 'port':
                // Port entities
                $sql = "SELECT * FROM `ports`";
                switch (strtolower($rule['field'])) {
                    case 'ifdescr':
                    case 'ifname':
                    case 'label':
                        $sql_rules   = [];
                        $sql_rules[] = generate_query_values($rule['match'], 'ifDescr', $sql_condition);
                        $sql_rules[] = generate_query_values($rule['match'], 'ifName', $sql_condition);
                        $sql_rules[] = generate_query_values($rule['match'], 'port_label', $sql_condition);
                        $sql_rules[] = generate_query_values($rule['match'], 'port_label_short', $sql_condition);
                        $sql_rule    = '(' . implode(' OR ', $sql_rules) . ')';
                        if ($measured = dbFetchRow($sql . generate_where_clause($where_array, $sql_rule))) {
                            $options['measured_class']            = 'port';
                            $options['measured_entity']           = $measured['port_id'];
                            $options['entPhysicalIndex_measured'] = $measured['ifIndex'];
                            $options['measured_entity_label']     = $measured['port_label'];
                            $options['port_label']                = $measured['port_label'];
                            print_debug('Linked to port ' . $measured['port_id'] . ' via ifDescr');
                            $measured_found = TRUE;
                        }
                        break;

                    case 'ifalias':
                        $sql_rule = generate_query_values($rule['match'], 'ifAlias', $sql_condition);
                        if ($measured = dbFetchRow($sql . generate_where_clause($where_array, $sql_rule))) {
                            $options['measured_class']            = 'port';
                            $options['measured_entity']           = $measured['port_id'];
                            $options['entPhysicalIndex_measured'] = $measured['ifIndex'];
                            $options['measured_entity_label']     = $measured['port_label'];
                            $options['port_label']                = $measured['port_label'];
                            print_debug('Linked to port ' . $measured['port_id'] . ' via ifAlias');
                            $measured_found = TRUE;
                        }
                        break;

                    case 'ifindex':
                        if ($measured = get_port_by_index_cache($device['device_id'], $rule['match'])) {
                            $options['measured_class']            = 'port';
                            $options['measured_entity']           = $measured['port_id'];
                            $options['entPhysicalIndex_measured'] = $measured['ifIndex'];
                            $options['measured_entity_label']     = $measured['port_label'];
                            $options['port_label']                = $measured['port_label'];
                            print_debug('Linked to port ' . $measured['port_id'] . ' via ifIndex');
                            $measured_found = TRUE;
                        }
                        break;
                }

                break;
        }
        if ($measured_found) {
            break;
        } // Stop foreach if measured match found
    }

    // If passed $entity_type arg, do something
    switch ($entity_type) {
        case 'ip-address':
        case 'ip-addresses':
            // Return measured array instead options
            if ($measured_found) {
                $options = $measured;
            }
            break;
    }

    return $options;
}

function entity_class_definition($device, $definition, $entity, $entity_type = NULL) {
    // Determine the sensor class
    if (isset($definition['class'])) {
        // Use the hardcoded sensor class if available
        return $definition['class'];
    }

    if (isset($definition['oid_class'], $entity[$definition['oid_class']])) {
        // Use the mapped class based on the value from oid_class
        if (isset($definition['map_class'][$entity[$definition['oid_class']]])) {
            print_debug('map_class found: ' . $entity[$definition['oid_class']] . ' - ' . $definition['map_class'][$entity[$definition['oid_class']]]);
            return $definition['map_class'][$entity[$definition['oid_class']]];
        }
        if (isset($definition['map_class_regex'])) {
            // Match by regex. See FIREBRICK-MONITORING
            foreach ($definition['map_class_regex'] as $pattern => $class) {
                if (preg_match($pattern, $entity[$definition['oid_class']])) {
                    print_debug('map_class_regex found: ' . $entity[$definition['oid_class']] . ' - ' . $class);
                    return $class;
                }
            }
        }
        if ($entity_type !== 'status') {
            print_debug('Value from oid_class (' . $entity[$definition['oid_class']] . ') does not match any configured values in map_class!');
            return FALSE; // Break foreach. Proceed to next!
        }
        return $entity[$definition['oid_class']];
    }

    if (isset($definition['map_class_index'])) {
        $index = $entity['index'];
        // Use the mapped class based on the index
        if (isset($definition['map_class_index'][$index])) {
            print_debug('map_class_index found: ' . $entity[$index] . ' - ' . $definition['map_class_index'][$index]);
            return $definition['map_class_index'][$index];
        }
        if ($entity_type !== 'status') {
            print_debug('map_class_index configured (' . $entity[$index] . ') but no matches for this index!');
            return FALSE; // Break foreach. Proceed to next!
        }
    }

    // No class determined from the available options
    if ($entity_type !== 'status') {
        print_debug('No class hardcoded and no oid_class (' . $definition['oid_class'] . ') found in table walk!');
    }
    return ''; // Break foreach. Proceed to next!
}

function entity_scale_definition($device, $definition, $entity, $entity_type = NULL) {

    if (isset($definition['oid_scale'])) {
        if (isset($entity[$definition['oid_scale']])) {
            // Scale in same table
            $scale = $entity[$definition['oid_scale']];
            if (isset($definition['map_scale'][$scale])) {
                // Scale map defined, just return it (ie SUPERMICRO-HEALTH-MIB)
                print_debug("Scale mapped from value [" . $scale . "] to " . $definition['map_scale'][$scale]);
                return $definition['map_scale'][$scale];
            }
            if (isset($definition['map_scale_regex'])) {
                // Scale map regex defined. See FIREBRICK-MONITORING
                foreach ($definition['map_scale_regex'] as $pattern => $return) {
                    if (preg_match($pattern, $scale)) {
                        print_debug("Scale mapped from value [" . $scale . "] to " . $return);
                        return $return;
                    }
                }
            }
            if ((isset($definition['scale_si']) && $definition['scale_si']) || !is_numeric($scale)) {
                // Ie for ENTITY-SENSOR-MIB scale in SI units (and not already mapped in definition)
                // Also see in CISCOSB-SENSOR-MIB
                // Also see in ORION-BASE-MIB
                $precission = is_numeric($entity[$definition['oid_precision']]) ? $entity[$definition['oid_precision']] : NULL;
                $scale      = si_to_scale($scale, $precission);
                print_debug("Scale converted from SI unit [" . $entity[$definition['oid_scale']] . "] and precision [$precission] to $scale");
            }
        } elseif (str_contains($definition['oid_scale'], '.')) {
            // Scale is outside from table with single index, see SP2-MIB
            $scale = snmp_cache_oid($device, $definition['oid_scale'], $definition['mib']);
            // Translate scale from specific Oid
            if (isset($definition['map_scale'][$scale])) {
                print_debug("Scale mapped from value [" . $scale . "] to " . $definition['map_scale'][$scale]);
                return $definition['map_scale'][$scale];
            }
        }
    }

    if ($entity_type === 'processor' && isset($definition['oid_precision']) && !is_numeric($scale)) {
        // Precision
        if (isset($entity[$definition['oid_precision']])) {
            $scale = float_div(1, $entity[$definition['oid_precision']]);
            if ($scale === 0) {
                unset($scale);
            }
        }
    }

    // Fallback to 1 if scale not found or not numeric
    if (!is_numeric($scale)) {
        $scale = 1;
        if (isset($definition['scale'])) {
            if (is_array($definition['scale'])) {
                // See SPAGENT-MIB definition and https://jira.observium.org/browse/OBS-3836
                //print_vars($definition);
                //print_vars($entity);
                if (isset($definition['scale']['scale'])) {
                    // Convert single condition to multi
                    $definition['scale'] = [$definition['scale']];
                }
                foreach ($definition['scale'] as $test) {
                    print_debug("Test Scale by condition: " . $test['scale']);
                    if (isset($test['scale']) &&
                        !discovery_check_requires($device, ['test' => $test], $entity)) {
                        $scale = $test['scale'];
                        //print_vars($scale);
                        break;
                    }
                }
            } else {
                // Common numeric
                $scale = $definition['scale'];
            }
        }
        //$scale = isset($definition['scale']) ? $definition['scale'] : 1;
    }

    return $scale;
}

function entity_limits_definition($device, $definition, $entity, $scale = 1)
{
    $options = [];

    // Scale limit
    $limit_scale = 1;
    if (isset($definition['limit_scale'])) {
        if (is_array($definition['limit_scale'])) {
            // See SPAGENT-MIB definition and https://jira.observium.org/browse/OBS-3836
            //print_vars($definition);
            //print_vars($entity);
            if (isset($definition['limit_scale']['scale'])) {
                // Convert single condition to multi
                $definition['limit_scale'] = [$definition['limit_scale']];
            }
            foreach ($definition['limit_scale'] as $test) {
                print_debug("Test Limit Scale by condition: " . $test['scale']);
                if (isset($test['scale']) &&
                    !discovery_check_requires($device, ['test' => $test], $entity)) {
                    $limit_scale = $test['scale'];
                    //print_vars($limit_scale);
                    break;
                }
            }
        } else {
            // if limit scale equals string 'scale' copy it (for dynamic table scales)
            $limit_scale = in_array($definition['limit_scale'], ['scale', 'copy']) ? $scale : $definition['limit_scale'];
        }
    }

    // Oids in separate MIB, see: TPLINK-DDMSTATUS-MIB definition
    foreach (['oid_limit_warn', 'oid_limit_nominal', 'oid_limit_delta', 'oid_limit_delta_warn',
              'oid_limit_low', 'oid_limit_low_warn', 'oid_limit_high', 'oid_limit_high_warn'] as $oid_limit) {
        if (isset($definition[$oid_limit]) &&
            str_contains($definition[$oid_limit], '::') && !str_contains($definition[$oid_limit], '.')) {
            // remove MIB part for correctly fetch entity data
            [, $definition[$oid_limit]] = explode('::', $definition[$oid_limit], 2);
        }
    }

    // Limit Warning Tolerance (set warnings based on high/low limits)
    // See TROPIC-SHELF-MIB
    $limit_warn = FALSE;
    if (isset($definition['oid_limit_warn'])) {
        $oid_limit = 'oid_limit_warn';
        if (isset($entity[$definition[$oid_limit]])) {
            // Named oid, exist in table
            $limit_warn = $entity[$definition[$oid_limit]];
        } elseif (str_contains($definition[$oid_limit], '.')) {
            // Single param oid (outside table)
            $limit_warn = snmp_cache_oid($device, $definition[$oid_limit], $definition['mib']);
        }
        $limit_warn = snmp_fix_numeric($limit_warn, $definition['limit_unit']);
    } elseif (isset($definition['limit_warn'])) {
        $limit_warn = $definition['limit_warn'];
    }

    // Check limits oids if set
    foreach (['limit_low', 'limit_low_warn', 'limit_high', 'limit_high_warn'] as $limit) {
        $oid_limit     = 'oid_' . $limit;
        $invalid_limit = 'invalid_' . $limit;
        if (isset($definition[$oid_limit])) {
            // In case, when limit oids based on extra oid, ie phase/bank (see CyberPower CPS-MIB)
            $definition[$oid_limit] = array_tag_replace($entity, $definition[$oid_limit]);

            if (isset($entity[$definition[$oid_limit]])) {
                // Named oid, exist in table
                $options[$limit] = $entity[$definition[$oid_limit]];
            } elseif (str_contains($definition[$oid_limit], '.')) {
                // Single param oid (outside table)
                $options[$limit] = snmp_cache_oid($device, $definition[$oid_limit], $definition['mib']);
            } else {
                continue;
            }
            $options[$limit] = snmp_fix_numeric($options[$limit], $definition['limit_unit']);
            if (!is_numeric($options[$limit])) {
                // Skip not numeric limit values
                print_debug("$limit excluded by non numeric value (" . $options[$limit] . ").");
                unset($options[$limit]);
                continue;
            } elseif ((isset($definition[$invalid_limit]) && in_array($options[$limit], (array)$definition[$invalid_limit])) ||   // per each limit invalidation
                      (isset($definition['invalid_limit']) && in_array($options[$limit], (array)$definition['invalid_limit']))) { // more global limit invalidation
                print_debug("$limit excluded by value (" . $options[$limit] . ") in invalid range [" . implode(', ', (array)$definition[$invalid_limit]) . "].");
                unset($options[$limit]);
                continue;
            }
            $options[$limit] = scale_value($options[$limit], $limit_scale);
        } elseif ($limit === 'limit_low_warn' && isset($options['limit_low']) && is_numeric($limit_warn)) {
            // Low Warning based on tolerance
            $options[$limit] = $options['limit_low'] + scale_value($limit_warn, $limit_scale);
        } elseif ($limit === 'limit_high_warn' && isset($options['limit_high']) && is_numeric($limit_warn)) {
            // High Warning based on tolerance
            $options[$limit] = $options['limit_high'] - scale_value($limit_warn, $limit_scale);
        } elseif (isset($definition[$limit]) && is_numeric($definition[$limit])) {
            $options[$limit] = $definition[$limit]; // Limit from definition
        }
    }

    // Limits based on nominal +- delta oids (see TPT-HEALTH-MIB)
    if (isset($definition['oid_limit_nominal'])) {
        $oid_limit = 'oid_limit_nominal';
        if (isset($entity[$definition[$oid_limit]])) {
            // Named oid, exist in table
            $limit_nominal = $entity[$definition[$oid_limit]];
        } elseif (str_contains($definition[$oid_limit], '.')) {
            // Single param oid (outside table)
            $limit_nominal = snmp_cache_oid($device, $definition[$oid_limit], $definition['mib']);
        } else {
            $limit_nominal = FALSE;
        }
    }
    if (is_numeric($limit_nominal)) {
        // Delta in percents?
        $delta_perc = isset($definition['limit_delta_perc']) && $definition['limit_delta_perc'];

        // Warning limits
        $limit_delta_warn = FALSE;
        if (isset($definition['oid_limit_delta_warn'])) {
            $oid_limit = 'oid_limit_delta_warn';
            if (isset($entity[$definition[$oid_limit]])) {
                // Named oid, exist in table
                $limit_delta_warn = $entity[$definition[$oid_limit]];
            } elseif (str_contains($definition[$oid_limit], '.')) {
                // Single param oid (outside table)
                $limit_delta_warn = snmp_cache_oid($device, $definition[$oid_limit], $definition['mib']);
            }
        } elseif (isset($definition['limit_delta_warn'])) {
            $limit_delta_warn = $definition['limit_delta_warn'];
        }

        if (is_numeric($limit_delta_warn)) {
            if ($delta_perc) {
                $limit_delta_warn = $limit_nominal * ($limit_delta_warn / 100);
            }

            $options['limit_low_warn']  = $limit_nominal - $limit_delta_warn; //$definition['limit_scale'];
            $options['limit_high_warn'] = $limit_nominal + $limit_delta_warn; //$definition['limit_scale'];
            $options['limit_low_warn']  = scale_value($options['limit_low_warn'], $limit_scale);
            $options['limit_high_warn'] = scale_value($options['limit_high_warn'], $limit_scale);
        }

        // Alert limits
        $limit_delta = FALSE;
        if (isset($definition['oid_limit_delta'])) {
            $oid_limit = 'oid_limit_delta';
            if (isset($entity[$definition[$oid_limit]])) {
                // Named oid, exist in table
                $limit_delta = $entity[$definition[$oid_limit]];
            } elseif (str_contains($definition[$oid_limit], '.')) {
                $limit_delta = snmp_cache_oid($device, $definition[$oid_limit], $definition['mib']);
            }
        } elseif (isset($definition['limit_delta'])) {
            $limit_delta = $definition['limit_delta'];
        }

        if (is_numeric($limit_delta)) {
            if ($delta_perc) {
                $limit_delta = $limit_nominal * ($limit_delta / 100);
            }

            $options['limit_low']  = $limit_nominal - $limit_delta; //$definition['limit_scale'];
            $options['limit_high'] = $limit_nominal + $limit_delta; //$definition['limit_scale'];
            $options['limit_low']  = scale_value($options['limit_low'], $limit_scale);
            $options['limit_high'] = scale_value($options['limit_high'], $limit_scale);
        }
    }

    // One last step for convert limit unit, when value and limits use different units (ie HP-ICF-TRANSCEIVER-MIB)
    if (isset($definition['limit_unit'])) {
        foreach (['limit_low', 'limit_low_warn', 'limit_high_warn', 'limit_high'] as $limit) {
            if (isset($options[$limit])) {
                $options[$limit] = value_to_si($options[$limit], $definition['limit_unit'], $definition['class']);
            }
        }
    }

    // Allow disable auto limits by definitions
    if (isset($definition['limit_auto'])) {
        $options['limit_auto'] = $definition['limit_auto'];
    }

    // Limit by (ie for counters)
    if (isset($definition['limit_by'])) {
        $options['limit_by'] = $definition['limit_by'];
    }

    return $options;
}

/**
 * Translate an entity type to the relevant table and the identifier field name
 *
 * @param string entity_type
 *
 * @return string entity_table
 * @return array entity_id
 */
// TESTME needs unit testing
function entity_type_translate_array($entity_type)
{
    $translate = $GLOBALS['config']['entities'][$entity_type];

    //print_r($GLOBALS['config']['entities']);

    // Base fields
    // FIXME, not listed here: agg_graphs, metric_graphs
    $fields = ['name',               // Base entity name
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
               'graph'];
    foreach ($fields as $field) {
        if (isset($translate[$field])) {
            $data[$field] = $translate[$field];
        } elseif (isset($GLOBALS['config']['entities']['default'][$field])) {
            $data[$field] = $GLOBALS['config']['entities']['default'][$field];
        }
    }

    // Table fields
    $fields_table = [// Common fields
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
    ];
    if (isset($translate['table_fields'])) {
        // New definition style
        foreach ($translate['table_fields'] as $field => $entry) {
            // Add old style name (ie 'id_field') for compatibility
            $data[$field . '_field'] = $entry;
        }
    }

    return $data;
}

/**
 * Returns TRUE if the logged in user is permitted to view the supplied entity.
 *
 * @param string|integer $entity_id
 * @param string         $entity_type
 * @param string|mixed   $device_id
 * @param array          $permissions Permissions array, by default used global var $permissions generated by permissions_cache()
 *
 * @return bool
 */
function is_entity_permitted($entity_id, $entity_type, $device_id = NULL, $permissions = NULL)
{
    if ($_SESSION['userlevel'] >= 5) {
        // User not limited (userlevel >= 5)
        if (OBS_DEBUG) {
            $debug_msg = "PERMISSIONS CHECK. Entity type: $entity_type, Entity ID: $entity_id, Device ID: " . ($device_id ?: 'NULL') . ", Allowed: TRUE.";
            if (isset($GLOBALS['notifications'])) {
                $GLOBALS['notifications'][] = ['text' => $debug_msg, 'severity' => 'debug'];
            } else {
                print_debug($debug_msg);
            }
        }
        return TRUE;
    }

    if (is_null($permissions) && isset($GLOBALS['permissions'])) {
        // Note, pass permissions array by param used in permissions_cache()
        $permissions = (array)$GLOBALS['permissions'];
    }

    if (!is_numeric($device_id) && $entity_type !== "group") {
        $device_id = get_device_id_by_entity_id($entity_id, $entity_type);
    }

    if (is_numeric($device_id) && device_permitted($device_id)) {
        $allowed = TRUE;
    } elseif (isset($permissions[$entity_type][$entity_id]) && $permissions[$entity_type][$entity_id]) {
        $allowed = TRUE;
    } elseif (isset($GLOBALS['auth']) && is_graph()) {
        $allowed = $GLOBALS['auth'];
    } else {
        $allowed = FALSE;
    }

    if (OBS_DEBUG) {
        $debug_msg = "PERMISSIONS CHECK. Entity type: $entity_type, Entity ID: $entity_id, Device ID: " . ($device_id ?: 'NULL') . ", Allowed: " . ($allowed ? 'TRUE' : 'FALSE') . ".";
        if (isset($GLOBALS['notifications'])) {
            $GLOBALS['notifications'][] = ['text' => $debug_msg, 'severity' => 'debug'];
        } else {
            print_debug($debug_msg);
        }
    }
    return $allowed;
}

/**
 * Returns TRUE if the logged in user is permitted to view the supplied entity.
 *
 * @param string|integer $entity_id
 * @param string         $entity_type
 * @param string|mixed   $device_id
 * @param array          $permissions Permissions array, by default used global var $permissions generated by permissions_cache()
 *
 * @return bool
 */
function is_entity_write_permitted($entity_id, $entity_type, $device_id = NULL, $permissions = NULL)
{

    if (is_null($permissions) && isset($GLOBALS['permissions'])) {
        // Note, pass permissions array by param used in permissions_cache()
        $permissions = (array)$GLOBALS['permissions'];
    }

    if (!is_numeric($device_id)) {
        $device_id = get_device_id_by_entity_id($entity_id, $entity_type);
    }

    if ($_SESSION['userlevel'] >= 9) {
        // User has global device/entity write permissions
        $allowed = TRUE;
    } elseif (is_numeric($device_id) && isset($permissions['device'][$device_id]) && $permissions['device'][$device_id] === "rw") {
        // User has device write permissions
        $allowed = TRUE;
    } elseif (isset($permissions[$entity_type][$entity_id]) && $permissions[$entity_type][$entity_id] === "rw") {
        $allowed = TRUE;
    } else {
        $allowed = FALSE;
    }

    if (OBS_DEBUG) {
        $debug_msg = "WRITE PERMISSIONS CHECK. Entity type: $entity_type, Entity ID: $entity_id, Device ID: " . ($device_id ?: 'NULL') . ", Allowed: " . ($allowed ? 'TRUE' : 'FALSE') . ".";
        if (isset($GLOBALS['notifications'])) {
            $GLOBALS['notifications'][] = ['text' => $debug_msg, 'severity' => 'debug'];
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
 * @param $entity      array
 *
 */
// TESTME needs unit testing
function entity_rewrite($entity_type, &$entity)
{
    $translate = entity_type_translate_array($entity_type);

    // By default, fill $entity['entity_name'] with name_field contents.
    if (isset($translate['name_field'])) {
        $entity['entity_name'] = $entity[$translate['name_field']];
    }

    // By default, fill $entity['entity_shortname'] with shortname_field contents. Fallback to entity_name when field name is not set.
    if (isset($translate['shortname_field'])) {
        $entity['entity_shortname'] = $entity[$translate['name_field']];
    } else {
        $entity['entity_shortname'] = $entity['entity_name'];
    }

    // By default, fill $entity['entity_descr'] with descr_field contents.
    if (isset($translate['descr_field'])) {
        $entity['entity_descr'] = $entity[$translate['descr_field']];
    }

    // By default, fill $entity['entity_id'] with id_field contents.
    if (isset($translate['id_field'])) {
        $entity['entity_id'] = $entity[$translate['id_field']];
    }

    switch ($entity_type) {
        case "bgp_peer":
        case "bgp_peer_af":
            // Special handling of name/shortname/descr for bgp_peer, since it combines multiple elements.

            $addr = ip_compress($entity['bgpPeerRemoteAddr']);

            $entity['entity_name']      = "AS" . $entity['bgpPeerRemoteAs'] . " " . $addr;
            $entity['entity_shortname'] = $addr;
            $entity['entity_descr']     = $entity['astext'];

            // Special code for BGP Peer AFI/SAFI
            if ($entity_type === "bgp_peer_af") {
                $entity['entity_name']      .= " " . $entity['afi'] . "/" . $entity['safi'];
                $entity['entity_shortname'] .= " " . $entity['afi'] . "/" . $entity['safi'];
            }


            break;

        case "sla":
            /* moved to humanize_sla()
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
            */
            $entity['entity_name']      = $entity['sla_descr'];
            $entity['entity_shortname'] = "#" . $entity['sla_index'] . " (" . $entity['sla_tag'] . ")";
            break;

        case "pseudowire":
            $entity['entity_name']      = $entity['pwID'] . ($entity['pwDescr'] ? " (" . $entity['pwDescr'] . ")" : '');
            $entity['entity_shortname'] = $entity['pwID'];
            break;

        case 'probe':
            if (strlen($entity['entity_descr'])) {
                $entity['entity_name'] .= ' (' . $entity['entity_descr'] . ')';
            }
            break;
    }
}

/**
 * Generates a URL to reach the entity's page (or the most specific list page the entity appears on)
 * Has no return value, it modifies the $entity array in-place.
 *
 * @param $entity_type string
 * @param $entity      array|integer
 * @param $text        string|null
 *
 */
// TESTME needs unit testing
function generate_entity_link($entity_type, $entity, $text = NULL, $graph_type = NULL, $escape = TRUE, $options = FALSE)
{
    if (is_numeric($entity)) {
        $entity = get_entity_by_id_cache($entity_type, $entity);
    }
    // Compat with old boolean $short option
    if (is_array($options)) {
        $short    = isset($options['short']) && $options['short'];
        $icon     = isset($options['icon']) && $options['icon'];
        $url_only = isset($options['url']) && $options['url'];
    } else {
        $short    = $options;
        $icon     = FALSE;
        $url_only = FALSE;
        $options  = [];
    }
    if ($icon) {
        // Get entity icon and force do not escape
        $text   = get_icon($GLOBALS['config']['entities'][$entity_type]['icon']);
        $escape = FALSE;
    }

    entity_rewrite($entity_type, $entity);

    switch ($entity_type) {
        case "device":
            if ($icon) {
                $link = generate_device_link($entity, $text, [], FALSE);
            } elseif ($url_only) {
                return generate_device_url($entity);
            } else {
                // Not sure, forced short as previous
                $link = generate_device_link_short($entity, [], 16);
            }
            break;
        case "mempool":
            $url = generate_url(['page' => 'device', 'device' => $entity['device_id'], 'tab' => 'health', 'metric' => 'mempool']);
            break;
        case "processor":
            //r($entity);
            if (isset($entity['id']) && is_array($entity['id'])) {
                // Multi-processors list
                $ids                 = implode(',', $entity['id']);
                $entity['entity_id'] = $ids;
            } else {
                $ids = $entity['processor_id'];
            }
            $url = generate_url(['page' => 'device', 'device' => $entity['device_id'], 'tab' => 'health', 'metric' => 'processor', 'processor_id' => $ids]);
            break;
        case "status":
            $url = generate_url(['page' => 'device', 'device' => $entity['device_id'], 'tab' => 'health', 'metric' => 'status', 'id' => $entity['status_id']]);
            break;
        case "sensor":
            $url = generate_url(['page' => 'device', 'device' => $entity['device_id'], 'tab' => 'health', 'metric' => $entity['sensor_class'], 'id' => $entity['sensor_id']]);
            break;
        case "counter":
            $url = generate_url(['page' => 'device', 'device' => $entity['device_id'], 'tab' => 'health', 'metric' => 'counter', 'id' => $entity['counter_id']]);
            break;
        case "printersupply":
            $url = generate_url(['page' => 'device', 'device' => $entity['device_id'], 'tab' => 'printing', 'supply' => $entity['supply_type']]);
            break;
        case "port":
            if ($icon) {
                $link = generate_port_link($entity, $text, $graph_type, FALSE, FALSE);
            } elseif ($url_only) {
                return generate_port_url($entity);
            } else {
                //$link = generate_port_link($entity, NULL, $graph_type, $escape, $short);
                $link = generate_port_link($entity, $text, $graph_type, $escape, $short);
            }
            break;
        case "storage":
            $url = generate_url(['page' => 'device', 'device' => $entity['device_id'], 'tab' => 'health', 'metric' => 'storage']);
            break;
        case "bgp_peer":
        case "bgp_peer_af":
            $url = generate_url(['page' => 'device', 'device' => ($entity['peer_device_id'] ? $entity['peer_device_id'] : $entity['device_id']), 'tab' => 'routing', 'proto' => 'bgp']);
            break;
        case "netscalervsvr":
            $url = generate_url(['page' => 'device', 'device' => $entity['device_id'], 'tab' => 'loadbalancer', 'type' => 'netscaler_vsvr', 'vsvr' => $entity['vsvr_id']]);
            break;
        case "netscalersvc":
            $url = generate_url(['page' => 'device', 'device' => $entity['device_id'], 'tab' => 'loadbalancer', 'type' => 'netscaler_services', 'svc' => $entity['svc_id']]);
            break;
        case "netscalersvcgrpmem":
            $url = generate_url(['page' => 'device', 'device' => $entity['device_id'], 'tab' => 'loadbalancer', 'type' => 'netscaler_servicegroupmembers', 'svc' => $entity['svc_id']]);
            break;
        case "p2pradio":
            $url = generate_url(['page' => 'device', 'device' => $entity['device_id'], 'tab' => 'p2pradios']);
            break;
        case "sla":
            $url = generate_url(['page' => 'device', 'device' => $entity['device_id'], 'tab' => 'slas', 'id' => $entity['sla_id']]);
            break;
        case "pseudowire":
            $url = generate_url(['page' => 'device', 'device' => $entity['device_id'], 'tab' => 'pseudowires', 'id' => $entity['pseudowire_id']]);
            break;
        case "maintenance":
            $url = generate_url(['page' => 'alert_maintenance', 'maintenance' => $entity['maint_id']]);
            break;
        case "group":
            $url = generate_url(['page' => 'group', 'group_id' => $entity['group_id']]);
            break;
        case "virtualmachine":
            // If we know this device by its vm name in our system, create a link to it, else just print the name.
            if (get_device_id_by_hostname($entity['vm_name'])) {
                if ($url_only) {
                    return generate_device_url(device_by_name($entity['vm_name']));
                }
                $link = generate_device_link(device_by_name($entity['vm_name']));
            } else {
                // Hardcode $link to just show the name, no actual link
                $link = $entity['vm_name'];
            }
            break;
        case 'probe':
            $url = generate_url(['page' => 'device', 'device' => $entity['device_id'], 'tab' => 'probes']);
            break;
        default:
            $url = NULL;
    }

    if ($url_only) {
        return $url;
    }
    if (isset($link)) {
        return $link;
    }

    if (!isset($text)) {
        if ($short && $entity['entity_shortname']) {
            $text = $entity['entity_shortname'];
        } else {
            $text = $entity['entity_name'];
        }
    }
    if ($escape) {
        $text = escape_html($text);
    }
    $link = '<a href="' . $url . '" class="entity-popup ' . $entity['html_class'] . '" data-eid="' . $entity['entity_id'] . '" data-etype="' . $entity_type . '">' . $text . '</a>';

    return $link;
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
 * This function generate url to entity
 *
 * @param $entity_type
 * @param $entity
 *
 * @return string
 */
function generate_entity_url($entity_type, $entity)
{
    return generate_entity_link($entity_type, $entity, NULL, NULL, FALSE, ['url' => TRUE]);
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

    switch ($entity_type) {
        case 'sensor':
            $filename = get_sensor_rrd($device, $entity);
            break;
        case 'status':
            $filename = get_status_rrd($device, $entity);
            break;
        case 'counter':
            $filename = get_counter_rrd($device, $entity);
            break;
        case 'processor':
            $filename = get_processor_rrd($device, $entity);
            break;
    }

    return $filename;
}

// EOF
