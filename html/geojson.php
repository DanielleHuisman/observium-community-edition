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
include($config['html_dir'] . "/includes/cache-data.inc.php");

if ($_SERVER['REMOTE_ADDR'] != $_SERVER['SERVER_ADDR']) { if (!$_SESSION['authenticated']) { echo("unauthenticated"); exit; } }

$vars = get_vars('GET');

$geo = array();

foreach ($GLOBALS['cache']['devices']['id'] as $device)
    {
      if (!$config['web_show_disabled'] && $device["disabled"]) { continue; }
      $lat = (is_numeric($device['location_lat']) ? $device['location_lat'] : $config['geocoding']['default']['lat']);
      $lon = (is_numeric($device['location_lon']) ? $device['location_lon'] : $config['geocoding']['default']['lon']);
      if ($device["status"] == "0")
      {
        if ($device["ignore"] == "0")
        {
          $locations[$lat][$lon]["down_hosts"][] = $device;
        }
      } else {
        $locations[$lat][$lon]["up_hosts"][] = $device;
      }
    }

    foreach ($locations as $la => $lat)
    {
      foreach ($lat as $lo => $lon)
      {
        $tooltip = "";
        $num_up = count($lon["up_hosts"]);
        $num_down = count($lon["down_hosts"]);
        $total_hosts = $num_up + $num_down;
        $tooltip = '<p><span class="label label-success">Up '.$num_up.'</span> <span class="label label-error">Down '.$num_down.'</span></p>';
        $state = unknown;
        $location_name = "";
        if ($num_down > 0)
        {
          $state = 'down';
          $location_name = ($lon['down_hosts'][0]['location'] === '' ? OBS_VAR_UNSET : $lon['down_hosts'][0]['location']);
          $location_url  = generate_location_url($lon['down_hosts'][0]['location']);
        }
        elseif ($num_up > 0)
        {
          $state = 'up';
          $location_name = ($lon['up_hosts'][0]['location'] === '' ? OBS_VAR_UNSET : $lon['up_hosts'][0]['location']);
          $location_url  = generate_location_url($lon['up_hosts'][0]['location']);
        }

        $tooltip = "<h3>".$location_name."</h3><hr />".$tooltip;
        foreach ($lon["down_hosts"] as $down_host) {
          $tooltip .= '<span class="label label-error">' . escape_html($down_host['hostname']) .'</span> ';
        }

        $feature = array('geometry' => array('type' => 'Point',
                                             'coordinates' => array((float)$lo, (float)$la)),
                         'type' => 'Feature',
                         'properties' => array('name' => $location_name,
                                               'state' => $state,
                                               'id' => safename($location_name),
                                               'popupContent' => $tooltip,
                                               'url' => $location_url));
        $features[] = $feature;

        //echo "[$la, $lo, $num_up, $num_down, \"$tooltip\", '$location_name', '$location_url'],\n      ";

      }
    }

$geo = array('type' => 'FeatureCollection', 'features' => $features);

header('Content-type: application/javascript');
//echo 'var geojson = ' . json_encode($geo) . ';';

//print_r($features);

echo json_encode($geo);

//r($geo);
