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

echo 'Converting alert serialize() arrays to JSON: ';

foreach (dbFetchRows("SELECT * FROM `alert_tests`") as $entry)
{
  $conditions      = safe_unserialize($entry['conditions']);
  $conditions_json = safe_json_encode($conditions);
  dbUpdate(array('conditions' => $conditions_json), 'alert_tests', '`alert_test_id` = ?', array($entry['alert_test_id']));
  echo('.');
}

foreach (dbFetchRows("SELECT * FROM `alert_assoc`") as $entry)
{
  $attributes      = safe_unserialize($entry['attributes']);
  $attributes_json = safe_json_encode($attributes);

  $device_attributes      = safe_unserialize($entry['device_attributes']);
  $device_attributes_json = safe_json_encode($device_attributes);

  dbUpdate(array('attributes' => $attributes_json, 'device_attributes' => $device_attributes_json), 'alert_assoc', '`alert_assoc_id` = ?', array($entry['alert_assoc_id']));
  echo('.');
}

// EOF
