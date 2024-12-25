<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage actions
 * @copyright  (C) Adam Armstrong
 *
 */

if (!$readwrite) { // Only valid forms from level 10 users
    return;
}

switch ($vars['action']) {
    case 'role_add':

        if (!safe_empty($vars['role_name']) && !safe_empty($vars['role_descr'])) {

            $oid_id = dbInsert('roles', [ 'role_descr' => $vars['role_descr'],
                                          'role_name'  => $vars['role_name'] ]);

            if ($oid_id) {
                print_success("<strong>SUCCESS:</strong> Added role");
                return 1;
            }
            print_warning("<strong>WARNING:</strong> Role not added");
        } else {
            print_error("<strong>ERROR:</strong> All fields must be completed to add a new role.");
        }
        return;

    case 'role_entity_add':

        if (isset($vars['entity_id'])) {
            // use entity_id
        } elseif (isset($vars[$vars['entity_type'] . '_entity_id'])) {
            // use type_entity_id
            $vars['entity_id'] = $vars[$vars['entity_type'] . '_entity_id'];
        }

        if (!is_array($vars['entity_id'])) {
            $vars['entity_id'] = [$vars['entity_id']];
        }

        $added = [];
        foreach ($vars['entity_id'] as $entity_id) {
            if (get_entity_by_id_cache($vars['entity_type'], $entity_id)) { // Skip not exist entities
                if (!dbExist('roles_entity_permissions', '`role_id` = ? AND `entity_type` = ? AND `entity_id` = ?',
                             [$vars['role_id'], $vars['entity_type'], $entity_id])) {

                    if (!in_array($vars['access'], ['ro', 'rw'])) {
                        $vars['access'] = 'ro';
                    }

                    $added[] = dbInsert(['entity_id' => $entity_id, 'entity_type' => $vars['entity_type'], 'role_id' => $vars['role_id'], 'access' => $vars['access']],
                                        'roles_entity_permissions');
                }
            } else {
                print_error('Error: Invalid Entity.');
            }
        }

        // Reset permissions cache
        if ($added) {
            set_cache_clear('wui');
        }

        return count($added);

    case 'role_entity_del':
    case 'role_entity_delete':

        if (isset($vars['entity_id'])) {
            // use entity_id
        } elseif (isset($vars[$vars['entity_type'] . '_entity_id'])) {
            // use type_entity_id
            $vars['entity_id'] = $vars[$vars['entity_type'] . '_entity_id'];
        }

        $where = '`role_id` = ? AND `entity_type` = ?' . generate_query_values_and($vars['entity_id'], 'entity_id');
        if (dbExist('roles_entity_permissions', $where, [$vars['role_id'], $vars['entity_type']])) {
            // Reset permissions cache
            set_cache_clear('wui');

            return dbDelete('roles_entity_permissions', $where, [$vars['role_id'], $vars['entity_type']]);
        }

        //echo ("nope"); // Hrm?
        break;

    case 'role_permission_add':

        $added = [];
        foreach ($vars['permission'] as $permission) {
            if (isset($config['permissions'][$permission]) &&
                !dbExist('roles_permissions', '`role_id` = ? AND `permission` = ?', [ $vars['role_id'], $permission ])) {
                $added[] = dbInsert(['permission' => $permission, 'role_id' => $vars['role_id']], 'roles_permissions');
            }
        }

        return count($added);

    case 'role_permission_del':
    case 'role_permission_delete':

        $where = '`role_id` = ? AND `permission` = ?';
        if (dbExist('roles_permissions', $where, [$vars['role_id'], $vars['permission']])) {
            return dbDelete('roles_permissions', $where, [$vars['role_id'], $vars['permission']]);
        }

        break;

    case 'role_user_add':

        if (!is_array($vars['role_id'])) {
            $vars['role_id'] = [$vars['role_id']];
        }

        // We need to turn this into an array for use with the roles page, but not overwrite user_id so as not to break the users page.
        if (!is_array($vars['user_id'])) {
            $vars['user_ids'] = [$vars['user_id']];
        } else {
            $vars['user_ids'] = $vars['user_id'];

        }

        $user_list = auth_user_list();

        $added = [];
        foreach ($vars['user_ids'] as $user_id) {
            if (is_array($user_list[$user_id])) {
                foreach ($vars['role_id'] as $role_id) {
                    if (!dbExist('roles_users', '`role_id` = ? AND `user_id` = ? AND `auth_mechanism` = ?', [$role_id, $user_id, $config['auth_mechanism']])) {
                        $added[] = dbInsert(['user_id' => $user_id, 'role_id' => $role_id, 'auth_mechanism' => $config['auth_mechanism']], 'roles_users');
                    } else {
                        print_warning("<strong>WARNING:</strong> User " . $user_id . " is already a role " . $role_id . " member.");
                    }
                }
            } else {
                print_error("<strong>ERROR:</strong> Invalid user id.");
            }
        }

        return count($added);

    case 'role_user_del':

        $where  = '`role_id` = ? AND `user_id` = ? AND `auth_mechanism` = ?';
        $params = [$vars['role_id'], $vars['user_id'], $config['auth_mechanism']];
        if (dbExist('roles_users', $where, $params)) {
            return dbDelete('roles_users', $where, $params);
        }

        break;
}

// EOF