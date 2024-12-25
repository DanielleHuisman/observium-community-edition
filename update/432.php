<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage db
 * @copyright  (C) Adam Armstrong
 *
 */

if (dbExist('entity_permissions', '`auth_mechanism` = ?', [ '' ]) ||
    dbExist('roles_users', '`auth_mechanism` = ?', [ '' ]))
{
  echo 'Update Roles auth: ';

  if (dbUpdate([ 'auth_mechanism' => $config['auth_mechanism'] ], 'entity_permissions', '`auth_mechanism` = ?', [ '' ]))
  {
    echo('.');
  }
  if (dbUpdate([ 'auth_mechanism' => $config['auth_mechanism'] ], 'roles_users', '`auth_mechanism` = ?', [ '' ]))
  {
    echo('.');
  }
  echo(PHP_EOL);
}

// EOF

