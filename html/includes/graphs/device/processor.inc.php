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

$sql = "SELECT * FROM `processors` WHERE `processor_type` != 'hr-average' AND `device_id` = ?";
if (isset($vars['filter_id'])) {
    $sql .= generate_where_clause(generate_query_values($vars['filter_id'], 'processor_id'));
}
$procs = dbFetchRows($sql, [$device['device_id']]);

if ($config['os'][$device['os']]['processor_stacked'] == 1 && $config['graphs']['stacked_processors'] == TRUE) {
    include($config['html_dir'] . "/includes/graphs/device/processor_stack.inc.php");
} else {
    include($config['html_dir'] . "/includes/graphs/device/processor_separate.inc.php");
}

// EOF
