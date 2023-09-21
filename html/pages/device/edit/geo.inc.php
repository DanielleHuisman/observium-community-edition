<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

if ($vars['editing']) {
    if ($readonly) {
        print_error_permission('You have insufficient permissions to edit settings.');
    } else {
        $updated = 0;

        if ($vars['submit'] === 'save') {
            if (get_var_true($vars['reset_geolocation'])) {
                $updated = dbDelete('devices_locations', '`device_id` = ?', [ $device['device_id'] ]);
            } elseif ((bool)$vars['location_manual']) {
                // Set manual coordinates if present
                if (preg_match(OBS_PATTERN_LATLON, $vars['coordinates'], $matches) ||
                    preg_match(OBS_PATTERN_LATLON_ALT, $vars['coordinates'], $matches)) {
                    //r($matches);
                    if ($matches['lat'] >= -90 && $matches['lat'] <= 90 &&
                        $matches['lon'] >= -180 && $matches['lon'] <= 180) {
                        $update_geo['location_lat']     = $matches['lat'];
                        $update_geo['location_lon']     = $matches['lon'];
                        $update_geo['location_country'] = '';
                        $update_geo['location_manual']  = 1;
                        $updated++;
                    }
                }
                if (!$updated) {
                    unset($vars);
                } // If manual set, but coordinates wrong - reset edit
                //r($vars);
            }

            if ((bool)$device['location_manual'] && !(bool)$vars['location_manual']) {
                // Reset manual flag, rediscover geo info
                $update_geo['location_lat']    = ['NULL'];
                $update_geo['location_lon']    = ['NULL'];
                $update_geo['location_manual'] = 0;
                $updated++;
            }

            if ($updated) {
                //r($update_geo);
                if (!safe_empty($update_geo)) {
                    dbUpdate($update_geo, 'devices_locations', '`location_id` = ?', [$device['location_id']]);
                }
                $geo_db = dbFetchRow("SELECT * FROM `devices_locations` WHERE `device_id` = ?", [$device['device_id']]);
                if (safe_count($geo_db)) {
                    if (get_var_true($vars['reset_geolocation'])) {
                        print_warning("Device Geo location dropped. Country/city will be updated on next poll.");
                    } else {
                        print_success("Device Geolocation updated. Country/city will be updated on next poll.");
                    }
                }
                $device = array_merge($device, (array)$geo_db);
                unset($updated, $update_geo, $geo_db);
            } else {
                print_warning("Some input data wrong. Device Geolocation not changed.");
            }
        }
    }
}

$location = ['location_text' => $device['location']];

$override_sysLocation_bool = get_dev_attrib($device, 'override_sysLocation_bool');
if ($override_sysLocation_bool) {
    $override_sysLocation_string = get_dev_attrib($device, 'override_sysLocation_string');
    if ($override_sysLocation_string != $device['location']) {
        // Device not polled since location override
        $location['location_help'] = 'NOTE, device not polled since location overridden, Geolocation is old.';
        $location['location_text'] = $override_sysLocation_string;
    }
}

if (safe_empty($location['location_text'])) {
    $location['location_text'] = OBS_VAR_UNSET;
}
foreach (['location_lat', 'location_lon', 'location_city', 'location_county', 'location_state', 'location_country',
          'location_geoapi', 'location_status', 'location_manual', 'location_updated'] as $param) {
    $location[$param] = $device[$param];
}
if (is_numeric($location['location_lat']) && is_numeric($location['location_lon'])) {
    // Generate link to Google maps
    // http://maps.google.com/maps?q=46.090271,6.657248+description+(name)
    $location['coordinates']        = $location['location_lat'] . ',' . $location['location_lon'];
    $location['coordinates_manual'] = $location['coordinates'];
    $location['location_link']      = '<a target="_blank" href="https://maps.google.com/maps?q=' . urlencode($location['coordinates']) . '"><i class="' . $config['icon']['map'] . '"></i> View this location on a map</a>';
    $location['location_geo']       = country_from_code($location['location_country']) . ' (Country), ' . $location['location_state'] . ' (State), ';
    $location['location_geo']       .= $location['location_county'] . ' (County), ' . $location['location_city'] . ' (City)';
    switch ($location['location_geoapi']) {
        //case 'yandex':
        //  // Generate link to Yandex maps
        //  $location['location_link'] = '<a target="_blank" href="http://maps.google.com/maps?q='.urlencode($location['coordinates']).'"><i class="oicon-map"></i> View this location on a map</a>';
        //  break;
        default:
            // Generate link to Google maps
            // http://maps.google.com/maps?q=46.090271,6.657248+description+(name)
            $location['location_link'] = '<a target="_blank" href="http://maps.google.com/maps?q=' . urlencode($location['coordinates']) . '"><i class="' . $config['icon']['map'] . '"></i> View this location on a map</a>';
    }
} else {
    $location['coordinates_manual'] = $config['geocoding']['default']['lat'] . ',' . $config['geocoding']['default']['lon'];
}

