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

// Currently, allowed only for Admins
if (!$readwrite) {
    print_json_status('failed', 'Insufficient permissions to delete role.');
    return;
}

$role_id = (int)$vars['role_id'];
if ($role_id > 0) {
    $rows_deleted = dbDelete('roles', '`role_id` = ?', [$role_id]);
    //$rows_deleted = 0;
    if ($rows_deleted > 0) {
        dbDelete('roles_entity_permissions', '`role_id` = ?', [$role_id]);
        dbDelete('roles_permissions', '`role_id` = ?', [$role_id]);
        dbDelete('roles_users', '`role_id` = ?', [$role_id]);
        print_json_status('ok', 'Role deleted successfully.', ['reload' => TRUE]);
    } else {
        print_json_status('failed', 'Failed to delete role.');
    }
} else {
    print_json_status('failed', 'Invalid role ID.');
}

// EOF
