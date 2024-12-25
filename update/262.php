<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage update
 * @copyright  (C) Adam Armstrong
 *
 */

$contacts = dbFetchRows("SELECT * FROM `alert_contacts`");

foreach ($contacts as $contact)
{
  $endpoint = array();

  if (!json_decode($contact['contact_endpoint']))
  {
    foreach (explode("||", $contact['contact_endpoint']) as $datum)
    {
      list($field, $value) = explode("::", $datum);
      $endpoint[$field] = $value;
    }

    dbUpdate(array('contact_endpoint' => json_encode($endpoint)), 'alert_contacts', '`contact_id` = ?', array($contact['contact_id']));
    echo('.');
  }
}

// EOF
