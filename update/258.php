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

$contacts = dbFetchRows("SELECT * FROM `alert_contacts` WHERE `contact_method`='email' AND `contact_endpoint` NOT LIKE 'email::%'");

foreach ($contacts as $contact)
{
  dbUpdate(array('contact_endpoint' => 'email::' . $contact['contact_endpoint']), 'alert_contacts', '`contact_id` = ?', array($contact['contact_id']));
  echo('.');
}

// EOF
