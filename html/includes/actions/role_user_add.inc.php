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

    if (!is_array($vars['user_id'])) {
        $vars['user_ids'] = array($vars['user_id']);
    } else {
        $vars['user_ids'] = $vars['user_id'];
    }

    if (!is_array($vars['role_id'])) {
        $vars['role_id'] = array($vars['role_id']);
    }

    $user_list = auth_user_list();


    foreach ($vars['user_ids'] as $user_id) {
        if (is_array($user_list[$user_id])) {
            foreach ($vars['role_id'] as $role_id) {
                if (!dbExist('roles_users', '`role_id` = ? AND `user_id` = ? AND `auth_mechanism` = ?', [ $role_id, $user_id, $config['auth_mechanism'] ]))
                {
                    dbInsert([ 'user_id' => $user_id, 'role_id' => $role_id, 'auth_mechanism' => $config['auth_mechanism'] ], 'roles_users');
                } else {
                    print_warning("<strong>WARNING:</strong> User " . $user_id . " is already a role " . $role_id . " member.");
                }
            }
        } else {
            print_error("<strong>ERROR:</strong> Invalid user id.");
        }
    }
}

// EOF
