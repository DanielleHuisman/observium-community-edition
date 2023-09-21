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


switch (str_replace('->', '|', $vars['setting'])) {
    case "theme":
    case "web_theme_default":
        $pref = 'web_theme_default';
        if ($vars['value'] === 'reset') {
            session_unset_var("theme");
            if ($config['web_theme_default'] === 'system') {
                // Override default
                session_unset_var("theme_default");
            }

            if (del_user_pref($_SESSION['user_id'], $pref)) {
                print_json_status('ok', 'Theme reset.');
            }
        } elseif (isset($config['themes'][$vars['value']]) || $vars['value'] === 'system') {
            if (set_user_pref($_SESSION['user_id'], $pref, serialize($vars['value']))) {
                print_json_status('ok', 'Theme set.');
            }
        } else {
            print_json_status('failed', 'Invalid theme.');
        }
        break;

    case "big_graphs":
        $pref = 'graphs|size';
        if (set_user_pref($_SESSION['user_id'], $pref, serialize('big'))) {
            print_json_status('ok', 'Big graphs set.');
            session_unset_var("big_graphs"); // clear old
        }
        //session_set_var("big_graphs", TRUE);
        //print_json_status('ok', 'Big graphs set.');
        break;

    case "normal_graphs":
        $pref = 'graphs|size';
        if (set_user_pref($_SESSION['user_id'], $pref, serialize('normal'))) {
            print_json_status('ok', 'Normal graphs set.');
            session_unset_var("big_graphs"); // clear old
        }
        //session_unset_var("big_graphs");
        //print_json_status('ok', 'Small graphs set.');
        break;

    case "sensors|web_measured_compact":
        // BOOL values
        $pref = $vars['setting'];
        if (set_user_pref($_SESSION['user_id'], $pref, serialize(get_var_true($vars['value'])))) {
            print_json_status('ok', 'Setting was set.', ['reload' => TRUE]);
        }
        break;

}
// EOF
