<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     discovery
 * @author         Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$GLOBALS['valid']['printersupply'] = [];

// Include all discovery modules by MIB
$include_dir = "includes/discovery/printersupplies";
include("includes/include-dir-mib.inc.php");

check_valid_printer_supplies($device);

echo(PHP_EOL);

unset($GLOBALS['valid']['printersupply']);

// EOF
