<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$is_permitted = FALSE;

// Populate $vars['id'] with group entities if we got a group
if (isset($vars['group_id'])) {
    if ($group = get_group_by_id($vars['group_id'])) {
        // Guard clause to bail if we've been given a non-sensor group.
        if ($group['entity_type'] != "sensor") {
            unset($vars['group_id']);
            unset($vars['id']);
        }

        $vars['id']    = get_group_entities($group['group_id']);
        $title_array[] = ['text' => 'Sensors Group'];
        $title_array[] = ['text' => $group['group_name'], 'url' => generate_url(['page' => 'group', 'group_id' => $group['group_id']])];
    }
} else {

    if (!is_array($vars['id'])) {
        $vars['id'] = [$vars['id']];
    }

    $title_array   = [];
    $title_array[] = ['text' => 'Multiple Sensors'];

}

if ($auth || $is_permitted || $_SESSION['userlevel'] >= 5) {
    $auth = TRUE;

    foreach ($vars['id'] as $sensor_id) {
        if (is_numeric($sensor_id) && is_entity_permitted('sensor', $sensor_id)) {
            $is_permitted = TRUE;
        } else {
            $is_permitted = FALSE;
            // Bail on first reject.
            break;
        }
    }

    $title_array[] = ['text' => safe_count($vars['id']) . ' sensors'];

}

unset($is_permitted);

// EOF

