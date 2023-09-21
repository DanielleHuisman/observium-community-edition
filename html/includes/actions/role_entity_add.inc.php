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

if ($_SESSION['userlevel'] == 10 && request_token_valid($vars)) { // Only valid forms from level 10 users

    if (isset($vars['entity_id'])) {
        // use entity_id
    } elseif (isset($vars[$vars['entity_type'] . '_entity_id'])) {
        // use type_entity_id
        $vars['entity_id'] = $vars[$vars['entity_type'] . '_entity_id'];
    }

    if (!is_array($vars['entity_id'])) {
        $vars['entity_id'] = [$vars['entity_id']];
    }

    $changed = 0;
    foreach ($vars['entity_id'] as $entity_id) {
        if (get_entity_by_id_cache($vars['entity_type'], $entity_id)) { // Skip not exist entities
            if (!dbExist('roles_entity_permissions', '`role_id` = ? AND `entity_type` = ? AND `entity_id` = ?',
                         [$vars['role_id'], $vars['entity_type'], $entity_id])) {

                if (!in_array($vars['access'], ['ro', 'rw'])) {
                    $vars['access'] = 'ro';
                }

                dbInsert(['entity_id' => $entity_id, 'entity_type' => $vars['entity_type'], 'role_id' => $vars['role_id'], 'access' => $vars['access']],
                         'roles_entity_permissions');
                $changed++;
            }
        } else {
            print_error('Error: Invalid Entity.');
        }
    }

    // Reset permissions cache
    if ($changed) {
        set_cache_clear('wui');
    }
    unset($changed);
}

// EOF
