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

include($config['html_dir'] . "/includes/alerting-navbar.inc.php");

// Pagination
$vars['pagination'] = TRUE;

print_logalert_log($vars);

// EOF
