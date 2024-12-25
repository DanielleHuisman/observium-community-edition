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

$div_class = ""; // Class for each block in status summary
include($config['html_dir'] . "/includes/status-summary.inc.php");

print_alert_table(['status' => 'failed', 'pagination' => FALSE, 'format' => 'condensed', 'short' => TRUE]);

// EOF
