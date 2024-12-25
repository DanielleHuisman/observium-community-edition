#!/usr/bin/env php
<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     tests
 * @copyright  (C) Adam Armstrong
 *
 */

chdir(dirname($argv[0]));

$options = getopt("ed");

include_once("includes/observium.inc.php");
include_once("html/includes/functions.inc.php");

if (isset($options['e'])) {

    echo export_db_schema();
}

// EOF
