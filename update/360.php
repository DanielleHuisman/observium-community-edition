<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage update
 * @author     Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

//return; // This update not quite necessary

/*

echo("Convert old polling/discovery times");

foreach (dbFetchColumn("SELECT `device_id` FROM `devices`") as $device_id)
{
  $query = 'SELECT * FROM `devices_perftimes` WHERE `operation` = ? AND `device_id` = ? ORDER BY `start` DESC';

  // Poller history
  $old_poller_history = array();
  foreach (dbFetchRows($query . ' LIMIT 288', array('poll', $device_id)) as $entry)
  {
    $old_poller_history[intval($entry['start'])] = $entry['duration'];
  }

  // Discovery history
  $old_discovery_history = array();
  foreach (dbFetchRows($query . ' LIMIT 100', array('discover', $device_id)) as $entry)
  {
    $old_discovery_history[intval($entry['start'])] = $entry['duration'];
  }

  // Update if exist old history
  if (count($old_poller_history) || count($old_discovery_history))
  {
    // Fetch previous device state (do not use $device array here, for exclude update history collisions)
    $device_state = dbFetchCell('SELECT `device_state` FROM `devices` WHERE `device_id` = ?;', array($device_id));
    //print_vars($device_state);
    if ($device_state)
    {
      $device_state = safe_unserialize($device_state);
    } else {
      $device_state = array();
    }

    // Poller history
    if (!isset($device_state['poller_history']))
    {
      $device_state['poller_history'] = array();
    }
    $device_state['poller_history'] = array_slice($device_state['poller_history'] + $old_poller_history, 0, 288, TRUE);

    // Discovery history
    if (!isset($device_state['discovery_history']))
    {
      $device_state['discovery_history'] = array();
    }
    $device_state['discovery_history'] = array_slice($device_state['discovery_history'] + $old_discovery_history, 0, 100, TRUE);

    print_debug_vars($device_state);

    $updated = dbUpdate(array('device_state' => serialize($device_state)), 'devices', '`device_id` = ?', array($device_id));
    if ($updated)
    {
      echo('.');
    }
  }
}

*/

//return FALSE; // Debug, do not increase db schema

// EOF

