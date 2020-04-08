<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @author     Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2015 Observium Limited
 *
 */

$valid['printersupplies'] = array();

// Include all discovery modules by MIB
$include_dir = "includes/discovery/printersupplies";
include("includes/include-dir-mib.inc.php");

check_valid_printer_supplies($device, $valid);

echo(PHP_EOL);

unset($valid['printersupplies']);

// EOF
