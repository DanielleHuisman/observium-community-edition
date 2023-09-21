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

$is_permitted = FALSE;
$entity_type  = "mempool";
$entity_data  = $GLOBALS['config']['entities'][$entity_type];

include_once($config['html_dir'] . "/includes/graphs/multi-auth-generic.inc.php");
