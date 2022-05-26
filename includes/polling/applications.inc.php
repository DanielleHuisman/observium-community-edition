<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

// This is populated by UNIX-Agent module. Don't reset it! :D
//$valid_applications = [];

foreach (dbFetchRows("SELECT * FROM `applications` WHERE `device_id` = ?", array($device['device_id'])) as $entry) {
  $valid_applications[$entry['app_type']] = $entry;
}

if (safe_count($valid_applications)) {
  print_cli_data_field("Applications", 2);
  foreach ($valid_applications as $app_type => $entry) {
    echo $app_type . ' ';

    // One include per application type. Multiple instances currently handled within the application code
    $app_include = $config['install_dir'].'/includes/polling/applications/'.$app_type.'.inc.php';
    if (is_file($app_include)) {
      include($app_include);
    } else {
      echo($app_type.' include missing! ');
    }

  }
  echo(PHP_EOL);
}

$app_rows = dbFetchRows("SELECT * FROM `applications` WHERE `device_id` = ? AND `app_lastpolled` < ?", array($device['device_id'], time()-604800));
foreach ($app_rows as $app)
{
  dbDelete('applications', '`app_id` = ?', array($app['app_id']));
  echo '-';
}


// EOF
