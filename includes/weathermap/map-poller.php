#!/usr/bin/env php
<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @author     Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

chdir(dirname($argv[0]));

// Get options before definitions!
$options = getopt("d");

if (is_file("../../includes/observium.inc.php")) {
	include("../../includes/observium.inc.php");
} else {
	include("../../includes/defaults.inc.php");
	include("../../config.php");
	include("../../includes/functions.inc.php");
	include("../../includes/definitions.inc.php");
}

include("../../includes/polling/functions.inc.php");

$cli = TRUE;

$conf_dir = $config['install_dir'].'/html/weathermap/configs/';

$config['weathermap_url'] = "/weathermap/";

print_debug("Configuration Directory: " . $conf_dir);

if(is_dir($conf_dir))
{
  $files = glob($conf_dir."*.conf");
  foreach($files as $file)
  {
    $cmd = "php ./weathermap --config $file --base-href ". $config['weathermap_url'] .($config['rrdcached'] ? ' --daemon' . $config['rrdcached'] : '');
    echo $cmd;
    echo exec($cmd);
  }
} else {
  print_error("Configuration directory doesn't exist! (".$conf_dir.")");
}

?>
