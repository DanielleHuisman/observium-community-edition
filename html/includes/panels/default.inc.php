<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage webui
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

$div_class = ""; // Class for each block in status summary
include($config['html_dir']."/includes/status-summary.inc.php");

print_alert_table(array('status' => 'failed', 'pagination' => FALSE, 'format' => 'condensed', 'short' => TRUE));

// EOF
