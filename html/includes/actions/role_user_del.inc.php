<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

if ($_SESSION['userlevel'] == 10 && request_token_valid($vars)) // Only valid forms from level 10 users
{

    $where  = '`role_id` = ? AND `user_id` = ? AND `auth_mechanism` = ?';
    $params = [$vars['role_id'], $vars['user_id'], $config['auth_mechanism']];
    if (dbExist('roles_users', $where, $params)) {
        dbDelete('roles_users', $where, $params);
    } else {
    }
}

// EOF
