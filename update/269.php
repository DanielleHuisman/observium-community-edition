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

foreach (dbFetchRows("SELECT * FROM `alert_tests` ". $where, $args) as $entry)
{
  $conditions = json_decode($entry['conditions'], TRUE);
  
  for ($i = 0; $i < count($conditions); $i++)
  {
    if ($conditions[$i]['value'] == '' && $conditions[$i]['metric'] == '' && $conditions[$i]['condition'] == '')
    {
      // Remove invalid condition entry
      unset($conditions[$i]);
    }
  }

  dbUpdate(array('conditions' => json_encode($conditions)), 'alert_tests', '`alert_test_id` = ?', array($entry['alert_test_id']));
  echo('.');
}

// EOF
