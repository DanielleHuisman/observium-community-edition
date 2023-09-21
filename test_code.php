#!/usr/bin/env php
<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     syslog
 * @author         Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// This file allows you to test code using observium's libraries. It's a quick diagnostic tool.
// You probably don't need to use it.


chdir(dirname($argv[0]));
$scriptname = basename($argv[0]);

include_once("includes/observium.inc.php");
include_once("html/includes/functions.inc.php");

include($argv[1]);


// EOF
