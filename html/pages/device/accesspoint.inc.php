<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     web
 * @copyright  (C) Adam Armstrong
 *
 */

$sql = "SELECT * FROM `accesspoints` LEFT JOIN `accesspoints-state` USING (`accesspoint_id`) 
         WHERE `device_id` = ? AND accesspoints.`accesspoint_id` = ? AND `deleted` = '0' 
         ORDER BY `name`,`radio_number` ASC";

$ap = dbFetchRow($sql, [$device['device_id'], $vars['ap']]);

if (safe_empty($ap)) {
    print_error("No Access Point Found");
    return;
}

echo generate_box_open();

echo '<table class="table table-striped">';

include($config['html_dir'] . '/includes/print-accesspoint.inc.php');

echo '</table>';

echo generate_box_close();

// EOF
