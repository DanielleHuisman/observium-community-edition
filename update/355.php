<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage syslog
 * @author     Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Add device_id to entity_attribs

foreach(dbFetchRows("SELECT * FROM `entity_attribs`") AS $attrib)
{

  unset($device_id);

  $entity = get_entity_by_id_cache($attrib['entity_type'], $attrib['entity_id']);
  if(isset($entity['device_id']))
  {
    $device_id = $entity['device_id'];
  }

  if(is_numeric($device_id))
  {
    dbUpdate(array('device_id' => $device_id), 'entity_attribs', 'attrib_id = ?', array($attrib['attrib_id']));
  } else {
    dbDelete('entity_attribs', 'attrib_id = ?', array($attrib['attrib_id']));
  }
}

// EOF

