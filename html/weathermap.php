<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage billing
 * @author     Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

ini_set('allow_url_fopen', 0);

include_once("../includes/sql-config.inc.php");

include($config['html_dir'] . "/includes/functions.inc.php");
include($config['html_dir'] . "/includes/authenticate.inc.php");

if ($_SERVER['REMOTE_ADDR'] != $_SERVER['SERVER_ADDR']) { if (!$_SESSION['authenticated']) { echo("unauthenticated"); exit; } }

$vars = get_vars('GET');

if($_SESSION['userlevel'] > 7)
{
  include($config['install_dir']."/includes/weathermap/editor.php");
} else {
  echo("Unauthorised Access Prohibited.");
  exit;
}



