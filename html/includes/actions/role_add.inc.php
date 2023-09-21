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
    if (!safe_empty($vars['role_name']) &&
        !safe_empty($vars['role_descr'])) {
        $oid_id = dbInsert('roles', ['role_descr' => $vars['role_descr'],
                                     'role_name'  => $vars['role_name']]
        );

        if ($oid_id) {
            print_success("<strong>SUCCESS:</strong> Added role");
        } else {
            print_warning("<strong>WARNING:</strong> Role not added");
        }
    } else {
        print_error("<strong>ERROR:</strong> All fields must be completed to add a new role.");
    }
}