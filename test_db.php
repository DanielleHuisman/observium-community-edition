#!/usr/bin/env php
<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     tests
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
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
