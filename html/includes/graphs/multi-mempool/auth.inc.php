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

$is_permitted = FALSE;
$entity_type  = "mempool";
$entity_data  = $GLOBALS['config']['entities'][$entity_type];

include_once($config['html_dir'] . "/includes/graphs/multi-auth-generic.inc.php");