if ($updated && $update_message) {
    print_message($update_message);
} elseif ($update_message) {
    print_error($update_message);
}

$form = ['type'     => 'horizontal',
         'id'       => 'edit',
         //'space'     => '20px',
         'title'    => 'Geolocation Options',
         //'icon'      => 'oicon-gear',
         //'class'     => 'box box-solid',
         'fieldset' => ['edit' => ''],
];

$form['row'][0]['editing']     = [
  'type'  => 'hidden',
  'value' => 'yes'];
$form['row'][1]['sysLocation'] = [
  'type'        => 'text',
  //'fieldset'    => 'edit',
  'name'        => 'sysLocation string',
  'placeholder' => '',
  'width'       => '66.6667%',
  //'readonly'    => $readonly,
  'disabled'    => TRUE, // Always disabled, just for see
  'value'       => $location['location_text']];
if ($location['location_help']) {
    $form['row'][1]['location_help'] = [
      'type'  => 'raw',
      'value' => '<span class="help-block"><small>' . $location['location_help'] . '</small></span>'];
}
$form['row'][2]['location_geo'] = [
  'type'        => 'text',
  //'fieldset'    => 'edit',
  'name'        => 'Location Place',
  'placeholder' => '',
  'width'       => '66.6667%',
  //'readonly'    => $readonly,
  'disabled'    => TRUE, // Always disabled, just for see
  'value'       => $location['location_geo']];
$form['row'][3]['location_lat'] = [
  'type'        => 'text',
  //'fieldset'    => 'edit',
  'name'        => 'Latitude/Longitude',
  'placeholder' => '',
  'width'       => '16.6667%',
  //'readonly'    => $readonly,
  'disabled'    => TRUE, // Always disabled, just for see
  'value'       => ($location['location_lat'] ? $location['location_lat'] . ',' . $location['location_lon'] : '')];
if ($location['location_link']) {
    $form['row'][3]['location_link'] = [
      'type'  => 'raw',
      'value' => '<span class="help-block"><small>' . $location['location_link'] . '</small></span>'];
}
$form['row'][4]['location_geoapi']  = [
  'type'        => 'text',
  //'fieldset'    => 'edit',
  'name'        => 'API used',
  'placeholder' => '',
  'width'       => '16.6667%',
  //'readonly'    => $readonly,
  'disabled'    => TRUE, // Always disabled, just for see
  'value'       => strtoupper($location['location_geoapi'])];
$form['row'][4]['help_link']        = [
  'type'  => 'raw',
  'value' => '<span class="help-inline"><small><a target="_blank" href="' . OBSERVIUM_DOCS_URL . '/config_options/#syslocation-configuration">
      <i class="' . $config['icon']['question'] . '"></i> View available Geolocation APIs and other configuration options</a></small></span>'];
$form['row'][5]['location_updated'] = [
  'type'        => 'text',
  //'fieldset'    => 'edit',
  'name'        => 'Last updated',
  'placeholder' => '',
  'width'       => '16.6667%',
  //'readonly'    => $readonly,
  'disabled'    => TRUE, // Always disabled, just for see
  'value'       => $location['location_updated']];
$form['row'][6]['location_status']  = [
  'type'        => 'textarea',
  //'fieldset'    => 'edit',
  'name'        => 'Last update status',
  'placeholder' => '',
  'width'       => '66.6667%',
  //'readonly'    => $readonly,
  'disabled'    => TRUE, // Always disabled, just for see
  'value'       => $location['location_status']];
$form['row'][7]['coordinates']      = [
  'type'        => 'text',
  //'fieldset'    => 'edit',
  'name'        => 'Manual coordinates',
  'placeholder' => '',
  'width'       => '16.6667%',
  'readonly'    => $readonly,
  'disabled'    => !$location['location_manual'],
  'value'       => $location['coordinates_manual']];
$form['row'][7]['location_manual']  = [
  'type'     => 'toggle',
  'size'     => 'large',
  'readonly' => $readonly,
  'onchange' => "toggleAttrib('disabled', 'coordinates')",
  'value'    => $location['location_manual']];

$form['row'][8]['reset_geolocation'] = [
  'type'      => 'switch-ng',
  'name'      => 'Reset GEO location',
  //'fieldset'    => 'edit',
  'size'      => 'small',
  'readonly'  => $readonly,
  'on-color'  => 'danger',
  'off-color' => 'primary',
  'on-text'   => 'Reset',
  'off-text'  => 'Keep',
  'value'     => 0];

$form['row'][9]['submit'] = [
  'type'     => 'submit',
  'name'     => 'Save Changes',
  'icon'     => 'icon-ok icon-white',
  //'right'       => TRUE,
  'class'    => 'btn-primary',
  'readonly' => $readonly,
  'value'    => 'save'];

print_form($form);
unset($form);

// EOF
