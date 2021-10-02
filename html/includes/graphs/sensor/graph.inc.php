<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

/// FIXME. To unify all sensor graphs.
$include = $config['html_dir'] . "/includes/graphs/$type/";
switch ($sensor['sensor_class']) {
  case 'humidity':
  case 'capacity':
  case 'load':
  case 'progress':
    include($include."percent.inc.php");
    break;
  case 'snr':
  case 'attenuation':
  case 'sound':
    include($include."db.inc.php");
    break;
  default:
    if (is_file($include.$sensor['sensor_class'].".inc.php")) {
      include($include.$sensor['sensor_class'].".inc.php");
    } else {
      graph_error($type.'_'.$subtype); // Graph Template Missing;
    }
}
unset($include);

// EOF
