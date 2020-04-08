<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

// Discover an entity by populating/updating a database table and returning an id

function discover_entity($device_id, $entity_type, $data)
{

    if (is_array($GLOBALS['config']['entities'][$entity_type])) {

        $def = $GLOBALS['config']['entities'][$entity_type];
        $index = $data[$def['table_fields']['index']];
        $params = $def['params'];

        if (isset($params['table_fields']['index']) && is_array())
        {



        } elseif (isset($params['table_fields']['index'])) {

        } else {

        }



        if (is_array($GLOBALS['cache'][$def['table']][$index])) {
            echo 'Exists';

            $db = $GLOBALS['cache'][$def['table']][$index];
            $id = $db[$def['table_fields']['id']];

            echo 'exists:'.$id.PHP_EOL;

            $update = array();
            foreach ($params as $param)
            {
                if ($data[$param] != $db[$param]) { $update[$param] = $data[$param]; }
            }
            if (count($update))
            {
                dbUpdate($update, $def['table'], '`'.$def['table_fields']['id'].'` = ?', array($id));
                echo('U');
            } else {
                echo('.');
            }

        } else {

            echo 'Doesnt Exist';

            $insert = array();
            $insert['device_id'] = $device_id;
            foreach ($params as $param)
            {
                $insert[$param] = $data[$param];
                if ($data[$param] == NULL) { $insert[$param] = array('NULL'); }
            }
            $id = dbInsert($insert, $def['table']);
            echo("+");

            $params[$def['table_fields']['id']] = $id;

            // Populate cache with this entry. Maybe we need it.
            $GLOBALS['cache'][$def['table']][$index] = $params;

        }

    } else {

        print_error("Entity Type does not exist. This is a relatively serious error.");

        return FALSE;

    }

    return $id;

}


// Discover WIFI Access Point. Returns ap_id.

function discover_wifi_ap($device_id, $ap)
{

    $params = array('ap_index', 'ap_number', 'ap_name', 'ap_serial', 'ap_model', 'ap_location', 'ap_fingerprint', 'ap_status');

    if (is_array($GLOBALS['cache']['wifi_aps'][$ap['ap_index']]))
    {
        // Database entry exists. Lets update it!

        $ap_db = $GLOBALS['cache']['wifi_aps'][$ap['ap_index']];
        $ap_id = $ap_db['wifi_ap_id'];

        echo 'exists:'.$ap_id.PHP_EOL;

        $update = array();
        foreach ($params as $param)
        {
            if ($ap[$param] != $ap_db[$param]) { $update[$param] = $ap[$param]; }
        }
        if (count($update))
        {
            dbUpdate($update, 'wifi_aps', '`wifi_ap_id` = ?', array($ap_db['wifi_ap_id']));
            echo('U');
        } else {
            echo('.');
        }

    } else {

        // Database entry doesn't exist. Lets create it!

        $insert = array();
        $insert['device_id'] = $device_id;
        foreach ($params as $param)
        {
            $insert[$param] = $ap[$param];
            if ($ap[$param] == NULL) { $insert[$param] = array('NULL'); }
        }
        $ap_id = dbInsert($insert, 'wifi_aps');
        echo("+");

        $params['ap_wifi_id'] = $ap_id;

        // Populate cache with this entry. Maybe we need it.
        $GLOBALS['cache']['wifi_aps'][$ap['ap_index']] = $params;

    }

    return $ap_id;

}


function discover_wifi_wlan($device_id, $wlan)
{

  $params = array('wlan_admin_status', 'wlan_beacon_period', 'wlan_bssid', 'wlan_bss_type', 'wlan_channel', 'wlan_dtim_period', 'wlan_frag_thresh',
                  'wlan_index', 'wlan_igmp_snoop', 'wlan_name', 'wlan_prot_mode', 'wlan_radio_mode', 'wlan_rts_thresh',
                  'wlan_ssid', 'wlan_ssid_bcast', 'wlan_vlan_id');

  if (is_array($GLOBALS['cache']['wifi_wlans'][$wlan['wlan_index']]))
  {
    // Database entry exists. Lets update it!
    $wlan_db = $GLOBALS['cache']['wifi_wlans'][$wlan['wlan_index']];
    $wlan_id = $wlan_db['wlan_id'];

    $update = array();
    foreach ($params as $param)
    {
      if ($wlan[$param] != $wlan_db[$param]) { $update[$param] = $wlan[$param]; }
    }
    if (count($update))
    {
      dbUpdate($update, 'wifi_wlans', '`wlan_id` = ?', array($wlan_db['wlan_id']));
      echo('U');
    } else {
      echo('.');
    }

  } else {
    // Database entry doesn't exist. Lets create it!

    $insert = array();
    $insert['device_id'] = $device_id;
    foreach ($params as $param)
    {
      $insert[$param] = $wlan[$param];
      if (is_null($wlan[$param])) { $insert[$param] = array('NULL'); }
    }
    $wlan_id = dbInsert($insert, 'wifi_wlans');
    echo("+");

  }

  return $wlan_id;

}

function discover_wifi_radio($device_id, $radio)
{
  $params  = array('radio_ap', 'radio_mib','radio_number', 'radio_util', 'radio_type', 'radio_status', 'radio_clients', 'radio_txpower', 'radio_channel', 'radio_mac', 'radio_protection', 'radio_bsstype');

  if (is_array($GLOBALS['cache']['wifi_radios'][$radio['radio_ap']][$radio['radio_number']])) { $radio_db = $GLOBALS['cache']['wifi_radios'][$radio['radio_ap']][$radio['radio_number']]; }

  if (!isset($radio_db['wifi_radio_id']))
  {
    $insert = array();
    $insert['device_id'] = $device_id;
    foreach ($params as $param)
    {
      $insert[$param] = $radio[$param];
      if (is_null($radio[$param])) { $insert[$param] = array('NULL'); }
    }
    $wifi_radio_id = dbInsert($insert, 'wifi_radios');
    echo("+");
  } else {
    $update = array();
    foreach ($params as $param)
    {
      if ($radio[$param] != $radio_db[$param]) { $update[$param] = $radio[$param]; }
    }
    if (count($update))
    {
      dbUpdate($update, 'wifi_radios', '`wifi_radio_id` = ?', array($radio_db['wifi_radio_id']));
      echo('U');
    } else {
      echo('.');
    }
  }

  $GLOBALS['valid']['wifi']['radio'][$radio['radio_mib']][$wifi_radio_id] = 1; // FIXME. What? How it passed there?
}

// EOF
