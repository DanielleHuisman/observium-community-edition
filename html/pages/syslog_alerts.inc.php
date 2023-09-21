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

include($config['html_dir'] . "/includes/alerting-navbar.inc.php");

// Pagination
$vars['pagination'] = TRUE;

print_logalert_log($vars);

// EOF
