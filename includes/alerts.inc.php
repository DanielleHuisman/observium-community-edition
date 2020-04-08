<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2015, Adam Armstrong - http://www.observium.org
 *
 * @package    observium
 * @subpackage alerter
 * @author     Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_contact_by_id($contact_id)
{
    if (is_numeric($contact_id)) {
        $contact = dbFetchRow('SELECT * FROM `alert_contacts` WHERE `contact_id` = ?', array($contact_id));
    }
    if (is_array($contact) && count($contact)) {
        return $contact;
    } else {
        return FALSE;
    }
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_alert_test_by_id($alert_test_id)
{
    if (is_numeric($alert_test_id)) {
        $alert_test = dbFetchRow('SELECT * FROM `alert_tests` WHERE `alert_test_id` = ?', array($alert_test_id));
    }
    if (is_array($alert_test) && count($alert_test)) {
        return $alert_test;
    } else {
        return FALSE;
    }
}

/**
 * Check an entity against all relevant alerts
 *
 * @param string type
 * @param array entity
 * @param array data
 * @return NULL
 */
// TESTME needs unit testing
function check_entity($entity_type, $entity, $data)
{
    global $config, $alert_rules, $alert_table, $device;

    //r(array($entity_type, $entity, $data));

    $alert_output = "";

    $entity_data = entity_type_translate_array($entity_type);
    $entity_id_field     = $entity_data['id_field'];
    $entity_ignore_field = $entity_data['ignore_field'];

    $entity_id = $entity[$entity_id_field];

    // Hardcode time and weekday for global use.
    $data['time']      = date('Hi');
    $data['weekday']   = date('N');

    if (!isset($alert_table[$entity_type][$entity_id])) {
        return;
    } // Just return to avoid PHP warnings

    $alert_info = array('entity_type' => $entity_type, 'entity_id' => $entity_id);

    foreach ($alert_table[$entity_type][$entity_id] as $alert_test_id => $alert_args) {
        if ($alert_rules[$alert_test_id]['and']) {
            $alert = TRUE;
        } else {
            $alert = FALSE;
        }

        $alert_info['alert_test_id'] = $alert_test_id;

        $alert_checker = $alert_rules[$alert_test_id];

        $update_array = array();

        if (is_array($alert_rules[$alert_test_id])) {
            //echo("Checking alert ".$alert_test_id." associated by ".$alert_args['alert_assocs']."\n");
            $alert_output .= $alert_rules[$alert_test_id]['alert_name'] . " [";

            foreach ($alert_rules[$alert_test_id]['conditions'] as $test_key => $test) {
                if (substr($test['value'], 0, 1) == "@") {
                    $ent_val = substr($test['value'], 1);
                    $test['value'] = $entity[$ent_val];
                    //echo(" replaced @".$ent_val." with ". $test['value'] ." from entity. ");
                }

                //echo("Testing: " . $test['metric']. " ". $test['condition'] . " " .$test['value']);
                $update_array['state']['metrics'][$test['metric']] = $data[$test['metric']];

                if (isset($data[$test['metric']])) {
                    //echo(" (value: ".$data[$test['metric']].")");
                    if (test_condition($data[$test['metric']], $test['condition'], $test['value'])) {
                        // A test has failed. Set the alert variable and make a note of what failed.
                        //print_cli("%R[FAIL]%N");
                        $update_array['state']['failed'][] = $test;

                        if ($alert_rules[$alert_test_id]['and']) {
                            $alert = ($alert && TRUE);
                        } else {
                            $alert = ($alert || TRUE);
                        }
                    } else {
                        if ($alert_rules[$alert_test_id]['and']) {
                            $alert = ($alert && FALSE);
                        } else {
                            $alert = ($alert || FALSE);
                        }
                        //print_cli("%G[OK]%N");
                    }
                } else {
                    //echo("  Metric is not present on entity.\n");
                    if ($alert_rules[$alert_test_id]['and']) {
                        $alert = ($alert && FALSE);
                    } else {
                        $alert = ($alert || FALSE);
                    }
                }
            }

            if ($alert) {
                // Check to see if this alert has been suppressed by anything
                ## FIXME -- not all of this is implemented

                // Have all alerts been suppressed?
                if ($config['alerts']['suppress']) {
                    $alert_suppressed = TRUE;
                    $suppressed[] = "GLOBAL";
                }

                // Is there a global scheduled maintenance?
                if (isset($GLOBALS['cache']['maint']['global']) && count($GLOBALS['cache']['maint']['global']) > 0) {
                    $alert_suppressed = TRUE;
                    $suppressed[] = "MNT_GBL";
                }

                // Have all alerts on the device been suppressed?
                if ($device['ignore']) {
                    $alert_suppressed = TRUE;
                    $suppressed[] = "DEV";
                }
                if ($device['ignore_until']) {
                    $device['ignore_until_time'] = strtotime($device['ignore_until']);
                    if ($device['ignore_until_time'] > time()) {
                        $alert_suppressed = TRUE;
                        $suppressed[] = "DEV_U";
                    }
                }

                if (isset($GLOBALS['cache']['maint'][$entity_type][$entity[$entity_id_field]])) {
                    $alert_suppressed = TRUE;
                    $suppressed[] = "MNT_ENT";
                }

                if (isset($GLOBALS['cache']['maint']['alert_checker'][$alert_test_id])) {
                    $alert_suppressed = TRUE;
                    $suppressed[] = "MNT_CHK";
                }

                if (isset($GLOBALS['cache']['maint']['device'][$device['device_id']])) {
                    $alert_suppressed = TRUE;
                    $suppressed[] = "MNT_DEV";
                }

                // Have all alerts on the entity been suppressed?
                if ($entity[$entity_ignore_field]) {
                    $alert_suppressed = TRUE;
                    $suppressed[] = "ENT";
                }
                if (is_numeric($entity['ignore_until']) && $entity['ignore_until'] > time()) {
                    $alert_suppressed = TRUE;
                    $suppressed[] = "ENT_U";
                }

                // Have alerts from this alerter been suppressed?
                if ($alert_rules[$alert_test_id]['ignore']) {
                    $alert_suppressed = TRUE;
                    $suppressed[] = "CHECK";
                }
                if ($alert_rules[$alert_test_id]['ignore_until']) {
                    $alert_rules[$alert_test_id]['ignore_until_time'] = strtotime($alert_rules[$alert_test_id]['ignore_until']);
                    if ($alert_rules[$alert_test_id]['ignore_until_time'] > time()) {
                        $alert_suppressed = TRUE;
                        $suppressed[] = "CHECK_UNTIL";
                    }
                }

                // Has this specific alert been suppressed?
                if ($alert_args['ignore']) {
                    $alert_suppressed = TRUE;
                    $suppressed[] = "ENTRY";
                }
                if ($alert_args['ignore_until']) {
                    $alert_args['ignore_until_time'] = strtotime($alert_args['ignore_until']);
                    if ($alert_args['ignore_until_time'] > time()) {
                        $alert_suppressed = TRUE;
                        $suppressed[] = "ENTRY_UNTIL";
                    }
                }

                if (is_numeric($alert_args['ignore_until_ok']) && $alert_args['ignore_until_ok'] == '1') {
                    $alert_suppressed = TRUE;
                    $suppressed[] = "ENTRY_UNTIL_OK";
                }

                $update_array['count'] = $alert_args['count'] + 1;

                // Check against the alert test's delay
                if ($alert_args['count'] >= $alert_rules[$alert_test_id]['delay'] && $alert_suppressed) {
                    // This alert is valid, but has been suppressed.
                    //echo(" Checks failed. Alert suppressed (".implode(', ', $suppressed).").\n");
                    $alert_output .= "%PFS%N";
                    $update_array['alert_status'] = '3';
                    $update_array['last_message'] = 'Checks failed (Suppressed: ' . implode(', ', $suppressed) . ')';
                    $update_array['last_checked'] = time();
                    if ($alert_args['alert_status'] != '3' || $alert_args['last_changed'] == '0') {
                        $update_array['last_changed'] = time();
                        $log_id = log_alert('Checks failed but alert suppressed by [' . implode($suppressed, ',') . ']', $device, $alert_info, 'FAIL_SUPPRESSED');
                    }
                    $update_array['last_failed'] = time();
                } elseif ($alert_args['count'] >= $alert_rules[$alert_test_id]['delay']) {
                    // This is a real alert.
                    //echo(" Checks failed. Generate alert.\n");
                    $alert_output .= "%PF!%N";
                    $update_array['alert_status'] = '0';
                    $update_array['last_message'] = 'Checks failed';
                    $update_array['last_checked'] = time();
                    if ($alert_args['alert_status'] != '0' || $alert_args['last_changed'] == '0') {
                        $update_array['last_changed'] = time();
                        $update_array['last_alerted'] = '0';
                        $log_id = log_alert('Checks failed', $device, $alert_info, 'FAIL');
                    }
                    $update_array['last_failed'] = time();
                } else {
                    // This is alert needs to exist for longer.
                    //echo(" Checks failed. Delaying alert.\n");
                    $alert_output .= "%OFD%N";
                    $update_array['alert_status'] = '2';
                    $update_array['last_message'] = 'Checks failed (delayed)';
                    $update_array['last_checked'] = time();
                    if ($alert_args['alert_status'] != '2' || $alert_args['last_changed'] == '0') {
                        $update_array['last_changed'] = time();
                        $log_id = log_alert('Checks failed but alert delayed', $device, $alert_info, 'FAIL_DELAYED');
                    }
                    $update_array['last_failed'] = time();
                }
            } else {
                $update_array['count'] = 0;
                // Alert conditions passed. Record that we tested it and update status and other data.
                $alert_output .= "%gOK%N";
                $update_array['alert_status'] = '1';
                $update_array['last_message'] = 'Checks OK';
                $update_array['last_checked'] = time();
                #$update_array['count'] = 0;
                if ($alert_args['alert_status'] != '1' || $alert_args['last_changed'] == '0') {
                    $update_array['last_changed'] = time();
                    $log_id = log_alert('Checks succeeded', $device, $alert_info, 'OK');
                }
                $update_array['last_ok'] = time();

                // Status is OK, so disable ignore_until_ok if it has been enabled
                if ($alert_args['ignore_until_ok'] != '0') {
                    $update_entry_array['ignore_until_ok'] = '0';
                }
            }

            unset($suppressed);
            unset($alert_suppressed);

            // json_encode the state array before we put it into MySQL.
            $update_array['state'] = json_encode($update_array['state']);
            #$update_array['alert_table_id'] = $alert_args['alert_table_id'];

            /// Perhaps this is better done with SQL replace?
            #print_vars($alert_args);
            //if (!$alert_args['state_entry'])
            //{
            // State entry seems to be missing. Insert it before we update it.
            //dbInsert(array('alert_table_id' => $alert_args['alert_table_id']), 'alert_table-state');
            // echo("I+");
            //}
            dbUpdate($update_array, 'alert_table', '`alert_table_id` = ?', array($alert_args['alert_table_id']));
            if (is_array($update_entry_array)) {
                dbUpdate($update_entry_array, 'alert_table', '`alert_table_id` = ?', array($alert_args['alert_table_id']));
            }

            if (TRUE) {
                // Write RRD data
                if ($update_array['alert_status'] == "1") {
                    // Status is up
                    rrdtool_update_ng($device, 'alert', array('status' => 1, 'code' => $update_array['alert_status']), $alert_args['alert_table_id']);
                } else {
                    rrdtool_update_ng($device, 'alert', array('status' => 0, 'code' => $update_array['alert_status']), $alert_args['alert_table_id']);
                }
            }
        } else {
            $alert_output .= "%RAlert missing!%N";
        }

        $alert_output .= ("] ");
    }

    $alert_output .= "%n";

    if ($entity_type == "device") {
        $cli_level = 1;
    } else {
        $cli_level = 3;
    }

    //print_cli_data("Checked Alerts", $alert_output, $cli_level);
}

/**
 * Build an array of conditions that apply to a supplied device
 *
 * This takes the array of global conditions and removes associations that don't match the supplied device array
 *
 * @param  array device
 * @return array
 */
// TESTME needs unit testing
function cache_device_conditions($device)
{
    // Return no conditions if the device is ignored or disabled.

    if ($device['ignore'] == 1 || $device['disabled'] == 1) {
        return array();
    }

    $conditions = cache_conditions();

    foreach ($conditions['assoc'] as $assoc_key => $assoc) {
        if (match_device($device, $assoc['device_attribs'])) {
            $assoc['alert_test_id'];
            $conditions['cond'][$assoc['alert_test_id']]['assoc'][$assoc_key] = $conditions['assoc'][$assoc_key];
            $cond_new['cond'][$assoc['alert_test_id']] = $conditions['cond'][$assoc['alert_test_id']];
        } else {
            unset($conditions['assoc'][$assoc_key]);
        }
    }

    return $cond_new;
}

/**
 * Fetch array of alerts to a supplied device from `alert_table`
 *
 * This takes device_id as argument and returns an array.
 *
 * @param device_id
 * @return array
 */
// TESTME needs unit testing
function cache_device_alert_table($device_id)
{
    $alert_table = array();

    $sql = "SELECT * FROM  `alert_table`";
    //$sql .= " LEFT JOIN `alert_table-state` USING(`alert_table_id`)";
    $sql .= " WHERE `device_id` = ?";

    foreach (dbFetchRows($sql, array($device_id)) as $entry) {
        $alert_table[$entry['entity_type']][$entry['entity_id']][$entry['alert_test_id']] = $entry;
    }

    return $alert_table;
}

// Wrapper function to loop all groups and trigger rebuild for each.

function update_alert_tables($silent = TRUE)
{

   $alerts = cache_alert_rules();
   $assocs   = cache_alert_assoc();

   // Populate associations table into alerts array for legacy association styles
   foreach($assocs as $assoc)
   {
      $alerts[$assoc['alert_test_id']]['assocs'][] = $assoc;
   }

   foreach($alerts AS $alert)
   {
      update_alert_table($alert, $silent);
   }

}

// Regenerate Alert Table
function update_alert_table($alert, $silent = TRUE)
{

   if(is_numeric($alert)) // We got an alert_test_id, fetch the array.
   {
      $alert = dbFetchRow("SELECT * FROM `alert_tests` WHERE `alert_test_id` = ?", array($alert));
   }

   if(strlen($alert['alert_assoc']))
   {

      $query = parse_qb_ruleset($alert['entity_type'], json_decode($alert['alert_assoc'], TRUE));
      $data  = dbFetchRows($query);
      $error = dbError();
      $entities = array();

      $field = $GLOBALS['config']['entities'][$alert['entity_type']]['table_fields']['id'];

      foreach($data as $datum)
      {
         $entities[$datum[$field]] = array('entity_id' => $datum[$field], 'device_id' => $datum['device_id']);
      }

   } else {
      $entities = get_alert_entities_from_assocs($alert);
   }

   $field = $config['entities'][$alert['entity_type']]['table_fields']['id'];

   $existing_entities = get_alert_entities($alert['alert_test_id']);

   //r($existing_entities);
   //r($entities);


   $add = array_diff_key($entities, $existing_entities);
   $remove = array_diff_key($existing_entities, $entities);

   if(!silent)
   {
      echo count($existing_entities) . " existing entries.<br />";
      echo count($entities) . " new entries.<br />";
      echo "(+" . count($add) . "/-" . count($del) . ")<br />";
   }

   foreach($add as $entity_id => $entity)
   {
      dbInsert(array('device_id' => $entity['device_id'], 'entity_type' => $alert['entity_type'], 'entity_id' => $entity_id, 'alert_test_id' => $alert['alert_test_id']), 'alert_table');
   }

   foreach($remove as $entity_id => $entity)
   {
      dbDelete('alert_table', 'alert_test_id = ? AND entity_id = ?', array($alert['alert_test_id'], $entity_id));
   }

   //print_vars($add);
   //print_vars($del);

   if(!is_cli() && !$silent)
   {
      print_message("Alert Checker " . $alert['alert_name'] . " regenerated", 'information');
   }

}


/**
 * Build an array of all alert rules
 *
 * @return array
 */
// TESTME needs unit testing
function cache_alert_rules($vars = array())
{
    $alert_rules = array();
    $rules_count = 0;
    $where = 'WHERE 1';
    $args = array();

    if (isset($vars['entity_type']) && $vars['entity_type'] !== "all") {
        $where .= ' AND `entity_type` = ?';
        $args[] = $vars['entity_type'];
    }

    foreach (dbFetchRows("SELECT * FROM `alert_tests` " . $where, $args) as $entry) {
        if ($entry['alerter'] == '') {
            $entry['alerter'] = "default";
        }
        $alert_rules[$entry['alert_test_id']] = $entry;
        $alert_rules[$entry['alert_test_id']]['conditions'] = json_decode($entry['conditions'], TRUE);
        $rules_count++;
    }

    print_debug("Cached $rules_count alert rules.");

    return $alert_rules;

}

// FIXME. Never used, deprecated?
// DOCME needs phpdoc block
// TESTME needs unit testing
function generate_alerter_info($alerter)
{
    global $config;

    if (is_array($config['alerts']['alerter'][$alerter])) {
        $a = $config['alerts']['alerter'][$alerter];
        $output = "<strong>" . $a['descr'] . "</strong><hr />";
        $output .= $a['type'] . ": " . $a['contact'] . "<br />";
        if ($a['enable']) {
            $output .= "Enabled";
        } else {
            $output .= "Disabled";
        }
        return $output;
    } else {
        return "Unknown alerter.";
    }
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function cache_alert_assoc()
{
    $alert_assoc = array();

    foreach (dbFetchRows("SELECT * FROM `alert_assoc`") as $entry) {
        $entity_attribs = json_decode($entry['entity_attribs'], TRUE);
        $device_attribs = json_decode($entry['device_attribs'], TRUE);
        $alert_assoc[$entry['alert_assoc_id']]['entity_type'] = $entry['entity_type'];
        $alert_assoc[$entry['alert_assoc_id']]['entity_attribs'] = $entity_attribs;
        $alert_assoc[$entry['alert_assoc_id']]['device_attribs'] = $device_attribs;
        $alert_assoc[$entry['alert_assoc_id']]['alert_test_id'] = $entry['alert_test_id'];
    }

    return $alert_assoc;
}

/**
 * Build an array of scheduled maintenances
 *
 * @return array
 *
 */
// TESTME needs unit testing
function cache_alert_maintenance()
{

    $return = array();
    $now = time();

    $maints = dbFetchRows("SELECT * FROM `alerts_maint` WHERE `maint_start` < ? AND `maint_end` > ?", array($now, $now));

    if (is_array($maints) && count($maints)) {

        $return['count'] = count($maints);

        foreach ($maints as $maint) {
            if ($maint['maint_global'] == 1) {
                $return['global'][$maint['maint_id']] = $maint;
            } else {

                $assocs = dbFetchRows("SELECT * FROM `alerts_maint_assoc` WHERE `maint_id` = ?", array($maint['maint_id']));

                foreach ($assocs as $assoc) {
                    switch ($assoc['entity_type']) {
                        case "group": // this is a group, so expand it's members into an array
                            $group = get_group_by_id($assoc['entity_id']);
                            $entities = get_group_entities($assoc['entity_id']);
                            foreach ($entities as $entity) {
                                $return[$group['entity_type']][$entity] = TRUE;
                            }
                            break;
                        default:
                            $return[$assoc['entity_type']][$assoc['entity_id']] = TRUE;
                            break;
                    }
                }

            }
        }
    }

    //print_r($return);

    return $return;

}

function get_alert_entities($ids)
{
   $array = array();
   if (!is_array($ids)) { $ids = array($ids); }

   foreach ($ids as $alert_id)
   {
      foreach (dbFetchRows("SELECT * FROM `alert_table` WHERE `alert_test_id` = ?", array($alert_id)) as $entry)
      {
         $array[$entry['entity_id']] = array('entity_id' => $entry['entity_id'], 'device_id' => $entry['device_id']);
      }
   }

   return $array;
}


function get_maintenance_associations($maint_id = NULL)
{
    $return = array();

#  if ($maint_id)
#  {
    $assocs = dbFetchRows("SELECT * FROM `alerts_maint_assoc` WHERE `maint_id` = ?", array($maint_id));
#  } else {
#    $assocs = dbFetchRows("SELECT * FROM `alerts_maint_assoc`");
#  }

    foreach ($assocs as $assoc) {
        $return[$assoc['entity_type']][$assoc['entity_id']] = TRUE;
    }

    return $return;

}

/**
 * Build an array of all conditions
 *
 * @return array
 */
// TESTME needs unit testing
function cache_conditions()
{
    $cache = array();

    foreach (dbFetchRows("SELECT * FROM `alert_tests`") as $entry) {
        $cache['cond'][$entry['alert_test_id']] = $entry;
        $conditions = json_decode($entry['conditions'], TRUE);
        $cache['cond'][$entry['alert_test_id']]['entity_type'] = $entry['entity_type'];
        $cache['cond'][$entry['alert_test_id']]['conditions'] = $conditions;
    }

    foreach (dbFetchRows("SELECT * FROM `alert_assoc`") as $entry) {
        $entity_attribs = json_decode($entry['entity_attribs'], TRUE);
        $device_attribs = json_decode($entry['device_attribs'], TRUE);
        $cache['assoc'][$entry['alert_assoc_id']] = $entry;
        $cache['assoc'][$entry['alert_assoc_id']]['entity_attribs'] = $entity_attribs;
        $cache['assoc'][$entry['alert_assoc_id']]['device_attribs'] = $device_attribs;
    }

    return $cache;
}

/**
 * Compare two values
 *
 * @param string $value_a
 * @param string $condition
 * @param string $value_b
 * @return boolean
 */
// TESTME needs unit testing
function test_condition($value_a, $condition, $value_b)
{
    $value_a = trim($value_a);
    if (!is_array($value_b)) {
        $value_b = trim($value_b);
    }
    $condition = strtolower($condition);
    $delimiters = array('/', '!', '@');

    switch ($condition) {
        case 'ge':
        case '>=':
            if ($value_a >= unit_string_to_numeric($value_b)) {
                $alert = TRUE;
            } else {
                $alert = FALSE;
            }
            break;
        case 'le':
        case '<=':
            if ($value_a <= unit_string_to_numeric($value_b)) {
                $alert = TRUE;
            } else {
                $alert = FALSE;
            }
            break;
        case 'gt':
        case 'greater':
        case '>':
            if ($value_a > unit_string_to_numeric($value_b)) {
                $alert = TRUE;
            } else {
                $alert = FALSE;
            }
            break;
        case 'lt':
        case 'less':
        case '<':
            if ($value_a < unit_string_to_numeric($value_b)) {
                $alert = TRUE;
            } else {
                $alert = FALSE;
            }
            break;
        case 'notequals':
        case 'isnot':
        case 'ne':
        case '!=':
            if ($value_a != unit_string_to_numeric($value_b)) {
                $alert = TRUE;
            } else {
                $alert = FALSE;
            }
            break;
        case 'equals':
        case 'eq':
        case 'is':
        case '==':
        case '=':
            if ($value_a == unit_string_to_numeric($value_b)) {
                $alert = TRUE;
            } else {
                $alert = FALSE;
            }
            break;
        case 'match':
        case 'matches':
            $value_b = str_replace('*', '.*', $value_b);
            $value_b = str_replace('?', '.', $value_b);

            foreach ($delimiters as $delimiter) {
                if (!str_contains($value_b, $delimiter)) {
                    break;
                }
            }
            if (preg_match($delimiter . '^' . $value_b . '$' . $delimiter, $value_a)) {
                $alert = TRUE;
            } else {
                $alert = FALSE;
            }
            break;
        case 'notmatches':
        case 'notmatch':
        case '!match':
            $value_b = str_replace('*', '.*', $value_b);
            $value_b = str_replace('?', '.', $value_b);

            foreach ($delimiters as $delimiter) {
                if (!str_contains($value_b, $delimiter)) {
                    break;
                }
            }
            if (preg_match($delimiter . '^' . $value_b . '$' . $delimiter, $value_a)) {
                $alert = FALSE;
            } else {
                $alert = TRUE;
            }
            break;
        case 'regexp':
        case 'regex':
            foreach ($delimiters as $delimiter) {
                if (!str_contains($value_b, $delimiter)) {
                    break;
                }
            }
            if (preg_match($delimiter . $value_b . $delimiter, $value_a)) {
                $alert = TRUE;
            } else {
                $alert = FALSE;
            }
            break;
        case 'notregexp':
        case 'notregex':
        case '!regexp':
        case '!regex':
            foreach ($delimiters as $delimiter) {
                if (!str_contains($value_b, $delimiter)) {
                    break;
                }
            }
            if (preg_match($delimiter . $value_b . $delimiter, $value_a)) {
                $alert = FALSE;
            } else {
                $alert = TRUE;
            }
            break;
        case 'in':
        case 'list':
            if (!is_array($value_b)) {
                $value_b = explode(',', $value_b);
            }
            $alert = in_array($value_a, $value_b);
            break;
        case '!in':
        case '!list':
        case 'notin':
        case 'notlist':
            if (!is_array($value_b)) {
                $value_b = explode(',', $value_b);
            }
            $alert = !in_array($value_a, $value_b);
            break;
        default:
            $alert = FALSE;
            break;
    }

    return $alert;
}

/**
 * Test if a device matches a set of attributes
 * Matches using the database entry for the supplied device_id
 *
 * @param array device
 * @param array attributes
 * @return boolean
 */
// TESTME needs unit testing
function match_device($device, $attributes, $ignore = TRUE)
{
    // Short circuit this check if the device is either disabled or ignored.
    if ($ignore && ($device['disable'] == 1 || $device['ignore'] == 1)) {
        return FALSE;
    }

    $query = "SELECT COUNT(*) FROM `devices` AS d";
    $join = "";
    $where = " WHERE d.`device_id` = ?";
    $params = array($device['device_id']);

    foreach ($attributes as $attrib) {
        switch ($attrib['condition']) {
            case 'ge':
            case '>=':
                $where .= ' AND d.`' . $attrib['attrib'] . '` >= ?';
                $params[] = $attrib['value'];
                break;
            case 'le':
            case '<=':
                $where .= ' AND d.`' . $attrib['attrib'] . '` <= ?';
                $params[] = $attrib['value'];
                break;
            case 'gt':
            case 'greater':
            case '>':
                $where .= ' AND d.`' . $attrib['attrib'] . '` > ?';
                $params[] = $attrib['value'];
                break;
            case 'lt':
            case 'less':
            case '<':
                $where .= ' AND d.`' . $attrib['attrib'] . '` < ?';
                $params[] = $attrib['value'];
                break;
            case 'notequals':
            case 'isnot':
            case 'ne':
            case '!=':
                $where .= ' AND d.`' . $attrib['attrib'] . '` != ?';
                $params[] = $attrib['value'];
                break;
            case 'equals':
            case 'eq':
            case 'is':
            case '==':
            case '=':
                $where .= ' AND d.`' . $attrib['attrib'] . '` = ?';
                $params[] = $attrib['value'];
                break;
            case 'match':
            case 'matches':
                $attrib['value'] = str_replace('*', '%', $attrib['value']);
                $attrib['value'] = str_replace('?', '_', $attrib['value']);
                $where .= ' AND IFNULL(d.`' . $attrib['attrib'] . '`, "") LIKE ?';
                $params[] = $attrib['value'];
                break;
            case 'notmatches':
            case 'notmatch':
            case '!match':
                $attrib['value'] = str_replace('*', '%', $attrib['value']);
                $attrib['value'] = str_replace('?', '_', $attrib['value']);
                $where .= ' AND IFNULL(d.`' . $attrib['attrib'] . '`, "") NOT LIKE ?';
                $params[] = $attrib['value'];
                break;
            case 'regexp':
            case 'regex':
                $where .= ' AND IFNULL(d.`' . $attrib['attrib'] . '`, "") REGEXP ?';
                $params[] = $attrib['value'];
                break;
            case 'notregexp':
            case 'notregex':
            case '!regexp':
            case '!regex':
                $where .= ' AND IFNULL(d.`' . $attrib['attrib'] . '`, "") NOT REGEXP ?';
                $params[] = $attrib['value'];
                break;
            case 'in':
            case 'list':
                $where .= generate_query_values(explode(',', $attrib['value']), 'd.' . $attrib['attrib']);
                break;
            case '!in':
            case '!list':
            case 'notin':
            case 'notlist':
                $where .= generate_query_values(explode(',', $attrib['value']), 'd.' . $attrib['attrib'], '!=');
                break;
            case 'include':
            case 'includes':
                switch ($attrib['attrib']) {
                    case 'group':
                        $join .= " INNER JOIN `group_table` USING(`device_id`)";
                        $join .= " INNER JOIN `groups`      USING(`group_id`)";
                        $where .= " AND `group_name` = ?";
                        $params[] = $attrib['value'];
                        break;
                    case 'group_id':
                        $join .= " INNER JOIN `group_table` USING(`device_id`)";
                        $where .= " AND `group_id` = ?";
                        $params[] = $attrib['value'];
                        break;

                }
                break;
        }
    }

    $query .= $join . $where;
    $device_count = dbFetchCell($query, $params);

    if ($device_count == 0) {
        return FALSE;
    } else {
        return TRUE;
    }
}

/**
 * Return an array of entities of a certain type which match device_id and entity attribute rules.
 *
 * @param integer device_id
 * @param array attributes
 * @param string entity_type
 * @return array
 */
// TESTME needs unit testing
function match_device_entities($device_id, $entity_attribs, $entity_type)
{
    // FIXME - this is going to be horribly slow.

    $e_type = $entity_type;
    $entity_type = entity_type_translate_array($entity_type);

    if (!is_array($entity_type)) {
        return NULL;
    } // Do nothing if entity type unknown

    $param = array();
    $sql = "SELECT * FROM `" . dbEscape($entity_type['table']) . "`"; // FIXME. Not sure why these required escape table name

    if(isset($entity_type['parent_table']) && isset($entity_type['parent_id_field']))
    {
        $sql .= ' LEFT JOIN `'.$entity_type['parent_table'].'` USING (`'.$entity_type['parent_id_field'].'`)';
    }

    $sql .= " WHERE `" . dbEscape($entity_type['table']) . "`.device_id = ?";

    if (isset($entity_type['where'])) {
        $sql .= ' AND ' . $entity_type['where'];
    }

    $param[] = $device_id;

    if (isset($entity_type['deleted_field'])) {
        $sql .= " AND `" . $entity_type['deleted_field'] . "` != ?";
        $param[] = '1';
    }

    foreach ($entity_attribs as $attrib) {
        switch ($attrib['condition']) {
            case 'ge':
            case '>=':
                $sql .= ' AND `' . $attrib['attrib'] . '` >= ?';
                $param[] = $attrib['value'];
                break;
            case 'le':
            case '<=':
                $sql .= ' AND `' . $attrib['attrib'] . '` <= ?';
                $param[] = $attrib['value'];
                break;
            case 'gt':
            case 'greater':
            case '>':
                $sql .= ' AND `' . $attrib['attrib'] . '` > ?';
                $param[] = $attrib['value'];
                break;
            case 'lt':
            case 'less':
            case '<':
                $sql .= ' AND `' . $attrib['attrib'] . '` < ?';
                $param[] = $attrib['value'];
                break;
            case 'notequals':
            case 'isnot':
            case 'ne':
            case '!=':
                $sql .= ' AND `' . $attrib['attrib'] . '` != ?';
                $param[] = $attrib['value'];
                break;
            case 'equals':
            case 'eq':
            case 'is':
            case '==':
            case '=':
                $sql .= ' AND `' . $attrib['attrib'] . '` = ?';
                $param[] = $attrib['value'];
                break;
            case 'match':
            case 'matches':
                $attrib['value'] = str_replace('*', '%', $attrib['value']);
                $attrib['value'] = str_replace('?', '_', $attrib['value']);
                $sql .= ' AND IFNULL(`' . $attrib['attrib'] . '`, "") LIKE ?';
                $param[] = $attrib['value'];
                break;
            case 'notmatches':
            case 'notmatch':
            case '!match':
                $attrib['value'] = str_replace('*', '%', $attrib['value']);
                $attrib['value'] = str_replace('?', '_', $attrib['value']);
                $sql .= ' AND IFNULL(`' . $attrib['attrib'] . '`, "") NOT LIKE ?';
                $param[] = $attrib['value'];
                break;
            case 'regexp':
            case 'regex':
                $sql .= ' AND IFNULL(`' . $attrib['attrib'] . '`, "") REGEXP ?';
                $param[] = $attrib['value'];
                break;
            case 'notregexp':
            case 'notregex':
            case '!regexp':
            case '!regex':
                $sql .= ' AND IFNULL(`' . $attrib['attrib'] . '`, "") NOT REGEXP ?';
                $param[] = $attrib['value'];
                break;
            case 'in':
            case 'list':
                $sql .= generate_query_values(explode(',', $attrib['value']), $attrib['attrib']);
                break;
            case '!in':
            case '!list':
            case 'notin':
            case 'notlist':
                $sql .= generate_query_values(explode(',', $attrib['value']), $attrib['attrib'], '!=');
                break;
            case 'include':
            case 'includes':
                switch ($attrib['attrib']) {
                    case 'group':
                        $group = get_group_by_name($attrib['value']);
                        if ($group['entity_type'] == $e_type) {
                            $attrib['value'] = $group['group_id'];
                        }
                    case 'group_id':
                        $values = get_group_entities($attrib['value']);
                        $sql .= generate_query_values($values, $entity_type['table_fields']['id']);
                        break;
                }
        }
    }

    // print_vars(array($sql, $param));

    $entities = dbFetchRows($sql, $param);

    return $entities;
}

/**
 * Test if an entity matches a set of attributes
 * Uses a supplied device array for matching.
 *
 * @param array entity
 * @param array attributes
 * @return boolean
 */
// TESTME needs unit testing
function match_entity($entity, $entity_attribs)
{
    // FIXME. Never used, deprecated?
    #print_vars($entity);
    #print_vars($entity_attribs);

    $failed = 0;
    $success = 0;
    $delimiters = array('/', '!', '@');

    foreach ($entity_attribs as $attrib) {
        switch ($attrib['condition']) {
            case 'equals':
                if (mb_strtolower($entity[$attrib['attrib']]) == mb_strtolower($attrib['value'])) {
                    $success++;
                } else {
                    $failed++;
                }
                break;
            case 'match':
                $attrib['value'] = str_replace('*', '.*', $attrib['value']);
                $attrib['value'] = str_replace('?', '.', $attrib['value']);

                foreach ($delimiters as $delimiter) {
                    if (!str_contains($attrib['value'], $delimiter)) {
                        break;
                    }
                }
                if (preg_match($delimiter . '^' . $attrib['value'] . '$' . $delimiter . 'i', $entity[$attrib['attrib']])) {
                    $success++;
                } else {
                    $failed++;
                }
                break;
        }
    }

    if ($failed) {
        return FALSE;
    } else {
        return TRUE;
    }
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function update_device_alert_table($device)
{
    $dbc = array();
    $alert_table = array();

    $msg = "<h4>Building alerts for device " . $device['hostname'] . ':</h4>';
    $msg_class = '';
    $msg_enable = FALSE;
    $conditions = cache_device_conditions($device);

    //foreach ($conditions['cond'] as $test_id => $test)
    //{
    //  #print_vars($test);
    //  #echo('<span class="label label-info">Matched '.$test['alert_name'].'</span> ');
    //}

    $db_cs = dbFetchRows("SELECT * FROM `alert_table` WHERE `device_id` = ?", array($device['device_id']));
    foreach ($db_cs as $db_c) {
        $dbc[$db_c['entity_type']][$db_c['entity_id']][$db_c['alert_test_id']] = $db_c;
    }

    $msg .= PHP_EOL;
    $msg .= '  <h5>Checkers matching this device:</h5> ';

    foreach ($conditions['cond'] as $alert_test_id => $alert_test) {
        $msg .= '<span class="label label-info">' . $alert_test['alert_name'] . '</span> ';
        $msg_enable = TRUE;
        foreach ($alert_test['assoc'] as $assoc_id => $assoc) {
            // Check that the entity_type matches the one we're interested in.
            // echo("Matching $assoc_id (".$assoc['entity_type'].")");

            list($entity_table, $entity_id_field, $entity_name_field) = entity_type_translate($assoc['entity_type']);
            $alert = $conditions['cond'][$assoc['alert_test_id']];
            $entities = match_device_entities($device['device_id'], $assoc['entity_attribs'], $assoc['entity_type']);

            foreach ($entities AS $id => $entity) {
                $alert_table[$assoc['entity_type']][$entity[$entity_id_field]][$assoc['alert_test_id']][] = $assoc_id;
            }

            // echo(count($entities)." matched".PHP_EOL);
        }
    }

    $msg .= PHP_EOL;
    $msg .= '  <h5>Matching entities:</h5> ';

    foreach ($alert_table as $entity_type => $entities) {
        foreach ($entities as $entity_id => $entity) {
            $entity_name = entity_name($entity_type, $entity_id);
            $msg .= '<span class="label label-ok">' . htmlentities($entity_name) . '</span> ';
            $msg_enable = TRUE;

            foreach ($entity as $alert_test_id => $b) {
#        echo(str_pad($entity_type, "20").str_pad($entity_id, "20").str_pad($alert_test_id, "20"));
#        echo(str_pad(implode($b,","), "20"));
                $msg .= '<span class="label label-info">' . $conditions['cond'][$alert_test_id]['alert_name'] . '</span><br >';
                $msg_class = 'success';
                if (isset($dbc[$entity_type][$entity_id][$alert_test_id])) {
                    if ($dbc[$entity_type][$entity_id][$alert_test_id]['alert_assocs'] != implode($b, ",")) {
                        $update_array = array('alert_assocs' => implode($b, ","));
                    }
                    #echo("[".$dbc[$entity_type][$entity_id][$alert_test_id]['alert_assocs']."][".implode($b,",")."]");
                    if (is_array($update_array)) {
                        dbUpdate($update_array, 'alert_table', '`alert_table_id` = ?', array($dbc[$entity_type][$entity_id][$alert_test_id]['alert_table_id']));
                        unset($update_array);
                    }
                    unset($dbc[$entity_type][$entity_id][$alert_test_id]);
                } else {
                    $alert_table_id = dbInsert(array('device_id' => $device['device_id'], 'entity_type' => $entity_type, 'entity_id' => $entity_id, 'alert_test_id' => $alert_test_id, 'alert_assocs' => implode($b, ",")), 'alert_table');
                    //dbInsert(array('alert_table_id' => $alert_table_id), 'alert_table-state');
                }
            }
        }
    }

    $msg .= PHP_EOL;
    $msg .= "  <h5>Checking for stale entries:</h5> ";

    foreach ($dbc as $type => $entity) {
        foreach ($entity as $entity_id => $alert) {
            foreach ($alert as $alert_test_id => $data) {
                dbDelete('alert_table', "`alert_table_id` =  ?", array($data['alert_table_id']));
                //dbDelete('alert_table-state', "`alert_table_id` =  ?", array($data['alert_table_id']));
                $msg .= "-";
                $msg_enable = TRUE;
            }
        }
    }

    if ($msg_enable) {
        return (array('message' => $msg, 'class' => $msg_class));
    }
}

/**
 * Check all alerts for a device to see if they should be notified or not
 *
 * @param array device
 * @return NULL
 */
// TESTME needs unit testing
function process_alerts($device)
{
    global $config, $alert_rules, $alert_assoc;

    $pid_info = check_process_run($device); // This just clear stalled DB entries
    add_process_info($device); // Store process info

    print_cli_heading($device['hostname'] . " [" . $device['device_id'] . "]", 1);

    $alert_table = cache_device_alert_table($device['device_id']);

    $sql = "SELECT * FROM `alert_table`";
    //$sql .= " LEFT JOIN `alert_table-state` ON `alert_table`.`alert_table_id` = `alert_table-state`.`alert_table_id`";
    $sql .= " WHERE `device_id` = ? AND `alert_status` IS NOT NULL;";

    foreach (dbFetchRows($sql, array($device['device_id'])) as $entry) {
        print_cli_data_field('Alert: ' . $entry['alert_table_id']);
        print_cli('Status: [' . $entry['alert_status'] . '] ', 'color');

        // If the alerter is now OK and has previously alerted, send an recovery notice.
        if ($entry['alert_status'] == '1' && $entry['has_alerted'] == '1') {
            $alert = $alert_rules[$entry['alert_test_id']];

            if (!$alert['suppress_recovery']) {
                $log_id = log_alert('Recovery notification sent', $device, $entry, 'RECOVER_NOTIFY');
                alert_notifier($entry, "recovery", $log_id);
            } else {
                echo('Recovery suppressed.');
                $log_id = log_alert('Recovery notification suppressed', $device, $entry, 'RECOVER_SUPPRESSED');
            }

            $update_array['last_recovered'] = time();
            $update_array['has_alerted'] = 0;
            dbUpdate($update_array, 'alert_table', '`alert_table_id` = ?', array($entry['alert_table_id']));
        }

        if ($entry['alert_status'] == '0') {
            echo('Alert tripped. ');

            // Has this been alerted more frequently than the alert interval in the config?
            /// FIXME -- this should be configurable per-entity or per-checker
            if ((time() - $entry['last_alerted']) < $config['alerts']['interval'] && !isset($GLOBALS['spam'])) {
                $entry['suppress_alert'] = TRUE;
            }

            // Don't re-alert if interval set to 0
            if ($config['alerts']['interval'] == 0 && $entry['last_alerted'] != 0) {
                $entry['suppress_alert'] = TRUE;
            }

            // Check if alert has ignore_until set.
            if (is_numeric($entry['ignore_until']) && $entry['ignore_until'] > time()) {
                $entry['suppress_alert'] = TRUE;
            }
            // Check if alert has ignore_until_ok set.
            if (is_numeric($entry['ignore_until_ok']) && $entry['ignore_until_ok'] == '1') {
                $entry['suppress_alert'] = TRUE;
            }

            if ($entry['suppress_alert'] != TRUE) {
                echo('Requires notification. ');

                $log_id = log_alert('Alert notification sent', $device, $entry, 'ALERT_NOTIFY');
                alert_notifier($entry, "alert", $log_id);

                $update_array['last_alerted'] = time();
                $update_array['has_alerted'] = 1;
                dbUpdate($update_array, 'alert_table', '`alert_table_id` = ?', array($entry['alert_table_id']));

            } else {
                echo("No notification required. " . (time() - $entry['last_alerted']));
            }
        } else if ($entry['alert_status'] == '1') {
            echo("Status: OK. ");
        } else if ($entry['alert_status'] == '2') {
            echo("Status: Notification Delayed. ");
        } else if ($entry['alert_status'] == '3') {
            echo("Status: Notification Suppressed. ");
        } else {
            echo("Unknown status.");
        }
        echo(PHP_EOL);
    }

    echo(PHP_EOL);
    print_cli_heading($device['hostname'] . " [" . $device['device_id'] . "] completed notifications at " . date("Y-m-d H:i:s"), 1);

    // Clean
    del_process_info($device); // Remove process info
}

/**
 * Generate notification queue entries for alert system
 *
 * @param array   $entry  Entry
 * @param string  $type   Alert type (alert (default) or syslog)
 * @param integer $log_id Alert log entry ID
 * @return array          List of processed notification ids.
 */
function alert_notifier($entry, $type = "alert", $log_id = NULL)
{
    global $config, $alert_rules;

    $alert_unixtime = time(); // Store time when alert processed

    $device = device_by_id_cache($entry['device_id']);

    if (empty($log_id) && is_numeric($entry['log_id'])) {
        // Log ID can passed as argument or inside entry array
        $log_id = $entry['log_id'];
    }

    $alert = $alert_rules[$entry['alert_test_id']];

    $state = json_decode($entry['state'], TRUE);
    $conditions = json_decode($alert['conditions'], TRUE);

    $entity = get_entity_by_id_cache($entry['entity_type'], $entry['entity_id']);

    $condition_array = array();
    foreach ($state['failed'] as $failed) {
        $condition_array[] = $failed['metric'] . " " . $failed['condition'] . " " . $failed['value'] . " (" . $state['metrics'][$failed['metric']] . ")";
    }

    $metric_array = array();
    foreach ($state['metrics'] as $metric => $value) {
        $metric_array[] = $metric . ' = ' . $value;
    }

    $graphs = array();
    $graph_done = array();
    foreach ($state['metrics'] as $metric => $value) {
        if ($config['email']['graphs'] !== FALSE
            && is_array($config['entities'][$entry['entity_type']]['metric_graphs'][$metric])
            && !in_array($config['entities'][$entry['entity_type']]['metric_graphs'][$metric]['type'], $graph_done)
        ) {
            $graph_array = $config['entities'][$entry['entity_type']]['metric_graphs'][$metric];
            foreach ($graph_array as $key => $val) {
                // Check to see if we need to do any substitution
                if (substr($val, 0, 1) == '@') {
                    $nval = substr($val, 1);
                    //echo(" replaced " . $val . " with " . $entity[$nval] . " from entity. " . PHP_EOL . "<br />");
                    $graph_array[$key] = $entity[$nval];
                }
            }

            $image_data_uri = generate_alert_graph($graph_array);
            $image_url = generate_graph_url($graph_array);

            $graphs[] = array('label' => $graph_array['type'], 'type' => $graph_array['type'], 'url' => $image_url, 'data' => $image_data_uri);

            $graph_done[] = $graph_array['type'];
        }

        unset($graph_array);
    }

    if ($config['email']['graphs'] !== FALSE && count($graph_done) == 0 && is_array($config['entities'][$entry['entity_type']]['graph'])) {
        // We can draw a graph for this type/metric pair!

        $graph_array = $config['entities'][$entry['entity_type']]['graph'];
        foreach ($graph_array as $key => $val) {
            // Check to see if we need to do any substitution
            if (substr($val, 0, 1) == '@') {
                $nval = substr($val, 1);
                //echo(" replaced ".$val." with ". $entity[$nval] ." from entity. ".PHP_EOL."<br />");
                $graph_array[$key] = $entity[$nval];
            }
        }

        //print_vars($graph_array);

        $image_data_uri = generate_alert_graph($graph_array);
        $image_url = generate_graph_url($graph_array);

        $graphs[] = array('label' => $graph_array['type'], 'type' => $graph_array['type'], 'url' => $image_url, 'data' => $image_data_uri);

        unset($graph_array);
    }

    /* unsed
    $graphs_html = "";
    foreach ($graphs as $graph) {
        $graphs_html .= '<h4>' . $graph['type'] . '</h4>';
        $graphs_html .= '<a href="' . $graph['url'] . '"><img src="' . $graph['data'] . '"></a><br />';
    }
    */

    //print_vars($graphs);
    //print_vars($graphs_html);
    //print_vars($entry);

    $message_tags = array(
      'ALERT_STATE'         => ($entry['alert_status'] == '1' ? "RECOVER" : "ALERT"),
      'ALERT_URL'           => generate_url(array('page'        => 'device',
                                                  'device'      => $device['device_id'],
                                                  'tab'         => 'alert',
                                                  'alert_entry' => $entry['alert_table_id'])),
      'ALERT_UNIXTIME'          => $alert_unixtime,                        // Standart unixtime
      'ALERT_TIMESTAMP'         => date('Y-m-d H:i:s P', $alert_unixtime), //           ie: 2000-12-21 16:01:07 +02:00
      'ALERT_TIMESTAMP_RFC2822' => date('r', $alert_unixtime),             // RFC 2822, ie: Thu, 21 Dec 2000 16:01:07 +0200
      'ALERT_TIMESTAMP_RFC3339' => date(DATE_RFC3339, $alert_unixtime),    // RFC 3339, ie: 2005-08-15T15:52:01+00:00
      'ALERT_ID'            => $entry['alert_table_id'],
      'ALERT_MESSAGE'       => $alert['alert_message'],
      'CONDITIONS'          => implode(PHP_EOL . '             ', $condition_array),
      'METRICS'             => implode(PHP_EOL . '             ', $metric_array),
      'DURATION'            => ($entry['alert_status'] == '1' ? ($entry['last_ok'] > 0 ? format_uptime($alert_unixtime - $entry['last_ok']) . " (" . format_unixtime($entry['last_ok']) . ")" : "Unknown")
                               : ($entry['last_ok'] > 0 ? format_uptime($alert_unixtime - $entry['last_ok']) . " (" . format_unixtime($entry['last_ok']) . ")" : "Unknown")),

      // Entity TAGs
      'ENTITY_LINK'         => generate_entity_link($entry['entity_type'], $entry['entity_id'], $entity['entity_name']),
      'ENTITY_NAME'         => $entity['entity_name'],
      'ENTITY_ID'           => $entity['entity_id'],
      'ENTITY_TYPE'         => $alert['entity_type'],
      'ENTITY_DESCRIPTION'  => $entity['entity_descr'],
      //'ENTITY_GRAPHS'       => $graphs_html,          // Predefined/embedded html images
      'ENTITY_GRAPHS_ARRAY' => json_encode($graphs),  // Json encoded images array

      // Device TAGs
      'DEVICE_HOSTNAME'     => $device['hostname'],
      'DEVICE_SYSNAME'      => $device['sysName'],
      //'DEVICE_SYSDESCR'     => $device['sysDescr'],
      'DEVICE_ID'           => $device['device_id'],
      'DEVICE_LINK'         => generate_device_link($device),
      'DEVICE_HARDWARE'     => $device['hardware'],
      'DEVICE_OS'           => $device['os_text'] . ' ' . $device['version'] . ($device['features'] ? ' (' . $device['features'] . ')' : ''),
      //'DEVICE_TYPE'         => $device['type'],
      'DEVICE_LOCATION'     => $device['location'],
      'DEVICE_UPTIME'       => deviceUptime($device),
      'DEVICE_REBOOTED'     => format_unixtime($device['last_rebooted']),
    );

    //logfile('debug.log', var_export($message, TRUE));

    $title = alert_generate_subject($device, $message_tags['ALERT_STATE'], $message_tags);
    $message_tags['TITLE'] = $title;

    $alert_id = $entry['alert_test_id'];

    $notify_status = FALSE; // Set alert notify status to FALSE by default

    $notification_type = 'alert';
    $contacts = get_alert_contacts($device, $alert_id, $notification_type);

    $notification_ids = array(); // Init list of Notification IDs
    foreach ($contacts as $contact)
    {

      // Add notification to queue
      $notification = array(
        'device_id'             => $device['device_id'],
        'log_id'                => $log_id,
        'aca_type'              => $notification_type,
        //'severity'              => 6,
        'endpoints'             => json_encode($contact),
        'message_graphs'        => $message_tags['ENTITY_GRAPHS_ARRAY'],
        'notification_added'    => time(),
        'notification_lifetime' => 300,                   // Lifetime in seconds
        'notification_entry'    => json_encode($entry),   // Store full alert entry for use later if required (not sure that this needed)
      );
      $notification_message_tags = $message_tags;
      unset($notification_message_tags['ENTITY_GRAPHS_ARRAY']); // graphs array stored in separate blob column message_graphs, do not duplicate this data
      $notification['message_tags'] = json_encode($notification_message_tags);
      /// DEBUG
      //file_put_contents('/tmp/alert_'.$alert_id.'_'.$message_tags['ALERT_STATE'].'_'.$alert_unixtime.'.json', json_encode($notification, JSON_PRETTY_PRINT));
      $notification_id = dbInsert($notification, 'notifications_queue');

      print_cli_data("Queueing Notification ", "[" . $notification_id . "]");

      $notification_ids[] = $notification_id;
    }

  return $notification_ids;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function alert_generate_subject($device, $prefix, $message_tags)
{
    $subject = "$prefix: [" . $device['hostname'] . ']';

    if ($message_tags['ENTITY_TYPE']) {
        $subject .= ' [' . $message_tags['ENTITY_TYPE'] . ']';
    }
    if ($message_tags['ENTITY_NAME'] && $message_tags['ENTITY_NAME'] != $device['hostname']) {
        $subject .= ' [' . $message_tags['ENTITY_NAME'] . ']';
    }
    $subject .= ' ' . $message_tags['ALERT_MESSAGE'];

    return $subject;
}

/**
 * Get contacts associated with selected notification type and alert ID
 * Currently know notification types: alert, syslog
 *
 * @param array $device Common device array
 * @param int $alert_id Alert ID
 * @param string $notification_type Used type for notifications
 * @return array Array with transport -> endpoints lists
 */
function get_alert_contacts($device, $alert_id, $notification_type)
{
    if (!is_array($device)) {
        $device = device_by_id_cache($device);
    }

    $contacts = array();

    if (!$device['ignore'] && !get_dev_attrib($device, 'disable_notify') && !$GLOBALS['config']['alerts']['disable']['all']) {
        // figure out which transport methods apply to an alert

        $sql = "SELECT * FROM `alert_contacts`";
        $sql .= " WHERE `contact_disabled` = 0 AND `contact_id` IN";
        $sql .= " (SELECT `contact_id` FROM `alert_contacts_assoc` WHERE `aca_type` = ? AND `alert_checker_id` = ?);";

        foreach (dbFetchRows($sql, array($notification_type, $alert_id)) as $contact) {
            $contacts[] = $contact;
        }

        if (empty($contacts)) {
            // if alert_contacts table is not in use, fall back to default
            // hardcoded defaults for when there is no contact configured.

            $email = NULL;

            if ($GLOBALS['config']['email']['default_only']) {
                // default only mail
                $email = $GLOBALS['config']['email']['default'];
            } else {
                // default device contact
                if (get_dev_attrib($device, 'override_sysContact_bool')) {
                    $email = get_dev_attrib($device, 'override_sysContact_string');
                } else {
                    if (parse_email($device['sysContact'])) {
                        $email = $device['sysContact'];
                    } else {
                        $email = $GLOBALS['config']['email']['default'];
                    }
                }
            }

            if ($email != NULL) {
                $emails = parse_email($email);

                foreach ($emails as $email => $descr) {
                    $contacts[] = array('contact_endpoint' => '{"email":"' . $email . '"}', 'contact_id' => '0', 'contact_descr' => $descr, 'contact_method' => 'email');
                }
            }
        }
    }

    return $contacts;
}

function process_notifications($vars = array())
{
    global $config;

    $result = array();
    $params = array();

    $sql = 'SELECT * FROM `notifications_queue` WHERE 1';

    foreach ($vars as $var => $value)
    {
      switch ($var)
      {
        case 'device_id':
        case 'notification_id':
        case 'aca_type':
          $sql .= generate_query_values($value, $var);
          //$sql .= ' AND `device_id` = ?';
          //$params[] = $value;
          break;
      }
    }

    /**
     * switch ($notification_type)
     * {
     * case 'alert':
     * case 'syslog':
     * // Alerts/syslog required device_id
     * $sql     .= ' AND `device_id` = ?';
     * $params[] = $device['device_id'];
     * break;
     * case 'web':
     * // Currently not used
     * break;
     * }
     **/

    foreach (dbFetchRows($sql, $params) as $notification)
    {

        print_debug_vars($notification);

        // Recheck if current notification is locked
        $locked = dbFetchCell('SELECT `notification_locked` FROM `notifications_queue` WHERE `notification_id` = ?', array($notification['notification_id'])); //ALTER TABLE `notifications_queue` ADD `notification_locked` BOOLEAN NOT NULL DEFAULT FALSE AFTER `notification_entry`;
        //if ($locked || $locked === NULL || $locked === FALSE) // If notification not exist or column 'notification_locked' not exist this query return NULL or (possible?) FALSE
        if ($locked || $locked === FALSE)
        {
            // Notification already processed by other alerter or has already been sent
            print_debug('Notification ID ('.$notification['notification_id'].') locked or not exist anymore in table. Skipped.');
            print_debug_vars($notification, 1);
            continue;
        } else {
            // Lock current notification
            dbUpdate(array('notification_locked' => 1), 'notifications_queue', '`notification_id` = ?', array($notification['notification_id']));
        }

        $notification_count = 0;
        $endpoint = json_decode($notification['endpoints'], TRUE);

        // If this notification is older than lifetime, unset the endpoints so that it is removed.
        if ((time() - $notification['notification_added']) > $notification['notification_lifetime']) {
            $endpoint = array();
            print_debug('Notification ID ('.$notification['notification_id'].') expired.');
            print_debug_vars($notification, 1);
        } else {
            $notification_age = time() - $notification['notification_added'];
            $notification_timeleft = $notification['notification_lifetime'] - $notification_age;
        }

        $message_tags = json_decode($notification['message_tags'], TRUE);
        $message_graphs = json_decode($notification['message_graphs'], TRUE);
        if (is_array($message_graphs) && count($message_graphs))
        {
            $message_tags['ENTITY_GRAPHS_ARRAY'] = $message_graphs;
        }
        if (isset($message_tags['ALERT_UNIXTIME']) && empty($message_tags['DURATION']))
        {
            $message_tags['DURATION'] = format_uptime(time() - $message_tags['ALERT_UNIXTIME']) . ' (' . $message_tags['ALERT_TIMESTAMP'] . ')';
        }

        if (isset($GLOBALS['config']['alerts']['disable'][$endpoint['contact_method']]) && $GLOBALS['config']['alerts']['disable'][$endpoint['contact_method']]) {
            $result[$method] = 'disabled';
            unset($endpoint);
            continue;
        } // Skip if method disabled globally

        $method_include = $GLOBALS['config']['install_dir'] . '/includes/alerting/' . $endpoint['contact_method'] . '.inc.php';

        if (is_file($method_include))
        {
          $transport = $endpoint['contact_method']; // Just set transport name for use in includes

            //print_cli_data("Notifying", "[" . $endpoint['contact_method'] . "] " . $endpoint['contact_descr'] . ": " . $endpoint['contact_endpoint']);
            print_cli_data_field("Notifying");
            echo("[" . $endpoint['contact_method'] . "] " . $endpoint['contact_descr'] . ": " . $endpoint['contact_endpoint']);

            // Split out endpoint data as stored JSON in the database into array for use in transport
            // The original string also remains available as the contact_endpoint key
            foreach (json_decode($endpoint['contact_endpoint']) as $field => $value) {
                $endpoint[$field] = $value;
            }

            include($method_include);

            // FIXME check success
            // FIXME log notification + success/failure!
            if ($notify_status['success'])
            {
                $result[$method] = 'ok';
                unset($endpoint);
                $notification_count++;
                print_message(" [%gOK%n]", 'color');
            } else {
                $result[$method] = 'false';
                print_message(" [%rFALSE%n]", 'color');
                if ($notify_status['error'])
                {
                  print_cli_data_field('', 4);
                  print_message("[%y".$notify_status['error']."%n]", 'color');
                }
            }
        } else {
            $result[$method] = 'missing';
            unset($endpoint); // Remove it because it's dumb and doesn't exist. Don't retry it if it doesn't exist.
            print_cli_data("Missing include", $method_include);
        }

        // Remove notification from queue,
        // currently in any case, lifetime, added time and result status is ignored!
        switch ($notification_type) {
            case 'alert':
                if ($notification_count) {
                    dbUpdate(array('notified' => 1), 'alert_log', '`event_id` = ?', array($notification['log_id']));
                }
                break;
            case 'syslog':
                if ($notification_count) {
                    dbUpdate(array('notified' => 1), 'syslog_alerts', '`lal_id` = ?', array($notification['log_id']));
                }
                break;
            case 'web':
                // Currently not used
                break;
        }

        if (empty($endpoint)) {
            dbDelete('notifications_queue', '`notification_id` = ?', array($notification['notification_id']));
        } else {
            // Set the endpoints to the remaining un-notified endpoints and unlock the queue entry.
            dbUpdate(array('notification_locked' => 0, 'endpoints' => json_encode($endpoint)), 'notifications_queue', '`notification_id` = ?', array($notification['notification_id']));
        }
    }

    return $result;
}

// Use this function to write to the alert_log table
// Fix me - quite basic.
// DOCME needs phpdoc block
// TESTME needs unit testing
function log_alert($text, $device, $alert, $log_type)
{
    $insert = array('alert_test_id' => $alert['alert_test_id'],
        'device_id' => $device['device_id'],
        'entity_type' => $alert['entity_type'],
        'entity_id' => $alert['entity_id'],
        'timestamp' => array("NOW()"),
        //'status'        => $alert['alert_status'],
        'log_type' => $log_type,
        'message' => $text);

    $id = dbInsert($insert, 'alert_log');

    return $id;
}

function threshold_string($alert_low, $warn_low, $warn_high, $alert_high, $symbol = NULL)
{

  // Generate "pretty" thresholds
  if (is_numeric($alert_low))
  {
    $alert_low_t = format_value($alert_low, $format) . $symbol;
  } else {
    $alert_low_t = "&infin;";
  }

  if (is_numeric($warn_low))
  {
    $warn_low_t = format_value($warn_low, $format) . $symbol;
  } else {
    $warn_low_t = NULL;
  }

  if ($warn_low_t) { $alert_low_t = $alert_low_t . " (".$warn_low_t.")"; }

  if (is_numeric($alert_high))
  {
    $alert_high_t = format_value($alert_high, $format) . $symbol;
  } else {
    $alert_high_t = "&infin;";
  }

  if (is_numeric($warn_high))
  {
    $warn_high_t = format_value($warn_high, $format) . $symbol;
  } else {
    $warn_high_t = NULL;
  }

  if ($warn_high_t) { $alert_high_t = "(".$warn_high_t.") " . $alert_high_t; }

  $thresholds = $alert_low_t . ' - ' . $alert_high_t;

  return $thresholds;

}

function check_thresholds($alert_low, $warn_low, $warn_high, $alert_high, $value)
{

  if (!is_numeric($value)) { return 'alert'; } // Not numeric value always alert

  if ((is_numeric($alert_low)  && $value <= $alert_low) ||
      (is_numeric($alert_high) && $value >= $alert_high))
  {
    $event = 'alert';
  }
  elseif ((is_numeric($warn_low)  && $value < $warn_low) ||
          (is_numeric($warn_high) && $value > $warn_high))
  {
    $event = 'warning';
  } else {
    $event = 'ok';
  }

  /*
  if(is_numeric($warn_low) && $warn_low > $value)   { $status = 'warn'; }
  if(is_numeric($warn_high) && $warn_high < $value) { $status = 'warn'; }

  if(is_numeric($alert_low)  && $alert_low > $value)   { $status = 'alert'; }
  if(is_numeric($alert_high) && $alert_high < $value)  { $status = 'alert'; }
  */

  return $event;

}

function get_alert_entities_from_assocs($alert)
{

   $entity_type_data = entity_type_translate_array($alert['entity_type']);

   $entity_type = $alert['entity_type'];

   $sql  = 'SELECT `'.$entity_type_data['table_fields']['id'] . '` AS `entity_id`';

   //We always need device_id and it's always from devices, duh!
   //if ($alert['entity_type'] != 'device')
   //{
      $sql .= ", `devices`.`device_id` as `device_id`";
   //}

   $sql .= ' FROM `'.$entity_type_data['table'].'` ';

//   if (isset($entity_type_data['state_table']))
//   {
//      $sql .= ' LEFT JOIN `'.$entity_type_data['state_table'].'` USING (`'.$entity_type_data['id_field'].'`)';
//   }

   if (isset($entity_type_data['parent_table']))
   {
      $sql .= ' LEFT JOIN `'.$entity_type_data['parent_table'].'` USING (`'.$entity_type_data['parent_id_field'].'`)';
   }

   if ($alert['entity_type'] != 'device')
   {
      $sql .= ' LEFT JOIN `devices` ON (`'.$entity_type_data['table'].'`.`device_id` = `devices`.`device_id`) ';
   }



   foreach ($alert['assocs'] as $assoc)
   {

      $where = ' (( 1';

      foreach ($assoc['device_attribs'] as $attrib)
      {
         switch ($attrib['condition'])
         {
            case 'ge':
            case '>=':
               $where    .= ' AND `devices`.`' . $attrib['attrib'] . '` >= ?';
               $params[] = $attrib['value'];
               break;
            case 'le':
            case '<=':
               $where    .= ' AND `devices`.`' . $attrib['attrib'] . '` <= ?';
               $params[] = $attrib['value'];
               break;
            case 'gt':
            case 'greater':
            case '>':
               $where    .= ' AND `devices`.`' . $attrib['attrib'] . '` > ?';
               $params[] = $attrib['value'];
               break;
            case 'lt':
            case 'less':
            case '<':
               $where    .= ' AND `devices`.`' . $attrib['attrib'] . '` < ?';
               $params[] = $attrib['value'];
               break;
            case 'notequals':
            case 'isnot':
            case 'ne':
            case '!=':
               $where    .= ' AND `devices`.`' . $attrib['attrib'] . '` != ?';
               $params[] = $attrib['value'];
               break;
            case 'equals':
            case 'eq':
            case 'is':
            case '==':
            case '=':
               $where    .= ' AND `devices`.`' . $attrib['attrib'] . '` = ?';
               $params[] = $attrib['value'];
               break;
            case 'match':
            case 'matches':
               $attrib['value'] = str_replace('*', '%', $attrib['value']);
               $attrib['value'] = str_replace('?', '_', $attrib['value']);
               $where           .= ' AND IFNULL(`devices`.`' . $attrib['attrib'] . '`, "") LIKE ?';
               $params[]        = $attrib['value'];
               break;
            case 'notmatches':
            case 'notmatch':
            case '!match':
               $attrib['value'] = str_replace('*', '%', $attrib['value']);
               $attrib['value'] = str_replace('?', '_', $attrib['value']);
               $where           .= ' AND IFNULL(`devices`.`' . $attrib['attrib'] . '`, "") NOT LIKE ?';
               $params[]        = $attrib['value'];
               break;
            case 'regexp':
            case 'regex':
               $where    .= ' AND IFNULL(`devices`.`' . $attrib['attrib'] . '`, "") REGEXP ?';
               $params[] = $attrib['value'];
               break;
            case 'notregexp':
            case 'notregex':
            case '!regexp':
            case '!regex':
               $where    .= ' AND IFNULL(`devices`.`' . $attrib['attrib'] . '`, "") NOT REGEXP ?';
               $params[] = $attrib['value'];
               break;
            case 'in':
            case 'list':
               $where .= generate_query_values(explode(',', $attrib['value']), '`devices`.' . $attrib['attrib']);
               break;
            case '!in':
            case '!list':
            case 'notin':
            case 'notlist':
               $where .= generate_query_values(explode(',', $attrib['value']), '`devices`.' . $attrib['attrib'], '!=');
               break;
            case 'include':
            case 'includes':
               switch ($attrib['attrib'])
               {
                  case 'group':
                     $attrib['value'] = group_id_by_name($attrib['value']);
                  case 'group_id':
                     $values = get_group_entities($attrib['value']);
                     $where .= generate_query_values($values, "`devices`.`device_id`");
                     break;
               }
               break;
         }

      } // End device_attribs


      $where .= ") AND ( 1";

      foreach ($assoc['entity_attribs'] as $attrib) {
         switch ($attrib['condition']) {
            case 'ge':
            case '>=':
               $where .= ' AND `' . $attrib['attrib'] . '` >= ?';
               $params[] = $attrib['value'];
               break;
            case 'le':
            case '<=':
               $where .= ' AND `' . $attrib['attrib'] . '` <= ?';
               $params[] = $attrib['value'];
               break;
            case 'gt':
            case 'greater':
            case '>':
               $where .= ' AND `' . $attrib['attrib'] . '` > ?';
               $params[] = $attrib['value'];
               break;
            case 'lt':
            case 'less':
            case '<':
               $where .= ' AND `' . $attrib['attrib'] . '` < ?';
               $params[] = $attrib['value'];
               break;
            case 'notequals':
            case 'isnot':
            case 'ne':
            case '!=':
               $where .= ' AND `' . $attrib['attrib'] . '` != ?';
               $params[] = $attrib['value'];
               break;
            case 'equals':
            case 'eq':
            case 'is':
            case '==':
            case '=':
               $where .= ' AND `' . $attrib['attrib'] . '` = ?';
               $params[] = $attrib['value'];
               break;
            case 'match':
            case 'matches':
               $attrib['value'] = str_replace('*', '%', $attrib['value']);
               $attrib['value'] = str_replace('?', '_', $attrib['value']);
               $where .= ' AND IFNULL(`' . $attrib['attrib'] . '`, "") LIKE ?';
               $params[] = $attrib['value'];
               break;
            case 'notmatches':
            case 'notmatch':
            case '!match':
               $attrib['value'] = str_replace('*', '%', $attrib['value']);
               $attrib['value'] = str_replace('?', '_', $attrib['value']);
               $where .= ' AND IFNULL(`' . $attrib['attrib'] . '`, "") NOT LIKE ?';
               $params[] = $attrib['value'];
               break;
            case 'regexp':
            case 'regex':
               $where .= ' AND IFNULL(`' . $attrib['attrib'] . '`, "") REGEXP ?';
               $params[] = $attrib['value'];
               break;
            case 'notregexp':
            case 'notregex':
            case '!regexp':
            case '!regex':
               $where .= ' AND IFNULL(`' . $attrib['attrib'] . '`, "") NOT REGEXP ?';
               $params[] = $attrib['value'];
               break;
            case 'in':
            case 'list':
               $where .= generate_query_values(explode(',', $attrib['value']), $attrib['attrib']);
               break;
            case '!in':
            case '!list':
            case 'notin':
            case 'notlist':
               $where .= generate_query_values(explode(',', $attrib['value']), $attrib['attrib'], '!=');
               break;
            case 'include':
            case 'includes':
               switch ($attrib['attrib']) {

                  case 'group':
                     $attrib['value'] = group_id_by_name($attrib['value']);
                  case 'group_id':
                     $group = get_group_by_id($attrib['value']);
                     if($group['entity_type'] == $entity_type)
                     {
                        $values = get_group_entities($attrib['value']);
                        $where  .= generate_query_values($values, $entity_type['table_fields']['id']);
                     }
                     break;
               }
         }
      }

      $where .= '))';

      $assoc_where[] = $where;

   }

   if (empty($assoc_where))
   {
      print_debug('WARNING. Into function '.__FUNCTION__.'() passed incorrect or empty entries.');
      return FALSE;
   }

   $where = "WHERE `devices`.`ignore` = '0' AND `devices`.`disabled` = '0' AND (" . implode(" OR ", $assoc_where) .")";

   if (isset($entity_type_data['deleted_field'])) {
      $where .= " AND `" . $entity_type_data['deleted_field'] . "` != '1'";
   }

   $query    = $sql;
   $query   .= $where;

   $entities  = dbFetchRows($query, $params);
   //$entities  = dbFetchRows($query, $params, TRUE);

  $return = [];
   foreach($entities as $entry)
   {
      $return[$entry['entity_id']] = array('entity_id' => $entry['entity_id'], 'device_id' => $entry['device_id']);
   }

   return $return;

   //print_vars($devices);
}

// QB / Alerts/ Groups common functions

// Because the order of key-value objects is uncertain, you can also use an array of one-element objects. (See query builder doc: http://querybuilder.js.org/#filters)
function values_to_json($values)
{

  //foreach($values as $id => $value) { $array[] = "'".$id."': '".str_replace(array("'", ","), array("\'", "\,")."'"; }
  foreach ($values as $id => $value)
  {
    //$array[] = '{ '.json_encode($id, OBS_JSON_ENCODE).': '.json_encode($value, OBS_JSON_ENCODE).' }';
    // Expanded format with optgroups
    // { value: 'one', label: 'Un', optgroup: 'Group 1' },
    if (is_array($value))
    {
      // Our form builder params
      $str = '{ value: '.json_encode($id, OBS_JSON_ENCODE).', label: '.json_encode($value['name'], OBS_JSON_ENCODE);
      if (isset($value['group']))
      {
        $str .= ', optgroup: ' . json_encode($value['group'], OBS_JSON_ENCODE);
      }
      $str .= ' }';
      $array[] = $str;
    } else {
      // Simple value -> label
      // { value: 'one', label: 'Un', optgroup: 'Group 1' },
      $array[] = '{ value: '.json_encode($id, OBS_JSON_ENCODE).', label: '.json_encode($value, OBS_JSON_ENCODE).' }';
    }
  }

  $array = ' [ '.implode(', ', $array).' ] ';

  return $array;
}

function generate_attrib_values($attrib, $vars)
{

  $values = array();
  //r($vars);

  switch ($attrib)
  {
    case "device":
      $values = generate_form_values('device', NULL, NULL, array('disabled' => TRUE));
      //$devices = get_all_devices();
      //foreach($devices as $id => $hostname)
      //{
      //  $values[$id] = $hostname;
      //}
      break;
    case "os":
      foreach ($GLOBALS['config']['os'] AS $os => $os_array)
      {
        $values[$os] = $os_array['text'];
      }
      break;
    case "measured_group":
      $groups = get_groups_by_type($vars['measured_type']);
      foreach ($groups[$vars['measured_type']] as $group_id => $array)
      {
        $values[$array['group_id']] = $array['group_name'];
      }
      break;
    case "group":
      $groups = get_groups_by_type($vars['entity_type']);
      foreach ($groups[$vars['entity_type']] as $group_id => $array)
      {
        $values[$array['group_id']] = $array['group_name'];
      }
      break;
    case "location":
      $values = get_locations();
      break;
    case "device_type":
      foreach ($GLOBALS['config']['device_types'] AS $type)
      {
        $values[$type['type']] = $type['text'];
      }
      break;
    case "device_vendor":
    case "device_hardware":
    case "device_distro":
    case "device_distro_ver":
      list(, $column) = explode('_', $attrib, 2);
      $query  = "SELECT DISTINCT `$column` FROM `devices`";
      foreach (dbFetchColumn($query) as $item)
      {
        if (strlen($item)) { $values[$item] = $item; }
      }
      ksort($values);
      break;
    /*
    case "device_distro":
      $query  = "SELECT `distro` FROM `devices` GROUP BY `distro` ORDER BY `distro`";
      foreach (dbFetchColumn($query) as $item)
      {
        if (strlen($item)) { $values[$item] = $item; }
      }
      break;
    case "device_distro_ver":
      $query  = "SELECT `distro_ver` FROM `devices` GROUP BY `distro_ver` ORDER BY `distro_ver`";
      foreach (dbFetchColumn($query) as $item)
      {
        if (strlen($item)) { $values[$item] = $item; }
      }
      break;
    */
    case "sensor_class":
      foreach($GLOBALS['config']['sensor_types'] AS $class => $data)
      {
        $values[$class] = nicecase($class);
      }
      break;
     case "status_type":
        $query  = "SELECT `status_type` FROM `status` GROUP BY `status_type` ORDER BY `status_type`";
        foreach (dbFetchColumn($query) as $item)
        {
           if (strlen($item)) { $values[$item] = $item; }
        }
        break;
  }

  return $values;

}

function generate_querybuilder_filter($attrib)
{

  // Default operators, possible custom list from entity definition (ie group)
  if (isset($attrib['operators']))
  {
    // All possible operators, for validate entity attrib
    $operators_array = array('equals', 'notequals', 'le', 'ge', 'lt', 'gt', 'match', 'notmatch', 'regexp', 'notregexp', 'in', 'notin', 'isnull', 'isnotnull');

    // List to array
    if (!is_array($attrib['operators']))
    {
      $attrib['operators'] = explode(',', str_replace(' ', '', $attrib['operators']));
    }

    $operators = array_intersect($attrib['operators'], $operators_array); // Validate operators list
    $text_operators = "['" . implode("', '", $operators) . "']";
  } else {
    $text_operators = "['equals', 'notequals', 'match', 'notmatch', 'regexp', 'notregexp', 'in', 'notin', 'isnull', 'isnotnull']";
  }
  $num_operators  = "['equals', 'notequals', 'le', 'ge', 'lt', 'gt', 'in', 'notin']";
  $list_operators = "['in', 'notin']";
  $bool_operators = "['equals', 'notequals']";
  $function_operators = "['in', 'notin']";

  $attrib['attrib_id'] = ($attrib['entity_type'] == 'device' ? 'device.' : 'entity.').$attrib['attrib_id'];
  $attrib['label'] = ($attrib['entity_type'] == 'device' ? 'Device ' : nicecase($attrib['entity_type']).' ').$attrib['label'];

  // Clean label duplicates
  $attrib['label'] = implode(' ', array_unique(explode(' ', $attrib['label'])));
  //$attrib['label'] = str_replace("Device Device", "Device", $attrib['label']);
  //r($attrib);

  $filter_array[] = "id: '".$attrib['attrib_id']. ($attrib['free'] ? '.free': '')."'";
  $filter_array[] = "field: '".$attrib['attrib_id']. "'";
  $filter_array[] = "label: '".$attrib['label']. ($attrib['free'] ? ' (Free)': '')."'";
  if ($attrib['type'] == 'boolean')
  {
    // Prevent store boolean type as boolean true/false in DB, keep as integer
    $filter_array[] = "type: 'integer'";
  } else {
    $filter_array[] = "type: '".$attrib['type']."'";
  }
  $filter_array[] = "optgroup: '".nicecase($attrib['entity_type'])."'";

  // Plugins options:
  $selectpicker_options = "width: '100%', iconBase: '', tickIcon: 'glyphicon glyphicon-ok', showTick: true, selectedTextFormat: 'count>2', ";
  $tagsinput_options    = "trimValue: true, tagClass: function(item) { return 'label label-default'; }";

  if (isset($attrib['values']))
  {

    if (is_array($attrib['values'])) {

      $value_list = array();
      foreach($attrib['values'] AS $value) {
        $value_list[$value] = $value;
      }

    } else {
      $value_list = generate_attrib_values($attrib['values'], array('entity_type' => $attrib['entity_type'], 'measured_type' => $attrib['measured_type']));
    }

    asort($value_list);
    //r($value_list);
    if (count($value_list) > 7)
    {
      $selectpicker_options .= "liveSearch: true, actionsBox: true, ";
    }
    $values = values_to_json($value_list);
    $filter_array[] = "input: 'select'";
    $filter_array[] = "plugin: 'selectpicker'";
    $filter_array[] = "plugin_config: { $selectpicker_options }";
    $filter_array[] = "values: ".$values;
    $filter_array[] = "multiple: true";
    $filter_array[] = "operators: ".$list_operators;
  } else {

    if (isset($attrib['function']))
    {
      register_html_resource('js',  'bootstrap-tagsinput.min.js');  // Enable Tags Input JS
      register_html_resource('css', 'bootstrap-tagsinput.css');     // Enable Tags Input CSS
      $filter_array[] = "input: 'select'";
      $filter_array[] = "plugin: 'tagsinput'";
      $filter_array[] = "plugin_config: { $tagsinput_options }";
      //$filter_array[] = "value_separator: ','";
      $filter_array[] = "valueSetter: function(rule, value) {
          var rule_container = rule.\$el.find('.rule-value-container select');
          if (typeof value == 'string') {
            rule_container.tagsinput('add', value);
          } else {
            for (i = 0; i < value.length; ++i) { rule_container.tagsinput('add', value[i]); }
          }
        }";
      $filter_array[] = "multiple: true";
      $filter_array[] = "operators: ".$function_operators;
    }
    else if ($attrib['type'] == 'integer')
    {
      $filter_array[] = "operators: ".$num_operators;
    }
    else if ($attrib['type'] == 'boolean')
    {
      $values = values_to_json(array(0 => 'False', 1 => 'True'));
      $filter_array[] = "input: 'select'";
      $filter_array[] = "plugin: 'selectpicker'";
      $filter_array[] = "plugin_config: { $selectpicker_options }";
      $filter_array[] = "values: ".$values;
      $filter_array[] = "multiple: false";
      $filter_array[] = "operators: ".$bool_operators;
    } else {
      $filter_array[] = "operators: ".$text_operators;
        //$filter_array[] = "plugin: 'tagsinput'";
        //$filter_array[] = "value_separator: ','";

    }
  }

  $filter = PHP_EOL . '{ '.implode(','.PHP_EOL, $filter_array).' } ';

  return $filter;

}

function generate_querybuilder_filters($entity_type, $type = "attribs")
{

  $type = (($type == "attribs" || $type == "metrics") ? $type : 'attribs');

  if (isset($GLOBALS['config']['entities'][$entity_type]['parent_type']))
  {
    $filter = generate_querybuilder_filters($GLOBALS['config']['entities'][$entity_type]['parent_type']);
  }
  else if($type != "metrics" && $entity_type != "device")
  {
    $filter = generate_querybuilder_filters("device");
  }

  foreach($GLOBALS['config']['entities'][$entity_type][$type] AS $attrib_id => $attrib)
  {
    $attrib['entity_type'] = $entity_type;
    $attrib['attrib_id']   = $attrib_id;

    $filter[] = generate_querybuilder_filter($attrib);

    if (isset($attrib['values']) && !str_ends($attrib['attrib_id'], "_id") &&      // Don't show freeform variant for device_id, location_id, group_id and etc
                                     (!isset($attrib['free']) || $attrib['free'])) // Don't show freeform variant if attrib free set to false
    {
      unset($attrib['values']);
      $attrib['free'] = 1;
      $filter[] = generate_querybuilder_filter($attrib);
    }
  }

  //$filters = ' [ '.implode(', ', $filter).' ] ';

  //print_vars($filter);
  return $filter;

}

function generate_querybuilder_form($entity_type, $type = "attribs", $form_id = 'rules-form', $ruleset = NULL)
{

   // Set rulesets, with allow invalid!
   if (!empty($ruleset))
   {
      $rulescript = "
  var rules = ".$ruleset.";

  $('#".$form_id."').queryBuilder('setRules', rules, { allow_invalid: true });

  $('#btn-set').on('click', function() {
    $('#".$form_id."').queryBuilder('setRules', rules, { allow_invalid: true });
  });";

      register_html_resource('script', $rulescript);
   }

   $filters = ' [ '.implode(', ', generate_querybuilder_filters($entity_type, $type)).' ] ';

   //$form_id = 'builder-'.$entity_type.'-'.$type;

   echo ('

    <div class="box box-solid">
      <!-- <div class="box-header with-border">
        <h3>' . nicecase($entity_type) . ' '.nicecase($type).' Rules Builder</h3>
      </div> -->

      <div id="'.$form_id.'"></div>

      <!--
      <div class="box-footer">
        <div class="btn-group pull-right">
          <button class="btn btn-sm btn-danger" id="btn-reset" data-target="'.$form_id.'">Reset</button>
          <button class="btn btn-sm btn-success" id="btn-set" data-target="'.$form_id.'">Set rules</button>
          <button class="btn btn-sm btn-success" id="btn-get" data-target="'.$form_id.'">Show JSON</button>
          <button class="btn btn-sm btn-primary" id="btn-save" data-target="'.$form_id.'">Save Rules</button>
        </div>
      </div> -->
    </div>');


   echo ("<div class='box box-solid' id='output'></div>

<script>

  $('#".$form_id."').queryBuilder({
    plugins: {
      'bt-selectpicker': {
        style: 'btn-inverse btn',
        width: '100%',
        liveSearch: true,
      },
      'sortable': null,
    },
    filters: ".$filters.",

      //operators: $.fn.queryBuilder.constructor.DEFAULTS.operators.concat([
      operators: ([
      { type: 'le',		      nb_inputs: 1, multiple: false, apply_to: ['string'] },
      { type: 'ge',		      nb_inputs: 1, multiple: false, apply_to: ['string'] },
      { type: 'lt',		      nb_inputs: 1, multiple: false, apply_to: ['string'] },
      { type: 'gt',		      nb_inputs: 1, multiple: false, apply_to: ['string'] },
      { type: 'equals',		  nb_inputs: 1, multiple: false, apply_to: ['string'] },
      { type: 'notequals',	nb_inputs: 1, multiple: false, apply_to: ['string'] },
      { type: 'match',		  nb_inputs: 1, multiple: false, apply_to: ['string'] },
      { type: 'notmatch',	  nb_inputs: 1, multiple: false, apply_to: ['string'] },
      { type: 'regexp',  	  nb_inputs: 1, multiple: false, apply_to: ['string'] },
      { type: 'notregexp',	nb_inputs: 1, multiple: false, apply_to: ['string'] },
      { type: 'in',		      nb_inputs: 1, multiple: true,  apply_to: ['string'] },
      { type: 'notin',		  nb_inputs: 1, multiple: true,  apply_to: ['string'] },
      { type: 'isnull',		      nb_inputs: 0,  apply_to: ['string'] },
      { type: 'isnotnull',		  nb_inputs: 0,  apply_to: ['string'] }
    ]),
    lang: {
      operators: {
        le:         'less or equal',
        ge:         'greater or equal',
        lt:         'less than',
        gt:         'greater than',
        equals:     'equals',
        notequals:  'not equals',
        match:      'match',
        notmatch:   'not match',
        regexp:     'regexp',
        notregexp:  'not regexp',
        in:         'in',
        notin:      'not in',
        isnull:     'is null',
        isnotnull:    'not null'
      }
    },
  });


$('#btn-reset').on('click', function() {
  $('#".$form_id."').queryBuilder('reset');
});

$('#btn-get').on('click', function() {
  var result = $('#".$form_id."').queryBuilder('getRules');

  if (!$.isEmptyObject(result)) {
    bootbox.alert({
      title: $(this).text(),
      message: '<pre class=\"code-popup\">' + format4popup(result) + '</pre>'
    });
  }
});

function format4popup(object) {
  return JSON.stringify(object, null, 2).replace(/</g, '&lt;').replace(/>/g, '&gt;')
}

</script>

");

}

function parse_qb_ruleset($entity_type, $rules, $ignore = FALSE)
{

  $entity_type_data = entity_type_translate_array($entity_type);

  $sql  = 'SELECT `'.$entity_type_data['table_fields']['id'] . '`';

  if ($entity_type != 'device')
  {
    $sql .= ", `devices`.`device_id` as `device_id`";
  }

  $sql .= ' FROM `'.$entity_type_data['table'].'` ';

//  if (isset($entity_type_data['state_table']))
//  {
//    $sql .= ' LEFT JOIN `'.$entity_type_data['state_table'].'` USING (`'.$entity_type_data['id_field'].'`)';
//  }

  if (isset($entity_type_data['parent_table']))
  {
    $sql .= ' LEFT JOIN `'.$entity_type_data['parent_table'].'` USING (`'.$entity_type_data['parent_id_field'].'`)';
  }

  if ($entity_type != 'device')
  {
    $sql .= ' LEFT JOIN `devices` ON (`'.$entity_type_data['table'].'`.`device_id` = `devices`.`device_id`) ';
  }

  $sql .= " WHERE ";

  $sql .= parse_qb_rules($entity_type, $rules, $ignore);

  if ($ignore) // This is for alerting, so filter out ignore/disabled stuff
  {
    // Exclude ignored entities
    if (isset($entity_type_data['ignore_field']))
    {
      $sql .= " AND `".$entity_type_data['table']."`.`" . $entity_type_data['ignore_field'] . "` != '1'";
    }
    // Exclude disabled entities
    if (isset($entity_type_data['disable_field']))
    {
      $sql .= " AND `".$entity_type_data['table']."`.`" . $entity_type_data['disable_field'] . "` != '1'";
    }
    // Exclude disabled/ignored devices (if not device entity)
    if ($entity_type != 'device')
    {
      $sql .= " AND `devices`.`disabled` != '1'";
      $sql .= " AND `devices`.`ignore` != '1'";
    }
  }

  if (isset($entity_type_data['deleted_field'])) {
    $sql .= " AND `".$entity_type_data['table']."`.`" . $entity_type_data['deleted_field'] . "` != '1'";
  }

  //r($sql);

  return $sql;
}

function parse_qb_rules($entity_type, $rules, $ignore = FALSE)
{
  global $config;

  $entity_type_data = entity_type_translate_array($entity_type);
  $entity_attribs   = $config['entities'][$entity_type]['attribs'];
  $parts = array();
  foreach ($rules['rules'] as $rule)
  {

    if (is_array($rule['rules']))
    {

      $parts[] = parse_qb_rules($entity_type, $rule);

    } else {

      //print_r($rule);

      list($table, $field) = explode('.', $rule['field']);

      if ($table == 'entity' || $table == $entity_type)
      {
        $table_type_data = $entity_type_data;
      } else {
        // This entity can be not same as main entity!
        $table_type_data = entity_type_translate_array($table);
      }

      // Pre Transform value according to DB field (see port ARP/MAC)
      if (isset($entity_attribs[$field]['transformations']))
      {
        $rule['value'] = string_transform($rule['value'], $entity_attribs[$field]['transformations']);
      }

      $part = '';
      // Check if field is measured entity
      $field_measured = isset($entity_attribs[$field]['measured_type']) &&                    // Attrib have measured type param
                        isset($config['entities'][$entity_attribs[$field]['measured_type']]); // And this entity type exist

      if (isset($entity_attribs[$field]['function']) &&
          function_exists($entity_attribs[$field]['function']))
      {
        // Pass original rule value, which translated to entity_id(s) by function call
        $function_args = array($entity_type, $rule['value']);
        $rule['value'] = call_user_func_array($entity_attribs[$field]['function'], $function_args);
        // Override $field by entity_id
        $rule['field_quoted'] = '`'.$entity_type_data['table'].'`.`'.$entity_type_data['table_fields']['id'].'`';

        //print_vars($function_args);
        //print_vars($rule['value']);
      }
      else if ($field_measured) {
        // This attrib is measured entity
        //$measured_type      = $entity_attribs[$field]['measured_type'];
        //$measured_type_data = entity_type_translate_array($measured_type);

        switch ($entity_attribs[$field]['values']) {
          case 'measured_group':
            // When values used as measured group, convert it to entity ids
            //logfile('groups.log', 'passed value: '.var_export($rule['value'], TRUE)); /// DEVEL
            $group_ids = !is_array($rule['value']) ? explode(',', $rule['value']) : $rule['value'];
            $rule['value'] = get_group_entities($group_ids);
            //logfile('groups.log', 'groups value: '.var_export($rule['value'], TRUE)); /// DEVEL
            break;
          default:
            //$rule['field_quoted'] = '`'.$table_type_data['table'].'`.`'.$field.'`';
        }
        // Override $field by measured entity_id
        $rule['field_quoted'] = '`'.$table_type_data['table'].'`.`'.$entity_type_data['table_fields']['measured_id'].'`';
        //logfile('groups.log', 'value: '.var_export($rule['value'], TRUE)); /// DEVEL
        //logfile('groups.log', 'field: '.$rule['field_quoted']);            /// DEVEL

      }
      else if (isset($entity_attribs[$field]['table']))
      {
        // This attrib specifies a table name (used for oid, since there is no parent)
        $rule['field_quoted'] = '`'.$entity_attribs[$field]['table'].'`.`'.$field.'`';
      }
      else if (!isset($entity_attribs[$field])
                 && isset($config['entities'][$entity_type]['parent_type'])
                 && isset($config['entities'][$config['entities'][$entity_type]['parent_type']]['attribs'][$field]))
      {
        // This attrib does not exist on this entity && this entity has a parent && this attrib exists on the parent
        $rule['field_quoted'] = '`'.$config['entities'][$config['entities'][$entity_type]['parent_type']]['table'].'`.`'.$field.'`';

      } else {

        //$rule['field_quoted'] = '`'.$field.'`';
        // Always use full table.column, for do not get errors ambiguous (after JOINs)

        $rule['field_quoted'] = '`'.$table_type_data['table'].'`.`'.$field.'`';
      }


      $operator_negative = FALSE; // Need for measured
      switch ($rule['operator'])
      {
        case 'ge':
          $part = ' ' . $rule['field_quoted'] . " >= '" . dbEscape($rule['value']) . "'";
          break;
        case 'le':
          $part = ' ' . $rule['field_quoted'] . " <= '" . dbEscape($rule['value']) . "'";
          break;
        case 'gt':
          $part = ' ' . $rule['field_quoted'] . " > '" . dbEscape($rule['value']) . "'";
          break;
        case 'lt':
          $part = ' ' . $rule['field_quoted'] . " < '" . dbEscape($rule['value']) . "'";
          break;
        case 'notequals':
          $operator_negative = TRUE;
          $part = ' ' . $rule['field_quoted'] . " != '" . dbEscape($rule['value']) . "'";
          break;
        case 'equals':
          $part = ' ' . $rule['field_quoted'] . " = '" . dbEscape($rule['value']) . "'";
          break;
        case 'match':
          switch ($field)
          {
            case 'group':
              $group = get_group_by_name($rule['value']);
              if ($group['entity_type'] == $table) {
                $values = get_group_entities($group['group_id']);
                $part = generate_query_values($values, ($table == "device" ? "devices.device_id" : $table_type_data['table'].'.'.$entity_type_data['table_fields']['id']), NULL, FALSE);
              }
              break;
            default:
              $rule['value'] = str_replace('*', '%', $rule['value']);
              $rule['value'] = str_replace('?', '_', $rule['value']);
              $part = ' IFNULL(' . $rule['field_quoted'] . ', "") LIKE' . " '" . dbEscape($rule['value']) . "'";
              break;
          }
          break;
        case 'notmatch':
          $operator_negative = TRUE;
          switch ($field)
          {
            case 'group':
              $group = get_group_by_name($rule['value']);
              if ($group['entity_type'] == $table) {
                $values = get_group_entities($group['group_id']);
                $part = generate_query_values($values, ($table == "device" ? "devices.device_id" : $table_type_data['table'].'.'.$entity_type_data['table_fields']['id']), '!=', FALSE);
              }
              break;
            default:
              $rule['value'] = str_replace('*', '%', $rule['value']);
              $rule['value'] = str_replace('?', '_', $rule['value']);
              $part = ' IFNULL(' . $rule['field_quoted'] . ', "") NOT LIKE' . " '" . dbEscape($rule['value']) . "'";
              break;
          }
          break;
        case 'regexp':
          $part = ' IFNULL(' . $rule['field_quoted'] . ', "") REGEXP' . " '" . dbEscape($rule['value']) . "'";
          break;
        case 'notregexp':
          $operator_negative = TRUE;
          $part = ' IFNULL(' . $rule['field_quoted'] . ', "") NOT REGEXP' . " '" . dbEscape($rule['value']) . "'";
          break;
        case 'isnull':
          $part = ' ' .$rule['field_quoted'] . ' IS NULL';
          break;
        case 'isnotnull':
          $part = ' ' .$rule['field_quoted'] . ' IS NOT NULL';
          break;
        case 'in':
          //print_vars($field);
          //print_vars($rule);
          switch ($field)
          {
            case 'group_id':
              $values = get_group_entities($rule['value']);
              $part = generate_query_values($values, ($table == "device" ? "devices.device_id" : $table_type_data['table'].'.'.$entity_type_data['table_fields']['id']), NULL, FALSE);
              break;
            default:
              $part = generate_query_values($rule['value'], $rule['field_quoted'], NULL, FALSE);
              break;
          }
          //print_vars($parts);
          break;
        case 'notin':
          $operator_negative = TRUE;
          switch ($field) {
            case 'group_id':
              $values = get_group_entities($rule['value']);
              $part = generate_query_values($values, ($table == "device" ? "devices.device_id" : $table_type_data['table'].'.'.$entity_type_data['table_fields']['id']), '!=', FALSE);
              break;
            default;
              $part = generate_query_values($rule['value'], $rule['field_quoted'], '!=', FALSE);
              break;
          }
          break;
      }
      // For measured field append measured
      if ($field_measured && strlen($part)) {
        $measured_type      = $entity_attribs[$field]['measured_type'];
        $part = ' (`'.$table_type_data['table'].'`.`'.$entity_type_data['table_fields']['measured_type'] .
                "` = '" . dbEscape($measured_type) . "' AND (" . $part . '))';
        // For negative rule operators append all entities without measured type field
        if ($operator_negative) {
          $part = ' (`'.$table_type_data['table'].'`.`'.$entity_type_data['table_fields']['measured_type'] . '` IS NULL OR' . $part . ')';
        }
      }
      //if ($field_measured) { logfile('groups.log', $part); } /// DEVEL
      if (strlen($part))
      {
        $parts[] = $part;
      }

    }
  }

  $sql = '(' . implode(" " . $rules['condition'], $parts) . ')';

  //print_vars($parts);
  //print_vars($sql);
  //if ($field_measured) { logfile('groups.log', $sql); }
  //logfile('groups.log', $sql); /// DEVEL
  print_debug_vars($sql);

  return $sql;

}

function migrate_assoc_rules($entry)
{

  $entity_type = $entry['entity_type'];

  $ruleset = array();
  $ruleset['condition'] = 'OR';
  $ruleset['valid'] = 'true';

  foreach ($entry['assocs'] as $assoc)
  {

    $x = array();
    $x['condition'] = 'AND';

    $a = array('device' => $assoc['device_attribs'], 'entity' => $assoc['entity_attribs']);

    foreach ($a as $type => $rules)
    {

      foreach ($rules as $rule)
      {

        if ($rule['attrib'] != '*')
        {

          if ($type == 'device' || $entity_type == 'device')
          {
            $def = $GLOBALS['config']['entities'][$type]['attribs'][$rule['attrib']];
          } else {
            $def = $GLOBALS['config']['entities'][$entity_type]['attribs'][$rule['attrib']];
          }

          $e = array();
          $e['id'] = ($type == 'device' ? 'device.' : 'entity.') . $rule['attrib'];
          $e['field'] = $e['id'];
          $e['type'] = $def['type'];
          $e['value'] = $rule['value'];

          switch ($rule['condition']) {
            case 'ge':
            case '>=':
              $e['operator'] = "ge";
              break;
            case 'le':
            case '<=':
              $e['operator'] = "le";
              break;
            case 'gt':
            case 'greater':
            case '>':
              $e['operator'] = "gt";
              break;
            case 'lt':
            case 'less':
            case '<':
              $e['operator'] = "lt";
              break;
            case 'notequals':
            case 'isnot':
            case 'ne':
            case '!=':
              $e['operator'] = "notequals";
              break;
            case 'equals':
            case 'eq':
            case 'is':
            case '==':
            case '=':
              $e['operator'] = "equals";
              break;
            case 'match':
            case 'matches':
              //$e['value'] = str_replace('*', '%', $e['value']);
              //$e['value'] = str_replace('?', '_', $e['value']);
              $e['operator'] = "match";
              break;
            case 'notmatches':
            case 'notmatch':
            case '!match':
              //$e['value'] = str_replace('*', '%', $e['value']);
              //$e['value'] = str_replace('?', '_', $e['value']);
              $e['operator'] = "notmatch";
              break;
            case 'regexp':
            case 'regex':
              $e['operator'] = "regexp";
              break;
            case 'notregexp':
            case 'notregex':
            case '!regexp':
            case '!regex':
              $e['operator'] = "notregexp";
              break;
            case 'in':
            case 'list':
              $e['value'] = explode(',', $e['value']);
              $e['operator'] = "in";
              break;
            case '!in':
            case '!list':
            case 'notin':
            case 'notlist':
              $e['value'] = explode(',', $e['value']);
              $e['operator'] = "notin";
              break;
            case 'include':
            case 'includes':
              switch ($rule['attrib']) {
                case 'group':
                  $e['operator'] = "match";
                  $e['type'] = 'text';
                  break;
                case 'group_id':
                  $e['operator'] = "in";
                  $e['value'] = explode(',', $e['value']);
                  $e['type'] = 'select';
                  break;

              }
              break;
          }

          if (isset($def['values']) &&
              in_array($e['operator'], array("equals", "notequals", "match", "notmatch", "regexp", "notregexp")))
          {
            $e['id'] .= ".free";
          }

          if (in_array($e['operator'], array('in', 'notin')))
          {
            $e['input'] = 'select';
          }
          else if ($def['type'] == 'integer')
          {
            $e['input'] = 'number';
          } else {
            $e['input'] = 'text';
          }

          $x['rules'][] = $e;

        }

      }

    }
    $ruleset['rules'][] = $x;
  }

  // Collapse group if there is only one entry.
  if (count($ruleset['rules']) == 1)
  {
    $ruleset['rules'] = $ruleset['rules'][0]['rules'];
    $ruleset['condition'] = 'AND';
  }

  if (count($ruleset['rules']) < 1)
  {
    $ruleset['rules'][] = array('id' => 'device.hostname', 'field' => 'device.hostname', 'type' => 'string', 'value' => '*', 'operator' => 'match', 'input' => 'text');
  }

  return $ruleset;

}

function render_qb_rules($entity_type, $rules)
{

  $parts = array();

  $entity_type_data = entity_type_translate_array($entity_type);

  foreach ($rules['rules'] as $rule)
  {
    if (is_array($rule['rules']))
    {
      $parts[] = render_qb_rules($entity_type, $rule);

    } else {

      list($table, $field) = explode('.', $rule['field']);

      if ($table == "device")
      {

      } elseif ($table == "entity") {

        $table = $entity_type;

      } elseif ($table == "parent") {

        $table = $entity_type_data['parent_type'];

      }

      // Boolean stored as bool object, can not be displayed
      if ($rule['type'] == 'boolean')
      {
        $rule['value'] = intval($rule['value']);
      }

      $parts[] = "<code style='margin: 1px'>$table.$field " . $rule['operator'] . " " . (is_array($rule['value']) ? implode($rule['value'],
                                                                                                        '|') : $rule['value']) . "</code>";
    }

  }

  $part = implode('' . ($rules['condition'] == "AND" ? ' <span class="label label-primary">AND</span> ' : ' <span class="label label-info">OR</span> ') . '',$parts);

  if(count($parts) > 1)
  {
    $part = '<b style="font-size: 1.2em">(</b>'.$part.'<b>)</b>';
  }


  return $part;
}

// EOF
