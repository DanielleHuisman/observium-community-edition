<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     webui
 * @copyright  (C) Adam Armstrong
 *
 */

$updated = '1';

$affected = dbDelete('services', '`service_id` = ?', [$_POST['service']]);

if ($affected) {
    $message       .= $message_break . $rows . " service deleted!";
    $message_break .= "<br />";
}

// EOF
