<?php


if ($_SESSION['userlevel'] == 10 && request_token_valid($vars)) // Only valid forms from level 10 users
{

    $where = '`role_id` = ? AND `permission` = ?';
    if (dbExist('roles_permissions', $where, [$vars['role_id'], $vars['permission']])) {
        dbDelete('roles_permissions', $where, [$vars['role_id'], $vars['permission']]);
    } else {
    }
}