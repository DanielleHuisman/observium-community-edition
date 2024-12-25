<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     graphs
 * @copyright  (C) Adam Armstrong
 *
 */

if (!is_array($vars['id'])) {
    $vars['id'] = [$vars['id']];
}

$auth = TRUE;

foreach ($vars['id'] as $storage_id) {
    if (!$auth && !is_entity_permitted('storage', $storage_id)) {
        $auth = FALSE;
    }
}

$title = "Multi Storage :: ";



// EOF

