<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2020 Observium Limited
 *
 */

if ($_SESSION['userlevel'] == 10 && request_token_valid($vars)) // Only valid forms from level 10 users
{

  if (isset($vars['entity_id']))
  {
  } // use entity_id
  elseif (isset($vars[$vars['entity_type'] . '_entity_id'])) // use type_entity_id
  {
    $vars['entity_id'] = $vars[$vars['entity_type'] . '_entity_id'];
  }
  if (!is_array($vars['entity_id']))
  {
    $vars['entity_id'] = array($vars['entity_id']);
  }

  foreach ($vars['entity_id'] as $entity_id)
  {
    if (get_entity_by_id_cache($vars['entity_type'], $entity_id)) // Skip not exist entities
    {
      if (!dbExist('roles_entity_permissions', '`role_id` = ? AND `entity_type` = ? AND `entity_id` = ?',
                   array($vars['role_id'], $vars['entity_type'], $entity_id)
      ))
      {

        if(!in_array($vars['access'], array('ro', 'rw'))) { $vars['access'] = 'ro'; }

        dbInsert(array('entity_id' => $entity_id, 'entity_type' => $vars['entity_type'], 'role_id' => $vars['role_id'], 'access' => $vars['access']),
                 'roles_entity_permissions'
        );
      }
    } else { print_error('Error: Invalid Entity.'); }
  }
}

// EOF
