<?php

// Generic code to handle checking authorisation for multiple ids. Also handles expanding group id into multiple ids.

// Populate $vars['id'] with group entities if we got a group
if (isset($vars['group_id'])) {
    if ($group = get_group_by_id($vars['group_id'])) {
        // Guard clause to bail if we've been given a non-port group.
        if ($group['entity_type'] != $entity_type) {
            unset($vars['group_id']);
            unset($vars['id']);
        }

        $vars['id']    = get_group_entities($group['group_id']);
        $title_array[] = ['text' => $entity_data['names'] . ' Group'];
        $title_array[] = ['text' => $group['group_name'], 'url' => generate_url(['page' => 'group', 'group_id' => $group['group_id']])];
    }
} else {

    if (!is_array($vars['id'])) {
        $vars['id'] = [$vars['id']];
    }

    $title_array   = [];
    $title_array[] = ['text' => 'Multiple ' . $entity_data['names']];

}

$is_permitted = FALSE;

// Loop each entity id and check if we're allowed to view it
// FIXME - perhaps just filter ones we're not allowed to see
foreach ($vars['id'] as $entity_id) {
    if (is_numeric($entity_id) && is_entity_permitted($entity_id, $entity_type)) {
        $is_permitted = TRUE;
    } else {
        $is_permitted = FALSE;
        // Bail on first reject.
        break;
    }
}

if ($auth || $is_permitted || $_SESSION['userlevel'] >= 5) {
    $title_array[] = ['text' => safe_count($vars['id']) . ' ' . $entity_data['names'], 'entity_type' => $entity_type, 'entities' => $vars['id']];
    $auth          = TRUE;
}