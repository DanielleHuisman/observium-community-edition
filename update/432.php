<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage db
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
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

