<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage entities
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2020 Observium Limited
 *
 */


/**
 * If a device is up, return its uptime, otherwise return the
 * time since the last time we were able to poll it.  This
 * is not very accurate, but better than reporting what the
 * uptime was at some time before it went down.
 * @param array  $device
 * @param string $format
 *
 * @return string
 */
// TESTME needs unit testing
function device_uptime($device, $format = "long")
{
  if ($device['status'] == 0)
  {
    if ($device['last_polled'] == 0)
    {
      return "Never polled";
    }

    $since = time() - strtotime($device['last_polled']);
    //$reason = isset($device['status_type']) && $format == 'long' ? '('.strtoupper($device['status_type']).') ' : '';
    $reason = isset($device['status_type']) ? '('.strtoupper($device['status_type']).') ' : '';

    return "Down $reason" . format_uptime($since, $format);
  } else {
    return format_uptime($device['uptime'], $format);
  }
}

//FIXME. Need refactor
function deviceUptime($device, $format = "long")
{
  return device_uptime($device, $format);
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function device_by_name($name, $refresh = 0)
{
  // FIXME - cache name > id too.
  return device_by_id_cache(get_device_id_by_hostname($name), $refresh);
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function device_by_id_cache($device_id, $refresh = 0)
{
  global $cache;

  if (!$refresh && isset($cache['devices']['id'][$device_id]) && is_array($cache['devices']['id'][$device_id]))
  {
    $device = $cache['devices']['id'][$device_id];
  } else {
    $device = dbFetchRow("SELECT * FROM `devices` WHERE `device_id` = ?", array($device_id));
  }

  if (!empty($device))
  {
    humanize_device($device);
    if ($refresh || !isset($device['graphs']))
    {
      // Fetch device graphs
      $device['graphs'] = dbFetchRows("SELECT * FROM `device_graphs` WHERE `device_id` = ?", array($device_id));
    }
    $cache['devices']['id'][$device_id] = $device;

    return $device;
  } else {
    return FALSE;
  }
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_device_id_by_hostname($hostname)
{
  global $cache;

  if (isset($cache['devices']['hostname'][$hostname]))
  {
    $id = $cache['devices']['hostname'][$hostname];
  } else {
    $id = dbFetchCell("SELECT `device_id` FROM `devices` WHERE `hostname` = ?", array($hostname));
  }

  if (is_numeric($id))
  {
    return $id;
  } else {
    return FALSE;
  }
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_device_id_by_port_id($port_id)
{
  if (is_numeric($port_id))
  {
    $device_id = dbFetchCell("SELECT `device_id` FROM `ports` WHERE `port_id` = ?", array($port_id));
    if (is_numeric($device_id))
    {
      return $device_id;
    }
  }

  return FALSE;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_device_id_by_mac($mac, $exclude_device_id = NULL)
{
  $remote_mac = mac_zeropad($mac);
  if ($remote_mac && $remote_mac != '000000000000')
  {
    $where = '`deleted` = ? AND `ifPhysAddress` = ?';
    $params = [ 0, $remote_mac ];
    if (is_numeric($exclude_device_id))
    {
      $where .= ' AND `device_id` != ?';
      $params[] = $exclude_device_id;
    }
    $device_id = dbFetchCell("SELECT `device_id` FROM `ports` WHERE $where LIMIT 1;", $params);
    if (is_numeric($device_id))
    {
      return $device_id;
    }
  }

  return FALSE;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_device_id_by_app_id($app_id)
{
  if (is_numeric($app_id))
  {
    $device_id = dbFetchCell("SELECT `device_id` FROM `applications` WHERE `app_id` = ?", array($app_id));
  }
  if (is_numeric($device_id))
  {
    return $device_id;
  } else {
    return FALSE;
  }
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_device_entphysical_state($device)
{
  $state = array();
  foreach (dbFetchRows("SELECT * FROM `entPhysical-state` WHERE `device_id` = ?", array($device)) as $entity)
  {
    $state['group'][$entity['group']][$entity['entPhysicalIndex']][$entity['subindex']][$entity['key']] = $entity['value'];
    $state['index'][$entity['entPhysicalIndex']][$entity['subindex']][$entity['group']][$entity['key']] = $entity['value'];
  }

  return $state;
}

/**
 * Generate device tags for use with some entities (ie probes)
 * @param array $device
 * @param bool $escape
 *
 * @return array
 */
function generate_device_tags($device, $escape = TRUE)
{
  $params = [
    // Basic
    'hostname',
    // SNMP
    'snmp_version', 'snmp_community', 'snmp_context', 'snmp_authlevel', 'snmp_authname', 'snmp_authpass',  'snmp_authalgo',
    'snmp_cryptopass', 'snmp_cryptoalgo', 'snmp_port', 'snmp_timeout', 'snmp_retries'
  ];

  $device_tags = [];

  foreach($params as $variable)
  {
    if (!empty($device[$variable]) || in_array($variable, ['snmp_timeout', 'snmp_authname']))
    {
      switch ($variable)
      {
        case 'snmp_version':
          $device_tags[$variable] = str_replace('v', '', $device[$variable]);
          break;
        case 'snmp_authalgo':
        case 'snmp_cryptoalgo':
          $device_tags[$variable] = strtoupper($device[$variable]);
          break;
        case 'snmp_authname':
          // Always pass username, default observium (need for noathnopriv)
          $device_tags[$variable] = strlen($device[$variable]) ? $device[$variable] : 'observium';
          break;
        case 'snmp_timeout':
          // Always pass timeout, default 15
          $device_tags[$variable] = is_numeric($device[$variable]) ? $device[$variable] : 15;
          break;
        default:
          $device_tags[$variable] = $device[$variable];
      }
      // Escape for pass to shell cmd
      if ($escape) { $device_tags[$variable] = escapeshellarg($device_tags[$variable]); }
    }
  }

  return $device_tags;
}

/* OBSOLETE, BUT STILL USED FUNCTIONS */

// CLEANME remove when all function calls will be deleted
function get_dev_attribs($device_id)
{
  // Call to new function
  return get_entity_attribs('device', $device_id);
}

// CLEANME remove when all function calls will be deleted
function set_dev_attrib($device, $attrib_type, $attrib_value)
{
  // Call to new function
  return set_entity_attrib('device', $device, $attrib_type, $attrib_value);
}

// CLEANME remove when all function calls will be deleted
function del_dev_attrib($device, $attrib_type)
{
  // Call to new function
  return del_entity_attrib('device', $device, $attrib_type);
}

/** OLD DEPRECATED FUNCTIONS */

/* CLEANME Unused, useless function
function gethostosbyid($id)
{
  global $cache;

  if (isset($cache['devices']['id'][$id]['os']))
  {
    $os = $cache['devices']['id'][$id]['os'];
  } else {
    $os = dbFetchCell("SELECT `os` FROM `devices` WHERE `device_id` = ?", array($id));
  }

  return $os;
}
*/

/* CLEANME Unused, useless function
function get_device_hostname_by_device_id($id)
{
  global $cache;

  if (isset($cache['devices']['id'][$id]['hostname']))
  {
    $hostname = $cache['devices']['id'][$id]['hostname'];
  } else {
    $hostname = dbFetchCell("SELECT `hostname` FROM `devices` WHERE `device_id` = ?", array($id));
  }

  return $hostname;
}
*/

// EOF
