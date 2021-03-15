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

  $where = '`role_id` = ? AND `entity_type` = ?' . generate_query_values($vars['entity_id'], 'entity_id');
  //if (@dbFetchCell("SELECT COUNT(*) FROM `entity_permissions` WHERE " . $where, array($vars['user_id'], $vars['entity_type'])))
  if (dbExist('roles_entity_permissions', $where, array($vars['role_id'], $vars['entity_type'])))
  {
    dbDelete('roles_entity_permissions', $where, array($vars['role_id'], $vars['entity_type']));

    //print_vars(dbError());

  } else { }
}

echo ("nope"); // Hrm?

// EOF
