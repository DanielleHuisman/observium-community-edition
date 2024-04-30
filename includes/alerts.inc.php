<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage functions
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_contact_by_id($contact_id)
{
    if (is_numeric($contact_id) &&
        $contact = dbFetchRow('SELECT * FROM `alert_contacts` WHERE `contact_id` = ?', [$contact_id])) {

        return $contact;
    }

    return FALSE;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_alert_test_by_id($alert_test_id)
{
    if (is_numeric($alert_test_id) &&
        $alert_test = dbFetchRow('SELECT * FROM `alert_tests` WHERE `alert_test_id` = ?', [$alert_test_id])) {

        return $alert_test;
    }

    return FALSE;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_alert_entry_by_id($id)
{
    return dbFetchRow("SELECT * FROM `alert_table`" .
                      //" LEFT JOIN `alert_table-state` ON  `alert_table`.`alert_table_id` =  `alert_table-state`.`alert_table_id`".
                      " WHERE  `alert_table`.`alert_table_id` = ?", [$id]);
}

/**
 * Check an entity against all relevant alerts
 *
 * @param string $entity_type Entity type
 * @param array  $entity      Entity array
 * @param array  $data        Checked values
 * @param bool   $return      When TRUE, do not update alert entry, just return array
 *
 * @return void|array
 */
// TESTME needs unit testing
function check_entity($entity_type, $entity, $data, $return = FALSE) {
    global $config, $alert_rules, $alert_table, $device;

    //print_vars($entity);
    //print_vars($data);
    //r(array($entity_type, $entity, $data));

    //$alert_output = "";

    $entity_data         = entity_type_translate_array($entity_type);
    $entity_id_field     = $entity_data['id_field'];
    $entity_ignore_field = $entity_data['ignore_field'];

    $entity_id = $entity[$entity_id_field];

    if (!isset($alert_table[$entity_type][$entity_id])) {
        // Just return to avoid PHP warnings
        return;
    }

    // Hardcode time and weekday for global use.
    $data['unixtime'] = time(); // get_time();
    $data['time']     = date('Hi');
    $data['weekday']  = date('N');

    foreach ($alert_table[$entity_type][$entity_id] as $alert_test_id => $alert_args) {
        if ($alert_rules[$alert_test_id]['and']) {
            // ALL conditions
            $alert = TRUE;
        } else {
            // ANY condition
            $alert = FALSE;
        }

        $alert_info = [
          'entity_type'   => $entity_type,
          'entity_id'     => $entity_id,
          'alert_test_id' => $alert_test_id
        ];

        $alert_checker = $alert_rules[$alert_test_id];

        $update_array = [];

        if (is_array($alert_rules[$alert_test_id])) {
            print_debug("Checking alert " . $alert_test_id . " associated by " . $alert_args['alert_assocs'] . "\n");
            //$alert_output .= $alert_rules[$alert_test_id]['alert_name'] . " [";

            foreach ($alert_rules[$alert_test_id]['conditions'] as $test_key => $test) {
                // Replace tagged conditions with entity params from db, ie @sensor_limit
                $test['value'] = get_metric_tagged_value($test['metric'], $test['value'], $entity);

                print_debug("Testing: " . $test['metric'] . " " . $test['condition'] . " " . $test['value']);
                $update_array['state']['metrics'][$test['metric']] = $data[$test['metric']];

                if (array_key_exists($test['metric'], $data)) {
                    if (safe_empty($data[$test['metric']])) {
                        print_debug(" (value empty: '" . (is_null($data[$test['metric']]) ? 'NULL' : $data[$test['metric']]) . "')");
                    } else {
                        print_debug(" (value: " . $data[$test['metric']] . ")");
                    }
                    if (test_condition($data[$test['metric']], $test['condition'], $test['value'])) {
                        // A test has failed. Set the alert variable and make a note of what failed.
                        print_debug("%R[FAIL]%N");
                        $update_array['state']['failed'][] = $test;

                        if ($alert_rules[$alert_test_id]['and']) {
                            // ALL conditions
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
                        print_debug("%G[OK]%N");
                    }
                } else {
                    print_debug("Metric '" . $test['metric'] . "' is not present on entity " . $entity_type . "=$entity_id.\n");
                    if ($alert_rules[$alert_test_id]['and']) {
                        $alert = ($alert && FALSE);
                    } else {
                        $alert = ($alert || FALSE);
                    }
                }
            }

            // json_encode the state array before we put it into MySQL.
            $update_array['state'] = safe_json_encode($update_array['state']);
            $alert_info['state']   = $update_array['state'];
            $last_time             = $data['unixtime'];

            if ($alert) {
                // Check to see if this alert has been suppressed by anything
                ## FIXME -- not all of this is implemented

                $suppressed       = []; // reasons
                $alert_suppressed = FALSE;

                // Have all alerts been suppressed?
                if ($config['alerts']['suppress']) {
                    $alert_suppressed = TRUE;
                    $suppressed['GLOBAL'] = "Global Configuration";
                }

                // Is there a global scheduled maintenance?
                if (safe_count($GLOBALS['cache']['maint']['global']) > 0) {
                    $alert_suppressed = TRUE;
                    $suppressed['MNT_GBL'] = "Global Maintenance";
                }

                // Have all alerts on the device been suppressed?
                if ($device['ignore']) {
                    $alert_suppressed = TRUE;
                    $suppressed['DEV'] = "Device Ignored";
                }

                if ($device['ignore_until']) {
                    $device['ignore_until_time'] = strtotime($device['ignore_until']);
                    if ($device['ignore_until_time'] > $last_time) {
                        $alert_suppressed = TRUE;
                        $suppressed['DEV_U'] = "Device Ignored until ".$device['ignore_until'];
                    }
                }

                if (isset($GLOBALS['cache']['maint'][$entity_type][$entity[$entity_id_field]])) {
                    $alert_suppressed = TRUE;
                    $suppressed['MNT_ENT'] = "Entity Maintenance";
                }

                if (isset($GLOBALS['cache']['maint']['alert_checker'][$alert_test_id])) {
                    $alert_suppressed = TRUE;
                    $suppressed['MNT_CHK'] = "Alert Checker Maintenance";
                }

                if (isset($GLOBALS['cache']['maint']['device'][$device['device_id']])) {
                    $alert_suppressed = TRUE;
                    $suppressed['MNT_DEV'] = "Device Maintenance";
                }

                // Have all alerts on the entity been suppressed?
                if ($entity[$entity_ignore_field]) {
                    $alert_suppressed = TRUE;
                    $suppressed['ENT'] = "Entity Ignored";
                }

                if (is_numeric($entity['ignore_until']) && $entity['ignore_until'] > $last_time) {
                    $alert_suppressed = TRUE;
                    $suppressed['ENT_U'] = "Entity Ignored until " . format_unixtime($entity['ignore_until']);
                }

                // Have alerts from this alerter been suppressed?
                if ($alert_rules[$alert_test_id]['ignore']) {
                    $alert_suppressed = TRUE;
                    $suppressed['CHECK'] = "Alert Checker Ignored";
                }

                if ($alert_rules[$alert_test_id]['ignore_until']) {
                    $alert_rules[$alert_test_id]['ignore_until_time'] = strtotime($alert_rules[$alert_test_id]['ignore_until']);
                    if ($alert_rules[$alert_test_id]['ignore_until_time'] > $last_time) {
                        $alert_suppressed = TRUE;
                        $suppressed['CHECK_UNTIL'] = "Alert Checker Ignored until ".$alert_rules[$alert_test_id]['ignore_until'];
                    }
                }

                // Has this specific alert been suppressed?
                if ($alert_args['ignore']) {
                    $alert_suppressed = TRUE;
                    $suppressed['ENTRY'] = "Alert Ignored";
                }

                if ($alert_args['ignore_until']) {
                    $alert_args['ignore_until_time'] = strtotime($alert_args['ignore_until']);
                    if ($alert_args['ignore_until_time'] > $last_time) {
                        $alert_suppressed = TRUE;
                        $suppressed['ENTRY_UNTIL'] = "Alert Ignored until " . $alert_args['ignore_until'];
                    }
                }

                if (is_numeric($alert_args['ignore_until_ok']) && $alert_args['ignore_until_ok'] == '1') {
                    $alert_suppressed = TRUE;
                    $suppressed['ENTRY_UNTIL_OK'] = "Alert Ignored until OK";
                }

                $update_array['count'] = $alert_args['count'] + 1;
                //$update_array['severity'] = $alert_rules[$alert_test_id]['severity'];

                // Check against the alert test's delay
                if ($alert_args['count'] >= $alert_rules[$alert_test_id]['delay'] && $alert_suppressed) {
                    // This alert is valid, but has been suppressed.
                    //echo(" Checks failed. Alert suppressed (".implode(', ', $suppressed).").\n");
                    //$alert_output .= "%PFS%N";

                    $update_array['alert_status'] = '3';
                    $update_array['last_message'] = 'Checks failed (Suppressed: ' . implode(', ', $suppressed) . ')';
                    $update_array['last_checked'] = $last_time;
                    if ($alert_args['alert_status'] != '3' || $alert_args['last_changed'] == '0') {
                        $update_array['last_changed'] = $last_time;
                        $log_id                       = log_alert('Checks failed but alert suppressed by [' . implode(',', $suppressed) . ']', $device, $alert_info, 'FAIL_SUPPRESSED');
                    }
                    $update_array['last_failed'] = $last_time;

                } elseif ($alert_args['count'] >= $alert_rules[$alert_test_id]['delay']) {
                    // This is a real alert.
                    //echo(" Checks failed. Generate alert.\n");
                    //$alert_output                 .= "%PF!%N";
                    $update_array['alert_status'] = '0';
                    $update_array['last_message'] = 'Checks failed';
                    $update_array['last_checked'] = $last_time;
                    if ($alert_args['alert_status'] != '0' || $alert_args['last_changed'] == '0') {
                        $update_array['last_changed'] = $last_time;
                        $update_array['last_alerted'] = '0';
                        $log_id                       = log_alert('Checks failed', $device, $alert_info, 'FAIL');
                    }
                    $update_array['last_failed'] = $last_time;

                } else {
                    // This is alert needs to exist for longer.
                    //echo(" Checks failed. Delaying alert.\n");
                    //$alert_output                 .= "%OFD%N";
                    $update_array['alert_status'] = '2';
                    $update_array['last_message'] = 'Checks failed (delayed)';
                    $update_array['last_checked'] = $last_time;
                    if ($alert_args['alert_status'] != '2' || $alert_args['last_changed'] == '0') {
                        $update_array['last_changed'] = $last_time;
                        $log_id                       = log_alert('Checks failed but alert delayed', $device, $alert_info, 'FAIL_DELAYED');
                    }
                    $update_array['last_failed'] = $last_time;
                }

            } else {
                $update_array['count'] = 0;
                // Alert conditions passed. Record that we tested it and update status and other data.
                //$alert_output                 .= "%gOK%N";
                $update_array['alert_status'] = '1';
                $update_array['last_message'] = 'Checks OK';
                $update_array['last_checked'] = $last_time;
                if ($alert_args['alert_status'] != '1' || $alert_args['last_changed'] == '0') {
                    $update_array['last_changed'] = $last_time;
                    $log_id                       = log_alert('Checks succeeded', $device, $alert_info, 'OK');
                }
                $update_array['last_ok'] = $last_time;

                // Status is OK, so disable ignore_until_ok if it has been enabled
                if ($alert_args['ignore_until_ok'] != '0') {
                    $update_entry_array['ignore_until_ok'] = '0';
                }
            }

            unset($suppressed, $alert_suppressed);

            #$update_array['alert_table_id'] = $alert_args['alert_table_id'];

            /// Perhaps this is better done with SQL replace?
            #print_vars($alert_args);
            //if (!$alert_args['state_entry'])
            //{
            // State entry seems to be missing. Insert it before we update it.
            //dbInsert(array('alert_table_id' => $alert_args['alert_table_id']), 'alert_table-state');
            // echo("I+");
            //}

            print_debug_vars($alert_args);
            print_debug_vars($update_array);

            if ($return) {
                // Do not make any DB/rrd updates, only return updates array
                return ['info' => $alert_info, 'data' => $data, 'update' => $update_array];
            }

            // Multiupdate
            $multi_row = ['alert_table_id' => $alert_args['alert_table_id']];
            $changed   = FALSE;
            foreach (['count', 'state', 'alert_status', 'last_message', 'last_changed',
                      'last_checked', 'last_ok', 'last_failed', 'last_alerted'] as $field) {
                if (isset($update_array[$field])) {
                    // Updated
                    $multi_row[$field] = $update_array[$field];
                } else {
                    // Previous
                    $multi_row[$field] = $alert_args[$field];
                }
                if (!in_array($field, ['last_checked', 'last_ok'])) {
                    $changed = $changed || ($multi_row[$field] != $alert_args[$field]);
                }
                if (is_null($multi_row[$field])) {
                    // keep as NULL
                    $multi_row[$field] = ['NULL'];
                }
            }
            // This store update entry for multi insert latter
            if (!$config['alerts']['reduce_db_updates']) {
                // Full update (default)
                dbUpdateRowMulti($multi_row, 'alert_table', 'alert_table_id');
            } elseif ($changed) {
                // Reduced update queries (only alerted entries)
                dbUpdateRowMulti($multi_row, 'alert_table', 'alert_table_id');
                //dbUpdateRowMulti($multi_row, 'alert_table_test', 'alert_table_id');
            }

            //dbUpdate($update_array, 'alert_table', '`alert_table_id` = ?', array($alert_args['alert_table_id']));
            /// FIXME. $update_entry_array not initialised, can be incorrect update results
            if (is_array($update_entry_array)) {
                dbUpdate($update_entry_array, 'alert_table', '`alert_table_id` = ?', [$alert_args['alert_table_id']]);
            }

            if (TRUE) {
                // Write RRD data

                if ($update_array['alert_status'] == "1") {
                    // Status is up
                    rrdtool_update_ng($device, 'alert', ['status' => 1, 'code' => $update_array['alert_status']], $alert_args);
                } else {
                    rrdtool_update_ng($device, 'alert', ['status' => 0, 'code' => $update_array['alert_status']], $alert_args);
                }
            }

        } else {
            //$alert_output .= "%RAlert missing!%N";
        }

        //$alert_output .= ("] ");
    }

    //$alert_output .= "%n";

    /*
    if ($entity_type == "device") {
      $cli_level = 1;
    } else {
      $cli_level = 3;
    }

    print_cli_data("Checked Alerts", $alert_output, $cli_level);
    */
}

/**
 * Replace tagged metric value with entity params from db.
 * Ie @sensor_limit or magic metric @previous return entity value from db (as previous value)
 *
 * @param string $metric
 * @param mixed $value
 * @param array $entity
 * @return mixed
 */
function get_metric_tagged_value($metric, $value, $entity) {
    // Replace tagged conditions with entity params from db, ie @sensor_limit
    if (str_starts_with($value, '@')) {
        $ent_val = substr($value, 1);
        print_debug("DEBUG get_metric_tagged_value(): ");
        if ($ent_val === 'previous') {
            // get entity value from previous polling
            $alt_metric = explode('_', $metric, 2)[1]; // device_status_type -> status_type
            if (isset($entity[$metric])) {
                $value = $entity[$metric];
                print_debug_vars($entity[$metric]);
            } elseif (isset($entity[$alt_metric])) {
                $value = $entity[$alt_metric];
                print_debug_vars($entity[$alt_metric]);
            } else {
                // remove tagged value for prevent false alerts
                $value = NULL;
            }
        //} elseif ($ent_val === 'difference' || $ent_val === 'diff') {
        } else {
            $value = $entity[$ent_val];
            print_debug_vars($entity[$ent_val]);
        }

        print_debug(" replaced $metric value @" . $ent_val . " with " . $value . " from entity. ");
        print_debug_vars($entity);
    }
    return $value;
}

/**
 * Build an array of conditions that apply to a supplied device
 *
 * This takes the array of global conditions and removes associations that don't match the supplied device array
 *
 * @param array $device device
 *
 * @return array
 */
// TESTME needs unit testing
function cache_device_conditions($device)
{

    // Return no conditions if the device is ignored or disabled.
    if ($device['ignore'] == 1 || $device['disabled'] == 1) {
        return [];
    }

    $conditions = cache_conditions();
    $cond_new   = [];

    foreach ($conditions['assoc'] as $assoc_key => $assoc) {
        if (match_device($device, $assoc['device_attribs'])) {
            //$assoc['alert_test_id'];
            $conditions['cond'][$assoc['alert_test_id']]['assoc'][$assoc_key] = $conditions['assoc'][$assoc_key];
            $cond_new['cond'][$assoc['alert_test_id']]                        = $conditions['cond'][$assoc['alert_test_id']];
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
 * @param integer|string $device_id
 *
 * @return array
 */
// TESTME needs unit testing
function cache_device_alert_table($device_id)
{
    $alert_table = [];

    $sql = "SELECT * FROM  `alert_table`";
    //$sql .= " LEFT JOIN `alert_table-state` USING(`alert_table_id`)";
    $sql .= " WHERE `device_id` = ?";

    foreach (dbFetchRows($sql, [$device_id]) as $entry) {
        $alert_table[$entry['entity_type']][$entry['entity_id']][$entry['alert_test_id']] = $entry;
    }

    return $alert_table;
}

// Wrapper function to loop all groups and trigger rebuild for each.

function update_alert_tables($silent = TRUE)
{

    // System with multiple pollers need to check if update not run on other system

    $alerts = cache_alert_rules();
    $assocs = cache_alert_assoc();

    // Populate associations table into alerts array for legacy association styles
    foreach ($assocs as $assoc) {
        $alerts[$assoc['alert_test_id']]['assocs'][] = $assoc;
    }

    foreach ($alerts as $alert) {
        update_alert_table($alert, $silent);
    }
}

// Regenerate Alert Table
function update_alert_table($alert, $silent = TRUE)
{

    if (is_numeric($alert)) { // We got an alert_test_id, fetch the array.
        $alert = dbFetchRow("SELECT * FROM `alert_tests` WHERE `alert_test_id` = ?", [$alert]);
    }

    if (!safe_empty($alert['alert_assoc'])) {

        $query = parse_qb_ruleset($alert['entity_type'], safe_json_decode($alert['alert_assoc']));

        //r($query);

        $data = dbFetchRows($query);
        //$data  = dbFetchRows($query, NULL, 'log');
        //$error = dbError();
        $entities = [];

        $field = $GLOBALS['config']['entities'][$alert['entity_type']]['table_fields']['id'];

        foreach ($data as $datum) {
            $entities[$datum[$field]] = ['entity_id' => $datum[$field], 'device_id' => $datum['device_id']];
        }

    } else {
        $entities = get_alert_entities_from_assocs($alert);
    }
    //r($entities);

    //$field = $config['entities'][$alert['entity_type']]['table_fields']['id'];

    $existing_entities = get_alert_entities($alert['alert_test_id']);

    $broken = [];
    foreach ($existing_entities as $id => $entry) {
        if (isset($entities[$id]) && $entry['device_id'] != $entities[$id]['device_id']) {
            //print_vars($existing_entities[$id]);
            $broken[$id] = $entry;
            unset($existing_entities[$id]);
        }
    }
    //print_vars($existing_entities);
    //print_vars($entities);

    $add    = array_diff_key($entities, (array)$existing_entities);
    $remove = array_diff_key((array)$existing_entities, $entities);
    print_debug_vars($add);
    print_debug_vars($remove);
    if ($broken_count = safe_count($broken)) {
        print_debug_vars($broken);
        print_debug("Found $broken_count broken entries. Remove..");
        $remove = array_merge($remove, $broken);
    }

    if (!$silent) {
        if (is_cli()) {
            print_cli("Alert Checker " . str_pad("'{$alert['alert_name']}'", 30, ' ', STR_PAD_LEFT) . ": " . safe_count($existing_entities) . " existing, " . safe_count($entities) . " new entries (+" . safe_count($add) . "/-" . safe_count($remove) . ").\n");
        } else {
            print_message(safe_count($existing_entities) . " existing entries.<br />" .
                          safe_count($entities) . " new entries.<br />" .
                          "(+" . safe_count($add) . "/-" . safe_count($remove) . ")<br />");
        }
    }

    // Multi-delete (before insert)
    $db_multi_delete = [];
    foreach ($remove as $entity_id => $entity) {
        //dbDelete('alert_table', 'alert_test_id = ? AND entity_id = ?', [$alert['alert_test_id'], $entity_id]);
        $db_multi_delete[] = $entity['alert_table_id'];
    }
    if (!empty($db_multi_delete)) {
        dbDelete('alert_table', generate_query_values($db_multi_delete, 'alert_table_id'));
    }

    $db_multi_insert = [];
    foreach ($add as $entity_id => $entity) {
        $db_multi_insert[] = [ 'device_id' => $entity['device_id'], 'entity_type' => $alert['entity_type'],
                               'entity_id' => $entity_id, 'alert_test_id' => $alert['alert_test_id'] ];
    }
    // Multi insert
    if (!empty($db_multi_insert)) {
        dbInsertMulti($db_multi_insert, 'alert_table');
    }

    if (!$silent && !is_cli()) {
        print_message("Alert Checker " . $alert['alert_name'] . " regenerated", 'information');
    }
}


/**
 * Build an array of all alert rules
 *
 * @return array
 */
// TESTME needs unit testing
function cache_alert_rules($vars = [])
{
    $alert_rules = [];
    $rules_count = 0;
    $where       = 'WHERE 1';
    $args        = [];

    if (isset($vars['entity_type']) && $vars['entity_type'] !== "all") {
        $where  .= ' AND `entity_type` = ?';
        $args[] = $vars['entity_type'];
    }

    foreach (dbFetchRows("SELECT * FROM `alert_tests` " . $where, $args) as $entry) {
        if ($entry['alerter'] == '') {
            $entry['alerter'] = "default";
        }
        $alert_rules[$entry['alert_test_id']]               = $entry;
        $alert_rules[$entry['alert_test_id']]['conditions'] = safe_json_decode($entry['conditions']);
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
        $a      = $config['alerts']['alerter'][$alerter];
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
    $alert_assoc = [];

    foreach (dbFetchRows("SELECT * FROM `alert_assoc`") as $entry) {
        $entity_attribs                                          = safe_json_decode($entry['entity_attribs']);
        $device_attribs                                          = safe_json_decode($entry['device_attribs']);
        $alert_assoc[$entry['alert_assoc_id']]['entity_type']    = $entry['entity_type'];
        $alert_assoc[$entry['alert_assoc_id']]['entity_attribs'] = $entity_attribs;
        $alert_assoc[$entry['alert_assoc_id']]['device_attribs'] = $device_attribs;
        $alert_assoc[$entry['alert_assoc_id']]['alert_test_id']  = $entry['alert_test_id'];
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

    $return = [];
    $now    = time();

    $maints = dbFetchRows("SELECT * FROM `alerts_maint` WHERE `maint_start` < ? AND `maint_end` > ?", [$now, $now]);

    if (is_array($maints) && count($maints)) {

        $return['count'] = count($maints);

        foreach ($maints as $maint) {
            if ($maint['maint_global'] == 1) {
                $return['global'][$maint['maint_id']] = $maint;
            } else {

                $assocs = dbFetchRows("SELECT * FROM `alerts_maint_assoc` WHERE `maint_id` = ?", [$maint['maint_id']]);

                foreach ($assocs as $assoc) {
                    switch ($assoc['entity_type']) {
                        case "group": // this is a group, so expand it's members into an array
                            $group    = get_group_by_id($assoc['entity_id']);
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

function get_alert_entities($ids) {
    if (safe_empty($ids)) {
        return [];
    }

    $array = [];
    foreach (dbFetchRows("SELECT `alert_table_id`, `entity_id`, `device_id` FROM `alert_table` WHERE " . generate_query_values($ids, 'alert_test_id')) as $entry) {
        $array[$entry['entity_id']] = [
            'entity_id'      => $entry['entity_id'],
            'device_id'      => $entry['device_id'],
            'alert_table_id' => $entry['alert_table_id']
        ];
    }

    return $array;
}


function get_maintenance_associations($maint_id = NULL)
{
    $return = [];

    if ($maint_id) {
        $assocs = dbFetchRows("SELECT * FROM `alerts_maint_assoc` WHERE `maint_id` = ?", [$maint_id]);
    } else {
        $assocs = dbFetchRows("SELECT * FROM `alerts_maint_assoc`");
    }

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
    $cache = [];

    foreach (dbFetchRows("SELECT * FROM `alert_tests`") as $entry) {
        $cache['cond'][$entry['alert_test_id']]                = $entry;
        $conditions                                            = safe_json_decode($entry['conditions']);
        $cache['cond'][$entry['alert_test_id']]['entity_type'] = $entry['entity_type'];
        $cache['cond'][$entry['alert_test_id']]['conditions']  = $conditions;
    }

    foreach (dbFetchRows("SELECT * FROM `alert_assoc`") as $entry) {
        $entity_attribs                                             = safe_json_decode($entry['entity_attribs']);
        $device_attribs                                             = safe_json_decode($entry['device_attribs']);
        $cache['assoc'][$entry['alert_assoc_id']]                   = $entry;
        $cache['assoc'][$entry['alert_assoc_id']]['entity_attribs'] = $entity_attribs;
        $cache['assoc'][$entry['alert_assoc_id']]['device_attribs'] = $device_attribs;
    }

    return $cache;
}

/**
 * Compare two values
 *
 * @param string       $value_a
 * @param string       $condition
 * @param string|array $value_b
 *
 * @return boolean
 */
function test_condition($value_a, $condition, $value_b)
{

    // Clean values
    if (is_string($value_a)) {
        $value_a = trim($value_a);
    }
    if (is_string($value_b)) {
        $value_b = trim($value_b);
    }

    // Condition & delimiters
    $is_numeric_a = is_numeric($value_a);
    $is_numeric   = $is_numeric_a && is_numeric($value_b);
    $condition    = strtolower($condition);
    $delimiters   = ['/', '!', '@'];
    // numeric oid patterns (oid b can be partially regex
    $oid_pattern_a = '/^(?:(?<start>\.?)(?:\d+(?:\.\d+)+)|\.\d+)$/';
    //$oid_pattern_b = '/^(?:(?<start>\.?)(?:\d+(?:\.\d+)+)|\.\d+)(?<end>\.)?$/';
    $oid_pattern_b = '/^(?<start>\.?)\d+(?:\.(\d+|[\d\[\]\-]+|[\d\(\)\|]+|[\d\*]+))*(?<end>\.)?$/';

    switch ($condition) {
        case 'isnull':
        case 'null':
            $result = is_null($value_a);
            break;
        case 'notnull':
        case '!null':
            $result = !is_null($value_a);
            break;
        case 'ge':
        case '>=':
            if ($is_numeric) {
                $result = $value_a >= unit_string_to_numeric($value_b);
            } elseif ($is_numeric_a && safe_empty($value_b)) {
                // In case when use empty sensor_limit for compare, see: OBSENT-100
                $result = FALSE;
            } else {
                $result = strnatcmp($value_a, unit_string_to_numeric($value_b)) >= 0;
            }
            break;
        case 'le':
        case '<=':
            if ($is_numeric) {
                $result = $value_a <= unit_string_to_numeric($value_b);
            } elseif ($is_numeric_a && safe_empty($value_b)) {
                // In case when use empty sensor_limit for compare, see: OBSENT-100
                $result = FALSE;
            } else {
                $result = strnatcmp($value_a, unit_string_to_numeric($value_b)) <= 0;
            }
            break;
        case 'gt':
        case 'greater':
        case '>':
            if ($is_numeric) {
                $result = $value_a > unit_string_to_numeric($value_b);
            } elseif ($is_numeric_a && safe_empty($value_b)) {
                // In case when use empty sensor_limit for compare, see: OBSENT-100
                $result = FALSE;
            } else {
                $result = strnatcmp($value_a, unit_string_to_numeric($value_b)) > 0;
            }
            break;
        case 'lt':
        case 'less':
        case '<':
            if ($is_numeric) {
                $result = $value_a < unit_string_to_numeric($value_b);
            } elseif ($is_numeric_a && safe_empty($value_b)) {
                // In case when use empty sensor_limit for compare, see: OBSENT-100
                $result = FALSE;
            } else {
                $result = strnatcmp($value_a, unit_string_to_numeric($value_b)) < 0;
            }
            break;
        case 'notequals':
        case 'isnot':
        case 'ne':
        case '!=':
            if ($is_numeric) {
                $result = $value_a != unit_string_to_numeric($value_b);
            } elseif ($is_numeric_a && safe_empty($value_b)) {
                // In case when use empty sensor_limit for compare, see: OBSENT-100
                // FIXME. Not sure, logically this correct
                $result = TRUE;
            } else {
                $result = strnatcmp($value_a, unit_string_to_numeric($value_b)) != 0;
            }
            break;
        case 'equals':
        case 'eq':
        case 'is':
        case '==':
        case '=':
            if ($is_numeric) {
                $result = $value_a == unit_string_to_numeric($value_b);
            } elseif ($is_numeric_a && safe_empty($value_b)) {
                // In case when use empty sensor_limit for compare, see: OBSENT-100
                $result = FALSE;
            } else {
                $result = strnatcmp($value_a, unit_string_to_numeric($value_b)) == 0;
            }
            break;
        case 'match':
        case 'matches':
            // Numeric OID matches
            if (!$is_numeric && preg_match($oid_pattern_a, $value_a) &&
                (is_array($value_b) || preg_match($oid_pattern_b, $value_b))) {
                $result = match_oid_num($value_a, $value_b);
                break;
            }

            $value_b = str_replace(['*', '?'], ['.*', '.'], $value_b);

            // Find suitable delimiter for pattern
            foreach ($delimiters as $delimiter) {
                if (!str_contains($value_b, $delimiter)) {
                    break;
                }
            }
            if (preg_match($delimiter . '^' . $value_b . '$' . $delimiter, $value_a)) {
                $result = TRUE;
            } else {
                $result = FALSE;
            }
            break;
        case 'notmatches':
        case 'notmatch':
        case '!match':
            // Numeric OID matches
            if (!$is_numeric && preg_match('/^(?:(?<start>\.?)(?:\d+(?:\.\d+)+)|\.\d+)$/', $value_a) &&
                (is_array($value_b) || preg_match('/^(?:(?<start>\.?)(?:\d+(?:\.\d+)+)|\.\d+)(?<end>\.)?$/', $value_b))) {
                $result = !match_oid_num($value_a, $value_b);
                break;
            }

            $value_b = str_replace(['*', '?'], ['.*', '.'], $value_b);

            // Find suitable delimiter for pattern
            foreach ($delimiters as $delimiter) {
                if (!str_contains($value_b, $delimiter)) {
                    break;
                }
            }
            if (preg_match($delimiter . '^' . $value_b . '$' . $delimiter, $value_a)) {
                $result = FALSE;
            } else {
                $result = TRUE;
            }
            break;
        case 'regexp':
        case 'regex':
            // Find suitable delimiter for pattern
            foreach ($delimiters as $delimiter) {
                if (!str_contains($value_b, $delimiter)) {
                    break;
                }
            }
            if (preg_match($delimiter . $value_b . $delimiter, $value_a)) {
                $result = TRUE;
            } else {
                $result = FALSE;
            }
            break;
        case 'notregexp':
        case 'notregex':
        case '!regexp':
        case '!regex':
            // Find suitable delimiter for pattern
            foreach ($delimiters as $delimiter) {
                if (!str_contains($value_b, $delimiter)) {
                    break;
                }
            }
            if (preg_match($delimiter . $value_b . $delimiter, $value_a)) {
                $result = FALSE;
            } else {
                $result = TRUE;
            }
            break;
        case 'in':
        case 'list':
            if (!is_array($value_b)) {
                $value_b = array_map('trim', explode(',', $value_b));
            }
            foreach ($value_b as $value) {
                if ((string)$value === (string)$value_a) { // NOTE. php before 8.x: 'string' == 0 => true
                    $result = TRUE;
                    break 2;
                }
            }
            $result = FALSE;
            // in_array doesn't seem to behave how one would expect
            //$result = in_array($value_a, $value_b);
            break;

        case '!in':
        case '!list':
        case 'notin':
        case 'notlist':
            if (!is_array($value_b)) {
                $value_b = array_map('trim', explode(',', $value_b));
            }
            foreach ($value_b as $value) {
                if ((string)$value === (string)$value_a) { // NOTE. php before 8.x: 'string' == 0 => true
                    $result = FALSE;
                    break 2; // break out of foreach loop and which
                }
            }
            $result = TRUE;
            // in_array doesn't seem to behave how one would expect
            //$result = !in_array($value_a, $value_b);
            break;

        case 'between':
        case 'notbetween':
        case '!between':
            $result = FALSE;

            if (!is_array($value_b)) {
                $value_b = array_map('trim', explode(',', $value_b));
            } // perhaps extend to allow space-separated values

            if (isset($value_b[0], $value_b[1]) && is_numeric($value_b[0]) && is_numeric($value_b[1])) {
                $result = ($condition === "between")
                  ? ($value_a > $value_b[0]) && ($value_a < $value_b[1])
                  : ($value_a < $value_b[0]) || ($value_a > $value_b[1]);
            } else {
                print_debug("ERROR: Invalid values passed to 'between' operator in test_condition().");
            }
            break;

        default:
            print_debug("ERROR: Unknown condition '$condition' passed to test_condition().");
            $result = FALSE;
            break;
    }

    if ($result) {
        print_debug("TRUE");
    } else {
        print_debug("NOT TRUE");
    }

    return $result;
}

/**
 * Test if a device matches a set of attributes
 * Matches using the database entry for the supplied device_id
 *
 * @param array device
 * @param array attributes
 *
 * @return boolean
 */
// TESTME needs unit testing
function match_device($device, $attributes, $ignore = TRUE)
{
    // Short circuit this check if the device is either disabled or ignored.
    if ($ignore && ($device['disable'] == 1 || $device['ignore'] == 1)) {
        return FALSE;
    }

    $query  = "SELECT COUNT(*) FROM `devices` AS d";
    $join   = "";
    $where  = " WHERE d.`device_id` = ?";
    $params = [$device['device_id']];

    foreach ($attributes as $attrib) {
        switch ($attrib['condition']) {
            case 'ge':
            case '>=':
                $where    .= ' AND d.`' . $attrib['attrib'] . '` >= ?';
                $params[] = $attrib['value'];
                break;
            case 'le':
            case '<=':
                $where    .= ' AND d.`' . $attrib['attrib'] . '` <= ?';
                $params[] = $attrib['value'];
                break;
            case 'gt':
            case 'greater':
            case '>':
                $where    .= ' AND d.`' . $attrib['attrib'] . '` > ?';
                $params[] = $attrib['value'];
                break;
            case 'lt':
            case 'less':
            case '<':
                $where    .= ' AND d.`' . $attrib['attrib'] . '` < ?';
                $params[] = $attrib['value'];
                break;
            case 'notequals':
            case 'isnot':
            case 'ne':
            case '!=':
                $where    .= ' AND d.`' . $attrib['attrib'] . '` != ?';
                $params[] = $attrib['value'];
                break;
            case 'equals':
            case 'eq':
            case 'is':
            case '==':
            case '=':
                $where    .= ' AND d.`' . $attrib['attrib'] . '` = ?';
                $params[] = $attrib['value'];
                break;
            case 'match':
            case 'matches':
                $attrib['value'] = str_replace('*', '%', $attrib['value']);
                $attrib['value'] = str_replace('?', '_', $attrib['value']);
                $where           .= ' AND IFNULL(d.`' . $attrib['attrib'] . '`, "") LIKE ?';
                $params[]        = $attrib['value'];
                break;
            case 'notmatches':
            case 'notmatch':
            case '!match':
                $attrib['value'] = str_replace('*', '%', $attrib['value']);
                $attrib['value'] = str_replace('?', '_', $attrib['value']);
                $where           .= ' AND IFNULL(d.`' . $attrib['attrib'] . '`, "") NOT LIKE ?';
                $params[]        = $attrib['value'];
                break;
            case 'regexp':
            case 'regex':
                $where    .= ' AND IFNULL(d.`' . $attrib['attrib'] . '`, "") REGEXP ?';
                $params[] = $attrib['value'];
                break;
            case 'notregexp':
            case 'notregex':
            case '!regexp':
            case '!regex':
                $where    .= ' AND IFNULL(d.`' . $attrib['attrib'] . '`, "") NOT REGEXP ?';
                $params[] = $attrib['value'];
                break;
            case 'in':
            case 'list':
                $where .= generate_query_values_and(explode(',', $attrib['value']), 'd.' . $attrib['attrib']);
                break;
            case '!in':
            case '!list':
            case 'notin':
            case 'notlist':
                $where .= generate_query_values_and(explode(',', $attrib['value']), 'd.' . $attrib['attrib'], '!=');
                break;
            case 'include':
            case 'includes':
                switch ($attrib['attrib']) {
                    case 'group':
                        $join     .= " INNER JOIN `group_table` USING(`device_id`)";
                        $join     .= " INNER JOIN `groups`      USING(`group_id`)";
                        $where    .= " AND `group_name` = ?";
                        $params[] = $attrib['value'];
                        break;
                    case 'group_id':
                        $join     .= " INNER JOIN `group_table` USING(`device_id`)";
                        $where    .= " AND `group_id` = ?";
                        $params[] = $attrib['value'];
                        break;

                }
                break;
        }
    }

    $query        .= $join . $where;
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
 *
 * @return array
 */
// TESTME needs unit testing
function match_device_entities($device_id, $entity_attribs, $entity_type)
{
    // FIXME - this is going to be horribly slow.

    $e_type      = $entity_type;
    $entity_type = entity_type_translate_array($entity_type);

    if (!is_array($entity_type)) {
        return NULL;
    } // Do nothing if entity type unknown

    $param = [];
    $sql   = "SELECT * FROM `" . dbEscape($entity_type['table']) . "`"; // FIXME. Not sure why these required escape table name

    if (isset($entity_type['parent_table']) && isset($entity_type['parent_id_field'])) {
        $sql .= ' LEFT JOIN `' . $entity_type['parent_table'] . '` USING (`' . $entity_type['parent_id_field'] . '`)';
    }

    $sql .= " WHERE `" . dbEscape($entity_type['table']) . "`.device_id = ?";

    if (isset($entity_type['where'])) {
        $sql .= ' AND ' . $entity_type['where'];
    }

    $param[] = $device_id;

    if (isset($entity_type['deleted_field'])) {
        $sql     .= " AND `" . $entity_type['deleted_field'] . "` != ?";
        $param[] = '1';
    }

    foreach ($entity_attribs as $attrib) {
        switch ($attrib['condition']) {
            case 'ge':
            case '>=':
                $sql     .= ' AND `' . $attrib['attrib'] . '` >= ?';
                $param[] = $attrib['value'];
                break;
            case 'le':
            case '<=':
                $sql     .= ' AND `' . $attrib['attrib'] . '` <= ?';
                $param[] = $attrib['value'];
                break;
            case 'gt':
            case 'greater':
            case '>':
                $sql     .= ' AND `' . $attrib['attrib'] . '` > ?';
                $param[] = $attrib['value'];
                break;
            case 'lt':
            case 'less':
            case '<':
                $sql     .= ' AND `' . $attrib['attrib'] . '` < ?';
                $param[] = $attrib['value'];
                break;
            case 'notequals':
            case 'isnot':
            case 'ne':
            case '!=':
                $sql     .= ' AND `' . $attrib['attrib'] . '` != ?';
                $param[] = $attrib['value'];
                break;
            case 'equals':
            case 'eq':
            case 'is':
            case '==':
            case '=':
                $sql     .= ' AND `' . $attrib['attrib'] . '` = ?';
                $param[] = $attrib['value'];
                break;
            case 'match':
            case 'matches':
                $attrib['value'] = str_replace('*', '%', $attrib['value']);
                $attrib['value'] = str_replace('?', '_', $attrib['value']);
                $sql             .= ' AND IFNULL(`' . $attrib['attrib'] . '`, "") LIKE ?';
                $param[]         = $attrib['value'];
                break;
            case 'notmatches':
            case 'notmatch':
            case '!match':
                $attrib['value'] = str_replace('*', '%', $attrib['value']);
                $attrib['value'] = str_replace('?', '_', $attrib['value']);
                $sql             .= ' AND IFNULL(`' . $attrib['attrib'] . '`, "") NOT LIKE ?';
                $param[]         = $attrib['value'];
                break;
            case 'regexp':
            case 'regex':
                $sql     .= ' AND IFNULL(`' . $attrib['attrib'] . '`, "") REGEXP ?';
                $param[] = $attrib['value'];
                break;
            case 'notregexp':
            case 'notregex':
            case '!regexp':
            case '!regex':
                $sql     .= ' AND IFNULL(`' . $attrib['attrib'] . '`, "") NOT REGEXP ?';
                $param[] = $attrib['value'];
                break;
            case 'in':
            case 'list':
                $sql .= generate_query_values_and(explode(',', $attrib['value']), $attrib['attrib'], NULL, ['ifnull']);
                break;
            case '!in':
            case '!list':
            case 'notin':
            case 'notlist':
                $sql .= generate_query_values_and(explode(',', $attrib['value']), $attrib['attrib'], '!=', ['ifnull']);
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
                        $sql    .= generate_query_values_and($values, $entity_type['table_fields']['id']);
                        break;
                }
        }
    }

    // print_vars(array($sql, $param));
    //logfile('alerts.log', $sql);
    //logfile('alerts.log', var_export($param, TRUE));

    return dbFetchRows($sql, $param);
}

/**
 * Test if an entity matches a set of attributes
 * Uses a supplied device array for matching.
 *
 * @param array entity
 * @param array attributes
 *
 * @return boolean
 */
// TESTME needs unit testing
function match_entity($entity, $entity_attribs)
{
    // FIXME. Never used, deprecated?
    #print_vars($entity);
    #print_vars($entity_attribs);

    $failed     = 0;
    $success    = 0;
    $delimiters = ['/', '!', '@'];

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
// CLEANME
/* not used anymore
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
                if (isset($dbc[$entity_type][$entity_id][$alert_test_id]))
                {
                    if ($dbc[$entity_type][$entity_id][$alert_test_id]['alert_assocs'] != implode(",", $b)) {
                        $update_array = array('alert_assocs' => implode(",", $b));
                    }
                    #echo("[".$dbc[$entity_type][$entity_id][$alert_test_id]['alert_assocs']."][".implode($b,",")."]");
                    if (is_array($update_array)) {
                        dbUpdate($update_array, 'alert_table', '`alert_table_id` = ?', array($dbc[$entity_type][$entity_id][$alert_test_id]['alert_table_id']));
                        unset($update_array);
                    }
                    unset($dbc[$entity_type][$entity_id][$alert_test_id]);
                } else {
                    $alert_table_id = dbInsert(array('device_id' => $device['device_id'], 'entity_type' => $entity_type, 'entity_id' => $entity_id, 'alert_test_id' => $alert_test_id, 'alert_assocs' => implode(",", $b)), 'alert_table');
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
*/

/**
 * Check all alerts for a device to see if they should be notified or not
 *
 * @param array device
 *
 * @return NULL
 */
// TESTME needs unit testing
function process_alerts($device)
{
    global $config, $alert_rules, $alert_assoc;

    $pid_info = check_process_run($device); // This just clear stalled DB entries
    add_process_info($device);              // Store process info

    print_cli_heading($device['hostname'] . " [" . $device['device_id'] . "]", 1);

    $alert_table = cache_device_alert_table($device['device_id']);

    $sql = "SELECT * FROM `alert_table`";
    //$sql .= " LEFT JOIN `alert_table-state` ON `alert_table`.`alert_table_id` = `alert_table-state`.`alert_table_id`";
    $sql .= " WHERE `device_id` = ? AND `alert_status` IS NOT NULL;";

    $db_multi_update = [];

    foreach (dbFetchRows($sql, [$device['device_id']]) as $entry) {
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

            $db_multi_update[] = [
              'alert_table_id' => $entry['alert_table_id'],
              'last_recovered' => time(),
              'last_alerted'   => $entry['last_alerted'], // keep previous
              'has_alerted'    => 0
            ];
            // $update_array['last_recovered'] = time();
            // $update_array['has_alerted'] = 0;
            // dbUpdate($update_array, 'alert_table', '`alert_table_id` = ?', array($entry['alert_table_id']));

        } elseif ($entry['alert_status'] == '0') {
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

                $db_multi_update[] = [
                  'alert_table_id' => $entry['alert_table_id'],
                  'last_recovered' => $entry['last_recovered'], // keep previous
                  'last_alerted'   => time(),
                  'has_alerted'    => 1
                ];
                // $update_array['last_alerted'] = time();
                // $update_array['has_alerted'] = 1;
                // dbUpdate($update_array, 'alert_table', '`alert_table_id` = ?', array($entry['alert_table_id']));

            } else {
                echo("No notification required. " . (time() - $entry['last_alerted']));
            }
        } elseif ($entry['alert_status'] == '1') {
            echo("Status: OK. ");
        } elseif ($entry['alert_status'] == '2') {
            echo("Status: Notification Delayed. ");
        } elseif ($entry['alert_status'] == '3') {
            echo("Status: Notification Suppressed. ");
        } else {
            echo("Unknown status.");
        }
        echo(PHP_EOL);
    }

    if (!empty($db_multi_update)) {
        dbUpdateMulti($db_multi_update, 'alert_table');
    }
    echo(PHP_EOL);
    print_cli_heading($device['hostname'] . " [" . $device['device_id'] . "] completed notifications at " . date("Y-m-d H:i:s"), 1);

    // Clean
    del_process_info($device);              // Remove process info
}

/**
 * Generate notification queue entries for alert system
 *
 * @param array   $entry  Entry
 * @param string  $type   Alert type (alert (default) or syslog)
 * @param integer $log_id Alert log entry ID
 *
 * @return array          List of processed notification ids.
 */
function alert_notifier($entry, $type = "alert", $log_id = NULL)
{

    $device = device_by_id_cache($entry['device_id']);

    $message_tags = alert_generate_tags($entry, $type);

    //logfile('debug.log', var_export($message, TRUE));

    $alert_id = $entry['alert_test_id'];

    $notify_status = FALSE; // Set alert notify status to FALSE by default

    $notification_type = 'alert';
    $contacts          = get_alert_contacts($device, $alert_id, $notification_type);

    $notification_ids = []; // Init list of Notification IDs
    foreach ($contacts as $contact) {

        // Add notification to queue
        $notification              = [
          'device_id'             => $device['device_id'],
          'log_id'                => $log_id,
          'aca_type'              => $notification_type,
          //'severity'              => 6,
          'endpoints'             => safe_json_encode($contact),
          'message_graphs'        => $message_tags['ENTITY_GRAPHS_ARRAY'],
          'notification_added'    => time(),
          'notification_lifetime' => 300,                      // Lifetime in seconds
          'notification_entry'    => safe_json_encode($entry), // Store full alert entry for use later if required (not sure that this needed)
        ];
        $notification_message_tags = $message_tags;
        unset($notification_message_tags['ENTITY_GRAPHS_ARRAY']); // graphs array stored in separate blob column message_graphs, do not duplicate this data
        $notification['message_tags'] = safe_json_encode($notification_message_tags);

        /// DEVEL
        //file_put_contents('/tmp/alert_'.$alert_id.'_'.$message_tags['ALERT_STATE'].'_'.time().'.json', safe_json_encode($notification, JSON_PRETTY_PRINT));

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
    $subject = "$prefix: [" . device_name($device) . ']';

    if ($message_tags['ENTITY_TYPE']) {
        $subject .= ' [' . $message_tags['ENTITY_TYPE'] . ']';
    }
    if ($message_tags['ENTITY_NAME'] && $message_tags['ENTITY_NAME'] != $device['hostname']) {
        $subject .= ' [' . $message_tags['ENTITY_NAME'] . ']';
    }
    $subject .= ' ' . $message_tags['ALERT_MESSAGE'];

    return $subject;
}

function alert_generate_tags($entry, $type = "alert")
{
    global $config, $alert_rules;

    $alert_unixtime = time(); // Store time when alert processed

    $device = device_by_id_cache($entry['device_id']);
    $entity = get_entity_by_id_cache($entry['entity_type'], $entry['entity_id']);
    $alert  = $alert_rules[$entry['alert_test_id']];

    /// DEVEL
    print_debug_vars($entry);
    print_debug_vars($alert);
    print_debug_vars($entity);

    /*
    [conditions] => Array
      (
        [0] => Array
          (
            [metric] => sensor_value
            [condition] => >
            [value] => 30
          )

        [1] => Array
          (
            [metric] => sensor_value
            [condition] => <
            [value] => 2
          )

      )
     */
    //$conditions = json_decode($alert['conditions'], TRUE);

    // [state] => {"metrics":{"sensor_value":45},"failed":[{"metric":"sensor_value","condition":">","value":"30"}]}
    $state = safe_json_decode($entry['state']);
    $entry['metrics'] = $state['metrics']; // passed to alert_generate_graphs()

    $condition_array = [];
    foreach ($state['failed'] as $failed) {
        $condition_array[] = $failed['metric'] . " " . $failed['condition'] . " " . format_value($failed['value']) . " (" . format_value($state['metrics'][$failed['metric']]) . ")";
    }

    $metric_array = [];
    foreach ($state['metrics'] as $metric => $value) {
        $metric_array[] = $metric . ' = ' . $value;
    }

    $graphs = alert_generate_graphs($entry, $entity);

    if (empty($alert['severity'])) {
        $alert['severity'] = 'crit';
    }

    // FIXME. This is how was previously, seems as need something change here?
    $alert_duration = $entry['last_ok'] > 0 ? format_uptime($alert_unixtime - $entry['last_ok']) . " (" . format_unixtime($entry['last_ok']) . ")" : "Unknown";
    if ($entry['alert_status'] == '1') {
        // RECOVER
        $alert_state = 'RECOVER';
        $alert_emoji = 'white_check_mark';
        $alert_color = '';
    } elseif ($entry['has_alerted']) {
        // ALERT REMINDER by $config['alerts']['interval']
        $alert_state = 'ALERT REMINDER';
        $alert_emoji = 'repeat';
        $alert_color = '';
    } else {
        // ALERT (first time)
        $alert_state = 'ALERT';
        $alert_emoji = $config['alerts']['severity'][$alert['severity']]['emoji'];
        $alert_color = $config['alerts']['severity'][$alert['severity']]['color'];
    }
    // Custom alert statuses
    $alert_status_custom = $config['alerts']['status'][$entry['alert_status']] ?? $entry['alert_status'];

    $message_tags = [
      'ALERT_STATE'             => $alert_state,
      'ALERT_EMOJI'             => get_icon_emoji($alert_emoji),               // https://unicodey.com/emoji-data/table.htm
      'ALERT_EMOJI_NAME'        => $alert_emoji,
      'ALERT_STATUS'            => $entry['alert_status'],                     // Tag for templates (0 - ALERT, 1 - RECOVERY, 2 - DELAYED, 3 - SUPPRESSED)
      'ALERT_STATUS_CUSTOM'     => $alert_status_custom,                       // Tag for templates (as defined in $config['alerts']['status'] array)
      //'ALERT_SEVERITY'          => $alert['severity'],                                       // Only: crit(2), warn(4), info(6)
      'ALERT_SEVERITY'          => $config['alerts']['severity'][$alert['severity']]['name'], // Critical, Warning, Informational
      'ALERT_COLOR'             => ltrim($alert_color, '#'),
      'ALERT_URL'               => generate_url(['page'        => 'device',
                                                 'device'      => $device['device_id'],
                                                 'tab'         => 'alert',
                                                 'alert_entry' => $entry['alert_table_id']]),
      'ALERT_UNIXTIME'          => $alert_unixtime,                        // Standard unixtime
      'ALERT_TIMESTAMP'         => date('Y-m-d H:i:s P', $alert_unixtime), //           ie: 2000-12-21 16:01:07 +02:00
      'ALERT_TIMESTAMP_RFC2822' => date('r', $alert_unixtime),             // RFC 2822, ie: Thu, 21 Dec 2000 16:01:07 +0200
      'ALERT_TIMESTAMP_RFC3339' => date(DATE_RFC3339, $alert_unixtime),    // RFC 3339, ie: 2005-08-15T15:52:01+00:00
      'ALERT_ID'                => $entry['alert_table_id'],
      'ALERT_MESSAGE'           => $alert['alert_message'],

      'CONDITIONS'          => implode(PHP_EOL . '             ', $condition_array),
      'METRICS'             => implode(PHP_EOL . '             ', $metric_array),
      'DURATION'            => $alert_duration,

      // Entity TAGs
      'ENTITY_URL'          => generate_entity_url($entry['entity_type'], $entry['entity_id']),
      'ENTITY_LINK'         => generate_entity_link($entry['entity_type'], $entry['entity_id'], $entity['entity_name']),
      'ENTITY_NAME'         => $entity['entity_name'],
      'ENTITY_ID'           => $entity['entity_id'],
      'ENTITY_TYPE'         => $alert['entity_type'],
      'ENTITY_DESCRIPTION'  => $entity['entity_descr'],
      //'ENTITY_GRAPHS'       => $graphs_html,          // Predefined/embedded html images
      'ENTITY_GRAPHS_ARRAY' => safe_json_encode($graphs),  // Json encoded images array

      // Device TAGs
      'DEVICE_HOSTNAME'     => $device['hostname'],
      'DEVICE_SYSNAME'      => $device['sysName'],
      //'DEVICE_SYSDESCR'     => $device['sysDescr'],
      'DEVICE_DESCRIPTION'  => $device['purpose'],
      'DEVICE_ID'           => $device['device_id'],
      'DEVICE_URL'          => generate_device_url($device),
      'DEVICE_LINK'         => generate_device_link($device),
      'DEVICE_HARDWARE'     => $device['hardware'],
      'DEVICE_OS'           => $device['os_text'] . ' ' . $device['version'] . ($device['features'] ? ' (' . $device['features'] . ')' : ''),
      'DEVICE_TYPE'         => $device['type'],
      'DEVICE_LOCATION'     => $device['location'],
      'DEVICE_UPTIME'       => device_uptime($device),
      'DEVICE_REBOOTED'     => format_unixtime($device['last_rebooted']),
    ];

    $message_tags['TITLE'] = alert_generate_subject($device, $alert_state, $message_tags);

    return $message_tags;
}

function alert_generate_graphs($entry, $entity) {
    global $config;

    $graphs     = [];
    $graph_done = [];
    foreach ($entry['metrics'] as $metric => $value) {
        if ($config['email']['graphs'] !== FALSE
            && is_array($config['entities'][$entry['entity_type']]['metric_graphs'][$metric])
            && !in_array($config['entities'][$entry['entity_type']]['metric_graphs'][$metric]['type'], $graph_done)
        ) {
            $graph_array = $config['entities'][$entry['entity_type']]['metric_graphs'][$metric];
            foreach ($graph_array as $key => $val) {
                // Check to see if we need to do any substitution
                if (str_starts($val, '@')) {
                    $nval = substr($val, 1);
                    //echo(" replaced " . $val . " with " . $entity[$nval] . " from entity. " . PHP_EOL . "<br />");
                    $graph_array[$key] = $entity[$nval];
                }
            }

            $image_data_uri = generate_alert_graph($graph_array);
            $image_url      = generate_graph_url($graph_array);

            $graphs[] = [ 'label' => $graph_array['type'], 'type' => $graph_array['type'], 'url' => $image_url, 'data' => $image_data_uri ];

            $graph_done[] = $graph_array['type'];
        }

        unset($graph_array);
    }

    if ($config['email']['graphs'] !== FALSE && empty($graph_done) && is_array($config['entities'][$entry['entity_type']]['graph'])) {
        // We can draw a graph for this type/metric pair!

        $graph_array = $config['entities'][$entry['entity_type']]['graph'];
        foreach ($graph_array as $key => $val) {
            // Check to see if we need to do any substitution
            if (str_starts($val, '@')) {
                $nval = substr($val, 1);
                //echo(" replaced ".$val." with ". $entity[$nval] ." from entity. ".PHP_EOL."<br />");
                $graph_array[$key] = $entity[$nval];
            }
        }

        //print_vars($graph_array);

        $image_data_uri = generate_alert_graph($graph_array);
        $image_url      = generate_graph_url($graph_array);

        $graphs[] = [ 'label' => $graph_array['type'], 'type' => $graph_array['type'], 'url' => $image_url, 'data' => $image_data_uri ];

        unset($graph_array);
    }

    //print_vars($graphs);
    return $graphs;
}

/**
 * Get contacts associated with selected notification type and alert ID
 * Currently know notification types: alert, syslog
 *
 * @param array  $device            Common device array
 * @param int    $alert_id          Alert ID
 * @param string $notification_type Used type for notifications
 *
 * @return array Array with transport -> endpoints lists
 */
function get_alert_contacts($device, $alert_id, $notification_type)
{
    if (!is_array($device)) {
        $device = device_by_id_cache($device);
    }

    $contacts = [];

    if ($device['ignore']) {
        print_error("Device '{$device['hostname']}' set ignored in Device -> Edit -> Settings.");
        return $contacts;
    }
    if ($GLOBALS['config']['alerts']['disable']['all']) {
        print_error("Alert notifications disabled by \$config['alerts']['disable']['all'].");
        return $contacts;
    }
    if (get_dev_attrib($device, 'disable_notify')) {
        print_error("Alert notifications disabled for device '{$device['hostname']}' in Device -> Edit -> Alerts.");
        return $contacts;
    }

    // figure out which transport methods apply to an alert
    $sql = "SELECT * FROM `alert_contacts`";
    $sql .= " WHERE `contact_disabled` = 0 AND `contact_id` IN";
    $sql .= " (SELECT `contact_id` FROM `alert_contacts_assoc` WHERE `aca_type` = ? AND `alert_checker_id` = ?);";

    $syscontact_exist = $GLOBALS['config']['email']['default_syscontact'];
    $syscontact_id    = 0;
    foreach (dbFetchRows($sql, [$notification_type, $alert_id]) as $contact) {
        if ($contact['contact_method'] === 'syscontact') {
            $syscontact_exist = !$contact['contact_disabled'];
            $syscontact_id    = $contact['contact_id'];
            continue;
        }
        $contacts[] = $contact;
    }

    // append syscontact as email transport
    if ($syscontact_exist) {
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

        foreach (parse_email($email) as $email => $descr) {
            $contacts[] = ['contact_endpoint' => '{"email":"' . $email . '"}', 'contact_id' => $syscontact_id, 'contact_descr' => $descr, 'contact_method' => 'email'];
            print_debug("Added contact by device sysContact ($email, $descr).");
        }

    }

    if (empty($contacts) && $GLOBALS['config']['email']['default_only'] &&
        !safe_empty($GLOBALS['config']['email']['default'])) {
        // if alert_contacts table is not in use, fall back to default
        // hardcoded defaults for when there is no contact configured.

        foreach (parse_email($GLOBALS['config']['email']['default']) as $email => $descr) {
            $contacts[] = ['contact_endpoint' => '{"email":"' . $email . '"}', 'contact_id' => '0', 'contact_descr' => $descr, 'contact_method' => 'email'];
            print_debug("Added contact by default email config ($email, $descr).");
        }
    }

    return $contacts;
}

function process_notifications($vars = []) {
    global $config;

    $result = [];
    $where  = [];

    $sql = 'SELECT * FROM `notifications_queue` ';

    foreach ($vars as $var => $value) {
        switch ($var) {
            case 'device_id':
            case 'notification_id':
            case 'aca_type':
                $where[] = generate_query_values($value, $var);
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

    foreach (dbFetchRows($sql . generate_where_clause($where)) as $notification) {

        // Recheck if current notification is locked
        $locked = dbFetchCell('SELECT `notification_locked` FROM `notifications_queue` WHERE `notification_id` = ?', [$notification['notification_id']]); //ALTER TABLE `notifications_queue` ADD `notification_locked` BOOLEAN NOT NULL DEFAULT FALSE AFTER `notification_entry`;
        //if ($locked || $locked === NULL || $locked === FALSE) // If notification not exist or column 'notification_locked' not exist this query return NULL or (possible?) FALSE
        if ($locked || $locked === FALSE) {
            // Notification already processed by other alerter or has already been sent
            print_debug('Notification ID (' . $notification['notification_id'] . ') locked or not exist anymore in table. Skipped.');
            print_debug_vars($notification, 1);
            continue;
        }
        print_debug_vars($notification);

        // Lock current notification
        dbUpdate([ 'notification_locked' => 1 ], 'notifications_queue', '`notification_id` = ?', [$notification['notification_id']]);

        $notification_count = 0;
        $endpoint           = safe_json_decode($notification['endpoints']);

        // If this notification is older than lifetime, unset the endpoints so that it is removed.
        if ((time() - $notification['notification_added']) > $notification['notification_lifetime']) {
            $endpoint = [];
            print_debug('Notification ID (' . $notification['notification_id'] . ') expired.');
            print_debug_vars($notification, 1);
        } else {
            $notification_age      = time() - $notification['notification_added'];
            $notification_timeleft = $notification['notification_lifetime'] - $notification_age;
        }

        $message_tags   = safe_json_decode($notification['message_tags']);
        $message_graphs = safe_json_decode($notification['message_graphs']);
        if (safe_count($message_graphs)) {
            $message_tags['ENTITY_GRAPHS_ARRAY'] = $message_graphs;
            $message_tags['ENTITY_GRAPH_URL']    = $message_graphs[0]['url'];
            $message_tags['ENTITY_GRAPH_BASE64'] = substr($message_graphs[0]['data'], 22); // cut: data:image/png;base64,
            //print_vars($message_tags['ENTITY_GRAPH_URL']);
            //print_vars($message_tags['ENTITY_GRAPH_BASE64']);
        }
        if (isset($message_tags['ALERT_UNIXTIME']) && empty($message_tags['DURATION'])) {
            $message_tags['DURATION'] = format_uptime(time() - $message_tags['ALERT_UNIXTIME']) . ' (' . $message_tags['ALERT_TIMESTAMP'] . ')';
        }

        if (isset($GLOBALS['config']['alerts']['disable'][$endpoint['contact_method']]) &&
            $GLOBALS['config']['alerts']['disable'][$endpoint['contact_method']]) {
            $result[$endpoint['contact_method']] = 'disabled';
            unset($endpoint);
            continue;
        } // Skip if method disabled globally

        $transport      = $endpoint['contact_method']; // Just set transport name for use in includes
        $method_include = $GLOBALS['config']['install_dir'] . '/includes/alerting/' . $transport . '.inc.php';

        $is_transport_def  = isset($config['transports'][$transport]['notification']); // Is definition for transport
        $is_transport_file = is_file($method_include);                                 // Is file based transport
        if ($is_transport_def || $is_transport_file) {

            //print_cli_data("Notifying", "[" . $endpoint['contact_method'] . "] " . $endpoint['contact_descr'] . ": " . $endpoint['contact_endpoint']);
            print_cli_data_field("Notifying");
            echo("[" . $endpoint['contact_method'] . "] " . $endpoint['contact_descr'] . ": " . $endpoint['contact_endpoint']);

            // Split out endpoint data as stored JSON in the database into array for use in transport
            // The original string also remains available as the contact_endpoint key
            foreach (safe_json_decode($endpoint['contact_endpoint']) as $field => $value) {
                $endpoint[$field] = $value;
            }

            // Clean data array for use with definition based processing
            $data    = [];
            $message = [];

            // File based transport
            if ($is_transport_file) {
                print_debug("\nUse file-based notification transport");
                include($method_include);
            }

            // Definition based generate transport options, url and process request
            if ($is_transport_def) {
                print_debug("\nUse definition based notification transport");
                $notification_def = $config['transports'][$transport]['notification'];

                // Pass message tags to request as ARRAY (example in opsgenie)
                if (isset($notification_def['message_tags']) && $notification_def['message_tags']) {
                    $data = array_merge($data, $message_tags);
                    unset($data['ENTITY_GRAPHS_ARRAY']);
                    //print_debug_vars(safe_json_encode($data));
                } elseif (isset($notification_def['message_json'])) {
                    // Pass raw json as $data (example in webhook-json)
                    $json = array_tag_replace(generate_transport_tags($transport, $endpoint, [], [], array_map('json_escape', $message_tags)), $notification_def['message_json']);
                    //print_vars(generate_transport_tags($transport, $endpoint, [], [], array_map('json_escape', $message_tags)));
                    //print_vars($json);
                    $json_array = safe_json_decode($json);
                    //print_vars($json_array);
                    if (!safe_empty($json_array)) {
                        $data = array_merge($data, $json_array);
                    }
                    unset($json, $json_array);
                } else {
                    // Or set common title tag
                    $message['title'] = $message_tags['TITLE'];
                }

                // Generate a notification message from tags using a mustache template system
                if (isset($endpoint['contact_message_custom']) && $endpoint['contact_message_custom'] &&
                    empty(!$endpoint['contact_message_template'])) {
                    // Use user defined template
                    print_debug("User-defined message template is used.");
                    $message['text'] = simple_template($endpoint['contact_message_template'], $message_tags);
                } elseif (isset($notification_def['message_template'])) {
                    print_debug("Definition message template file is used.");
                    // template can have tags (ie telegram)
                    if (str_contains($notification_def['message_template'], '%')) {
                        //print_vars($notification_def['message_template']);
                        $message_template = array_tag_replace_encode(generate_transport_tags($transport, $endpoint), $notification_def['message_template']);
                        $message_template = strtolower($message_template);
                        //print_vars($message_template);
                    } else {
                        $message_template = $notification_def['message_template'];
                    }
                    // Template in file, see: includes/templates/notification/
                    $message['text'] = simple_template($message_template, $message_tags, ['is_file' => TRUE]);
                    //$data['message'] = $message['text'];
                } elseif (isset($notification_def['message_text'])) {
                    print_debug("Definition message template is used.");
                    // Template in definition
                    $message['text'] = simple_template($notification_def['message_text'], $message_tags);
                    //$data['message'] = $message['text'];
                }

                // After all, message transform
                if (isset($notification_def['message_transform']) && $message['text']) {
                    $message['text'] = string_transform($message['text'], $notification_def['message_transform']);
                }

                // Generate transport tags, used for rewrites in definition
                $tags = generate_transport_tags($transport, $endpoint, $data, $message, $message_tags);

                // Generate context/options with encoded data and transport specific api headers
                $options = generate_http_context($transport, $tags, $data);
                // Always get response also with bad status
                $options['ignore_errors'] = TRUE;

                // API URL to POST to
                $url = generate_http_url($transport, $tags, $data);
                $notify_status['success'] = process_http_request($transport, $url, $options);

                // Secondary (fallback) request, example in webhook-json
                // https://jira.observium.org/browse/OBS-4767
                if (isset($notification_def['url1']) && !$notify_status['success']) { // get_http_last_code() === 408
                    $url = generate_http_url($transport, $tags, $data, 'url1');
                    $notify_status['success'] = process_http_request($transport, $url, $options);
                }

                // Clean after transport data and request generation
                unset($message, $color, $url, $data, $options, $tags);
            }

            // FIXME check success
            // FIXME log notification + success/failure!
            if ($notify_status['success']) {
                $result[$transport] = 'ok';
                unset($endpoint);
                $notification_count++;
                print_message(" [%gOK%n]", 'color');
            } else {
                $result[$transport] = 'false';
                print_message(" [%rFALSE%n]", 'color');
                if ($notify_status['error']) {
                    print_cli_data_field('', 4);
                    print_message("[%y" . $notify_status['error'] . "%n]", 'color');
                }
            }
        } else {
            $result[$transport] = 'missing';
            unset($endpoint); // Remove it because it's dumb and doesn't exist. Don't retry it if it doesn't exist.
            print_cli_data("Missing include", $method_include);
        }

        // Remove notification from queue,
        // currently in any case, lifetime, added time and result status is ignored!
        switch ($notification['aca_type']) {
            case 'alert':
                if ($notification_count) {
                    dbUpdate(['notified' => 1], 'alert_log', '`event_id` = ?', [$notification['log_id']]);
                }
                break;

            case 'syslog':
                if ($notification_count) {
                    dbUpdate(['notified' => 1], 'syslog_alerts', '`lal_id` = ?', [$notification['log_id']]);
                }
                break;

            case 'web':
                // Currently not used
                break;
        }

        if (empty($endpoint)) {
            dbDelete('notifications_queue', '`notification_id` = ?', [ $notification['notification_id'] ]);
        } else {
            // Set the endpoints to the remaining un-notified endpoints and unlock the queue entry.
            dbUpdate(['notification_locked' => 0, 'endpoints' => safe_json_encode($endpoint)], 'notifications_queue', '`notification_id` = ?', [$notification['notification_id']]);
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
    $insert = [
      'alert_test_id' => $alert['alert_test_id'],
      'device_id'     => $device['device_id'],
      'entity_type'   => $alert['entity_type'],
      'entity_id'     => $alert['entity_id'],
      'timestamp'     => ["NOW()"],
      //'status'        => $alert['alert_status'],
      'log_type'      => $log_type,
      'message'       => $text
    ];
    if ($alert['state'] && !str_ends($log_type, 'NOTIFY') && get_db_version() >= 479) {
        $insert['log_state'] = $alert['state'];
    }

    return dbInsert($insert, 'alert_log');
}

function threshold_string($alert_low, $warn_low, $warn_high, $alert_high, $symbol = NULL) {
    $format = ''; // FIXME. Not passed

    // Generate "pretty" thresholds
    if (is_numeric($alert_low)) {
        $alert_low_t = format_value($alert_low, $format) . $symbol;
    } else {
        $alert_low_t = "&infin;";
    }

    if (is_numeric($warn_low)) {
        $warn_low_t = format_value($warn_low, $format) . $symbol;
    } else {
        $warn_low_t = NULL;
    }

    if ($warn_low_t) {
        $alert_low_t .= " (" . $warn_low_t . ")";
    }

    if (is_numeric($alert_high)) {
        $alert_high_t = format_value($alert_high, $format) . $symbol;
    } else {
        $alert_high_t = "&infin;";
    }

    if (is_numeric($warn_high)) {
        $warn_high_t = format_value($warn_high, $format) . $symbol;
    } else {
        $warn_high_t = NULL;
    }

    if ($warn_high_t) {
        $alert_high_t = "(" . $warn_high_t . ") " . $alert_high_t;
    }

    return $alert_low_t . ' - ' . $alert_high_t;
}

function check_thresholds($alert_low, $warn_low, $warn_high, $alert_high, $value)
{

    if (!is_numeric($value)) {
        return 'alert';
    } // Not numeric value always alert

    if ((is_numeric($alert_low) && $value <= $alert_low) ||
        (is_numeric($alert_high) && $value >= $alert_high)) {
        $event = 'alert';
    } elseif ((is_numeric($warn_low) && $value < $warn_low) ||
              (is_numeric($warn_high) && $value > $warn_high)) {
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

/**
 * Generate alert transport tags, used for transform any other parts of notification definition.
 *
 * @param string $transport    Alert transport key (see transports definitions)
 * @param array  $tags         (optional) Contact array and other tags
 * @param array  $params       (optional) Array of requested params with key => value entries (used with request method POST)
 * @param array  $message      (optional) Array with some variants of alert message (ie text, html) and title
 * @param array  $message_tags (optional) Array with all message tags
 *
 * @return array               HTTP Context which can used in get_http_request()
 * @global array $config
 */
function generate_transport_tags($transport, $tags = [], $params = [], $message = [], $message_tags = []) {
    global $config;

    if (!isset($message['message'])) {
        // Just use text version of message (also possible in future html, etc
        $message['message'] = $message['text'];
    }
    $tags = array_merge($tags, $params, $message, $message_tags);

    // If transport config options exist, merge it with tags array
    // for use in replace/etc, ie: smsbox
    if (isset($config[$transport])) {
        foreach ($config[$transport] as $param => $value) {
            if (!isset($tags[$param]) || $tags[$param] === '') {
                $tags[$param] = $value;
            }
        }
    }

    // Set defaults and transform params if required
    $def_params = [];
    // Merge required/global and optional parameters
    foreach (array_keys($config['transports'][$transport]['parameters']) as $tmp) {
        $def_params[] = $config['transports'][$transport]['parameters'][$tmp];
    }
    foreach (array_merge([], ...array_values($config['transports'][$transport]['parameters'])) as $param => $entry) {
        // Set default if tag empty
        if (isset($entry['default']) && safe_empty($tags[$param])) {
            $tags[$param] = $entry['default'];
        }
        // Transform param if defined
        if (isset($entry['transform'], $tags[$param])) {
            $tags[$param] = string_transform($tags[$param], $entry['transform']);
        }
    }
    //print_vars($tags);
    // if ($config['transports'][$transport]['notification']['request_format'] === 'json') {
    //   // escape tags for json
    //   print_debug("Transport TAGs escaped for JSON.");
    //   if (OBS_DEBUG > 1) {
    //     print_debug_vars(array_map('json_escape', $tags));
    //   }
    //   return array_map('json_escape', $tags);
    // }

    return $tags;
}

function get_alert_entities_from_assocs($alert)
{

    $entity_type_data = entity_type_translate_array($alert['entity_type']);

    $entity_type = $alert['entity_type'];

    $sql = 'SELECT `' . $entity_type_data['table_fields']['id'] . '` AS `entity_id`';

    //We always need device_id and it's always from devices, duh!
    //if ($alert['entity_type'] != 'device')
    //{
    $sql .= ", `devices`.`device_id` as `device_id`";
    //}

    $sql .= ' FROM `' . $entity_type_data['table'] . '` ';

//   if (isset($entity_type_data['state_table']))
//   {
//      $sql .= ' LEFT JOIN `'.$entity_type_data['state_table'].'` USING (`'.$entity_type_data['id_field'].'`)';
//   }

    if (isset($entity_type_data['parent_table'])) {
        $sql .= ' LEFT JOIN `' . $entity_type_data['parent_table'] . '` USING (`' . $entity_type_data['parent_id_field'] . '`)';
    }

    if ($alert['entity_type'] !== 'device') {
        $sql .= ' LEFT JOIN `devices` ON (`' . $entity_type_data['table'] . '`.`device_id` = `devices`.`device_id`) ';
    }


    $params = [];
    foreach ($alert['assocs'] as $assoc) {

        $where = ' (( 1';

        foreach ($assoc['device_attribs'] as $attrib) {
            switch ($attrib['condition']) {
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
                    $where .= generate_query_values_and(get_var_csv($attrib['value']), '`devices`.' . $attrib['attrib'], NULL, ['ifnull']);
                    break;
                case '!in':
                case '!list':
                case 'notin':
                case 'notlist':
                    $where .= generate_query_values_and(get_var_csv($attrib['value']), '`devices`.' . $attrib['attrib'], '!=', ['ifnull']);
                    break;
                case 'include':
                case 'includes':
                    switch ($attrib['attrib']) {
                        case 'group':
                            $attrib['value'] = group_id_by_name($attrib['value']);
                        case 'group_id':
                            $values = get_group_entities($attrib['value']);
                            $where  .= generate_query_values_and($values, "`devices`.`device_id`");
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
                    $where    .= ' AND `' . $attrib['attrib'] . '` >= ?';
                    $params[] = $attrib['value'];
                    break;
                case 'le':
                case '<=':
                    $where    .= ' AND `' . $attrib['attrib'] . '` <= ?';
                    $params[] = $attrib['value'];
                    break;
                case 'gt':
                case 'greater':
                case '>':
                    $where    .= ' AND `' . $attrib['attrib'] . '` > ?';
                    $params[] = $attrib['value'];
                    break;
                case 'lt':
                case 'less':
                case '<':
                    $where    .= ' AND `' . $attrib['attrib'] . '` < ?';
                    $params[] = $attrib['value'];
                    break;
                case 'notequals':
                case 'isnot':
                case 'ne':
                case '!=':
                    $where    .= ' AND `' . $attrib['attrib'] . '` != ?';
                    $params[] = $attrib['value'];
                    break;
                case 'equals':
                case 'eq':
                case 'is':
                case '==':
                case '=':
                    $where    .= ' AND `' . $attrib['attrib'] . '` = ?';
                    $params[] = $attrib['value'];
                    break;
                case 'match':
                case 'matches':
                    $attrib['value'] = str_replace('*', '%', $attrib['value']);
                    $attrib['value'] = str_replace('?', '_', $attrib['value']);
                    $where           .= ' AND IFNULL(`' . $attrib['attrib'] . '`, "") LIKE ?';
                    $params[]        = $attrib['value'];
                    break;
                case 'notmatches':
                case 'notmatch':
                case '!match':
                    $attrib['value'] = str_replace('*', '%', $attrib['value']);
                    $attrib['value'] = str_replace('?', '_', $attrib['value']);
                    $where           .= ' AND IFNULL(`' . $attrib['attrib'] . '`, "") NOT LIKE ?';
                    $params[]        = $attrib['value'];
                    break;
                case 'regexp':
                case 'regex':
                    $where    .= ' AND IFNULL(`' . $attrib['attrib'] . '`, "") REGEXP ?';
                    $params[] = $attrib['value'];
                    break;
                case 'notregexp':
                case 'notregex':
                case '!regexp':
                case '!regex':
                    $where    .= ' AND IFNULL(`' . $attrib['attrib'] . '`, "") NOT REGEXP ?';
                    $params[] = $attrib['value'];
                    break;
                case 'in':
                case 'list':
                    $where .= generate_query_values_and(get_var_csv($attrib['value']), $attrib['attrib'], NULL, ['ifnull']);
                    break;
                case '!in':
                case '!list':
                case 'notin':
                case 'notlist':
                    $where .= generate_query_values_and(get_var_csv($attrib['value']), $attrib['attrib'], '!=', ['ifnull']);
                    break;
                case 'include':
                case 'includes':
                    switch ($attrib['attrib']) {

                        case 'group':
                            $attrib['value'] = group_id_by_name($attrib['value']);
                        case 'group_id':
                            $group = get_group_by_id($attrib['value']);
                            if ($group['entity_type'] == $entity_type) {
                                $values = get_group_entities($attrib['value']);
                                $where  .= generate_query_values_and($values, $entity_type['table_fields']['id']);
                            }
                            break;
                    }
            }
        }

        $where .= '))';

        $assoc_where[] = $where;

    }

    if (empty($assoc_where)) {
        print_debug('WARNING. Into function ' . __FUNCTION__ . '() passed incorrect or empty entries.');
        return FALSE;
    }

    $where = "WHERE `devices`.`ignore` = '0' AND `devices`.`disabled` = '0' AND (" . implode(" OR ", $assoc_where) . ")";

    if (isset($entity_type_data['deleted_field'])) {
        $where .= " AND `" . $entity_type_data['deleted_field'] . "` != '1'";
    }

    $query = $sql;
    $query .= $where;

    //$entities  = dbFetchRows($query, $params, TRUE);

    $return = [];
    foreach (dbFetchRows($query, $params) as $entry) {
        $return[$entry['entity_id']] = ['entity_id' => $entry['entity_id'], 'device_id' => $entry['device_id']];
    }

    return $return;

    //print_vars($devices);
}

// QB / Alerts/ Groups common functions

// Because the order of key-value objects is uncertain, you can also use an array of one-element objects. (See query builder doc: http://querybuilder.js.org/#filters)
function values_to_json($values)
{
    $array = [];
    //foreach($values as $id => $value) { $array[] = "'".$id."': '".str_replace(array("'", ","), array("\'", "\,")."'"; }
    foreach ($values as $id => $value) {
        //$array[] = '{ '.safe_json_encode($id).': '.safe_json_encode($value).' }';
        // Expanded format with optgroups
        // { value: 'one', label: 'Un', optgroup: 'Group 1' },
        if (is_array($value)) {
            // Our form builder params
            $str = '{ value: ' . safe_json_encode($id) . ', label: ' . safe_json_encode($value['name']);
            if (isset($value['group'])) {
                $str .= ', optgroup: ' . safe_json_encode($value['group']);
            }
            $str     .= ' }';
            $array[] = $str;
        } else {
            // Simple value -> label
            // { value: 'one', label: 'Un', optgroup: 'Group 1' },
            $array[] = '{ value: ' . safe_json_encode($id) . ', label: ' . safe_json_encode($value) . ' }';
        }
    }

    return ' [ ' . implode(', ', $array) . ' ] ';
}

function generate_attrib_values($attrib, $vars)
{

    $values = [];
    //r($vars);

    switch ($attrib) {
        case "device":
            $values = generate_form_values('device', NULL, NULL, [ 'show_disabled' => TRUE ]);
            break;
        case "os":
            foreach ($GLOBALS['config']['os'] as $os => $os_array) {
                $values[$os] = $os_array['text'];
            }
            break;
        case "measured_group":
            $groups = get_groups_by_type($vars['measured_type']);
            foreach ($groups[$vars['measured_type']] as $group_id => $array) {
                $values[$array['group_id']] = $array['group_name'];
            }
            break;
        case "group":
            $groups = get_groups_by_type($vars['entity_type']);
            foreach ($groups[$vars['entity_type']] as $group_id => $array) {
                $values[$array['group_id']] = $array['group_name'];
            }
            break;
        case "location":
            foreach (get_locations() as $location) {
                $values[$location] = $location;
            }
            break;
        case "device_type":
            foreach ($GLOBALS['config']['device_types'] as $type) {
                $values[$type['type']] = $type['text'];
            }
            break;
        case "device_vendor":
        case "device_hardware":
        case "device_distro":
        case "device_distro_ver":
            $column = explode('_', $attrib, 2)[1];
            $query = "SELECT DISTINCT `$column` FROM `devices`";
            foreach (dbFetchColumn($query) as $item) {
                if (!safe_empty($item)) {
                    $values[$item] = $item;
                }
            }
            ksort($values);
            break;

        case "port_type":
            $query = "SELECT DISTINCT `ifType` FROM `ports`";
            foreach (dbFetchColumn($query) as $item) {
                $name = rewrite_iftype($item);
                if ($name != $item) {
                    $name = "$item ($name)";
                }
                if (!safe_empty($item)) {
                    $values[$item] = [ 'name' => $name ];
                }
            }
            //r($values);
            break;
        case "sensor_class":
            foreach ($GLOBALS['config']['sensor_types'] as $class => $data) {
                $values[$class] = nicecase($class);
            }
            break;
        case "status_type":
            $query = "SELECT `status_type` FROM `status` GROUP BY `status_type` ORDER BY `status_type`";
            foreach (dbFetchColumn($query) as $item) {
                if (!safe_empty($item)) {
                    $values[$item] = $item;
                }
            }
            break;
    }

    return $values;

}

function generate_querybuilder_filter($attrib)
{

    if (isset($attrib['community']) && OBSERVIUM_EDITION === 'community' && !$attrib['community']) {
        // Skip attribs not allowed in CE (ie device poller_id)
        return '';
    }

    // Default operators, possible custom list from entity definition (ie group)
    if (isset($attrib['operators'])) {
        // All possible operators, for validate entity attrib
        $operators_array = ['equals', 'notequals', 'le', 'ge', 'lt', 'gt', 'match', 'notmatch', 'regexp', 'notregexp', 'in', 'notin', 'isnull', 'isnotnull'];

        // List to array
        if (!is_array($attrib['operators'])) {
            $attrib['operators'] = explode(',', str_replace(' ', '', $attrib['operators']));
        }

        $operators      = array_intersect($attrib['operators'], $operators_array); // Validate operators list
        $text_operators = "['" . implode("', '", $operators) . "']";
    } else {
        $text_operators = "['equals', 'notequals', 'match', 'notmatch', 'regexp', 'notregexp', 'in', 'notin', 'isnull', 'isnotnull']";
    }
    $num_operators      = "['equals', 'notequals', 'le', 'ge', 'lt', 'gt', 'in', 'notin']";
    $list_operators     = "['in', 'notin']";
    $bool_operators     = "['equals', 'notequals']";
    $function_operators = "['in', 'notin']";

    $attrib['attrib_id'] = ($attrib['entity_type'] === 'device' ? 'device.' : 'entity.') . $attrib['attrib_id'];
    $attrib['label']     = ($attrib['entity_type'] === 'device' ? 'Device ' : nicecase($attrib['entity_type']) . ' ') . $attrib['label'];

    // Clean label duplicates
    $attrib['label'] = implode(' ', array_unique(explode(' ', $attrib['label'])));
    //$attrib['label'] = str_replace("Device Device", "Device", $attrib['label']);
    //r($attrib);

    $filter_array[] = "id: '" . $attrib['attrib_id'] . ($attrib['free'] ? '.free' : '') . "'";
    $filter_array[] = "field: '" . $attrib['attrib_id'] . "'";
    $filter_array[] = "label: '" . $attrib['label'] . ($attrib['free'] ? ' (Free)' : '') . "'";
    if ($attrib['type'] === 'boolean') {
        // Prevent store boolean type as boolean true/false in DB, keep as integer
        $filter_array[] = "type: 'integer'";
    } else {
        $filter_array[] = "type: '" . $attrib['type'] . "'";
    }
    $filter_array[] = "optgroup: '" . nicecase($attrib['entity_type']) . "'";

    // Plugins options:
    $selectpicker_options = "width: '100%', iconBase: '', tickIcon: 'glyphicon glyphicon-ok', showTick: true, selectedTextFormat: 'count>2', ";
    $tagsinput_options    = "trimValue: true, tagClass: function(item) { return 'label label-default'; }";

    if (isset($attrib['values'])) {

        if (is_array($attrib['values'])) {

            $value_list = [];
            foreach ($attrib['values'] as $value) {
                $value_list[$value] = $value;
            }

        } else {
            $value_list = generate_attrib_values($attrib['values'], [ 'entity_type' => $attrib['entity_type'], 'measured_type' => $attrib['measured_type'] ]);
        }

        asort($value_list);
        //r($value_list);
        if (isset($attrib['tags']) && $attrib['tags']) {
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
            $filter_array[] = "operators: " . $function_operators;

            // Fake form element generate, for create script
            $tmp_item = [
              'id'     => 'test',
              'values' => $value_list
            ];
            generate_form_element($tmp_item, 'tags');
        } else {
            // Common multiselect list
            if (count($value_list) > 7) {
                $selectpicker_options .= "liveSearch: true, actionsBox: true, ";
            }
            $values         = values_to_json($value_list);
            $filter_array[] = "input: 'select'";
            $filter_array[] = "plugin: 'selectpicker'";
            $filter_array[] = "plugin_config: { $selectpicker_options }";
            $filter_array[] = "values: " . $values;
            $filter_array[] = "multiple: true";
            $filter_array[] = "operators: " . $list_operators;
        }
    } elseif (isset($attrib['function']) && function_exists($attrib['function'])) {
        if (isset($attrib['tags']) && !$attrib['tags']) {
            // Same as values
            $value_list = call_user_func($attrib['function']);
            // Common multiselect list
            if (count($value_list) > 7) {
                $selectpicker_options .= "liveSearch: true, actionsBox: true, ";
            }
            $values         = values_to_json($value_list);
            $filter_array[] = "input: 'select'";
            $filter_array[] = "plugin: 'selectpicker'";
            $filter_array[] = "plugin_config: { $selectpicker_options }";
            $filter_array[] = "values: " . $values;
            $filter_array[] = "multiple: true";
            $filter_array[] = "operators: " . $list_operators;
        } else {
            register_html_resource('js', 'bootstrap-tagsinput.min.js');   // Enable Tags Input JS
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
            $filter_array[] = "operators: " . $function_operators;
        }
    } elseif ($attrib['type'] === 'integer') {
        $filter_array[] = "operators: " . $num_operators;
    } elseif ($attrib['type'] === 'boolean') {
        $values         = values_to_json([0 => 'False', 1 => 'True']);
        $filter_array[] = "input: 'select'";
        $filter_array[] = "plugin: 'selectpicker'";
        $filter_array[] = "plugin_config: { $selectpicker_options }";
        $filter_array[] = "values: " . $values;
        $filter_array[] = "multiple: false";
        $filter_array[] = "operators: " . $bool_operators;
    } else {
        $filter_array[] = "operators: " . $text_operators;
        //$filter_array[] = "plugin: 'tagsinput'";
        //$filter_array[] = "value_separator: ','";

    }

    return PHP_EOL . '{ ' . implode(',' . PHP_EOL, $filter_array) . ' } ';
}

function generate_querybuilder_filters($entity_type, $type = "attribs")
{

    $type = (($type === "attribs" || $type === "metrics") ? $type : 'attribs');
    $def  = $GLOBALS['config']['entities'][$entity_type];

    if (isset($def['parent_type'])) {
        $filter = generate_querybuilder_filters($def['parent_type']);
    } elseif ($type !== "metrics" && $entity_type !== "device") {
        $filter = generate_querybuilder_filters("device");
    }
    //r($filter);

    // Append Group attrib to any entity
    if (OBSERVIUM_EDITION !== 'community' &&
        $type === 'attribs' && !isset($def[$type]['group_id']) &&
        !isset($def['parent_type'])) { // exclude on parent entities (ie for bgp afi/safi)
        $add_group = [
          'group_id' => [ 'label' => 'Group', 'descr' => 'Group Membership', 'type' => 'string', 'values' => 'group' ],
          'group'    => [ 'label' => 'Group (Free)', 'descr' => 'Group Membership', 'type' => 'string', 'operators' => 'match, notmatch' ]
        ];
        //$config['entities'][$entity]['attribs']['group_id']             = array('label' => 'Group',             'descr' => 'Group Membership',        'type' => 'string', 'values' => 'group');
        //$config['entities'][$entity]['attribs']['group']                = array('label' => 'Group (Free)',      'descr' => 'Group Membership',        'type' => 'string', 'operators' => 'match, notmatch');
        $def[$type] = array_merge($add_group, $def[$type]);
    }

    foreach ($def[$type] as $attrib_id => $attrib) {
        $attrib['entity_type'] = $entity_type;
        $attrib['attrib_id']   = $attrib_id;

        $filter[] = generate_querybuilder_filter($attrib);

        if (isset($attrib['values']) &&
            (!isset($attrib['free']) || $attrib['free']) && // Don't show freeform variant if attrib free set to false
            !str_ends_with($attrib['attrib_id'], "_id")) {  // Don't show freeform variant for device_id, location_id, group_id and etc

            unset($attrib['values']);
            $attrib['free'] = 1;
            $filter[]       = generate_querybuilder_filter($attrib);
        }
    }

    //$filters = ' [ '.implode(', ', $filter).' ] ';

    //print_vars($filter);
    return $filter;

}

function generate_querybuilder_form($entity_type, $type = "attribs", $form_id = 'rules-form', $ruleset = NULL)
{

    // Set rulesets, with allow invalid!
    if (!empty($ruleset)) {
        $rulescript = "
  var rules = " . $ruleset . ";

  $('#" . $form_id . "').queryBuilder('setRules', rules, { allow_invalid: true });

  $('#btn-set').on('click', function() {
    $('#" . $form_id . "').queryBuilder('setRules', rules, { allow_invalid: true });
  });";

        register_html_resource('script', $rulescript);
    }

    $filters = ' [ ' . implode(', ', generate_querybuilder_filters($entity_type, $type)) . ' ] ';

    //$form_id = 'builder-'.$entity_type.'-'.$type;

    echo('

    <div class="box box-solid">
      <!-- <div class="box-header with-border">
        <h3>' . nicecase($entity_type) . ' ' . nicecase($type) . ' Rules Builder</h3>
      </div> -->

      <div id="' . $form_id . '"></div>

      <!--
      <div class="box-footer">
        <div class="btn-group pull-right">
          <button class="btn btn-sm btn-danger" id="btn-reset" data-target="' . $form_id . '">Reset</button>
          <button class="btn btn-sm btn-success" id="btn-set" data-target="' . $form_id . '">Set rules</button>
          <button class="btn btn-sm btn-success" id="btn-get" data-target="' . $form_id . '">Show JSON</button>
          <button class="btn btn-sm btn-primary" id="btn-save" data-target="' . $form_id . '">Save Rules</button>
        </div>
      </div> -->
    </div>');

    // Add CSRF Token
    if (isset($_SESSION['requesttoken'])) {
        echo generate_form_element(['type' => 'hidden', 'id' => 'requesttoken', 'value' => $_SESSION['requesttoken']]) . PHP_EOL;
    }
    echo("<div class='box box-solid' id='output'></div>

<script>

  $('#" . $form_id . "').queryBuilder({
    plugins: {
      'bt-selectpicker': {
        style: 'btn-inverse btn',
        width: '100%',
        liveSearch: true,
      },
      'sortable': null,
    },
    filters: " . $filters . ",

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
  })


$('#btn-reset').on('click', function() {
  $('#" . $form_id . "').queryBuilder('reset');
});

$('#btn-get').on('click', function() {
  var result = $('#" . $form_id . "').queryBuilder('getRules');

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

    $sql = 'SELECT `' . $entity_type_data['table_fields']['id'] . '`';

    // Required in update_alert_table()
    if ($entity_type !== 'device') {
        $sql .= ", `devices`.`device_id` as `device_id`";
    }

    $sql .= ' FROM `' . $entity_type_data['table'] . '` ';

    // Join devices before parents
    if ($entity_type !== 'device') {
        //$sql .= ' LEFT JOIN `devices` USING (`device_id`)';
        $sql .= ' LEFT JOIN `devices` ON (`' . $entity_type_data['table'] . '`.`device_id` = `devices`.`device_id`) ';
    }

    // if (isset($entity_type_data['state_table'])) {
    //   $sql .= ' LEFT JOIN `'.$entity_type_data['state_table'].'` USING (`'.$entity_type_data['id_field'].'`)';
    // }

    if (isset($entity_type_data['parent_table'])) {
        $sql .= ' LEFT JOIN `' . $entity_type_data['parent_table'] . '` USING (`' . $entity_type_data['parent_id_field'] . '`)';
    }

    $where = [];
    $where[] = parse_qb_rules($entity_type, $rules, $ignore);

    if ($ignore) { // This is for alerting, so filter out ignore/disabled stuff
        // Exclude ignored entities
        if (isset($entity_type_data['ignore_field'])) {
            $where[] = "`" . $entity_type_data['table'] . "`.`" . $entity_type_data['ignore_field'] . "` != '1'";
        }
        // Exclude disabled entities
        if (isset($entity_type_data['disable_field'])) {
            $where[] = "`" . $entity_type_data['table'] . "`.`" . $entity_type_data['disable_field'] . "` != '1'";
        }
        // Exclude disabled/ignored devices (if not device entity)
        if ($entity_type !== 'device') {
            $where[] = "`devices`.`disabled` != '1'";
            $where[] = "`devices`.`ignore` != '1'";
        }
    }

    if (isset($entity_type_data['deleted_field'])) {
        $where[] = "`" . $entity_type_data['table'] . "`.`" . $entity_type_data['deleted_field'] . "` != '1'";
    }
    $sql .= generate_where_clause($where);

    //r($sql);

    return $sql;
}

function parse_qb_rules($entity_type, $rules, $ignore = FALSE) {
    global $config;


    $entity_type_data = entity_type_translate_array($entity_type);
    $entity_attribs   = $config['entities'][$entity_type]['attribs'];
    $parts            = [];
    foreach ($rules['rules'] as $rule) {

        if (is_array($rule['rules'])) {

            $parts[] = parse_qb_rules($entity_type, $rule);

        } else {

            //print_r($rule);

            [ $table, $field ] = explode('.', $rule['field']);

            if ($table === 'entity' || $table == $entity_type) {
                $table_type_data = $entity_type_data;
            } else {
                // This entity can be not same as main entity!
                $table_type_data = entity_type_translate_array($table);
            }

            // Pre-transform value according to DB field (see port ARP/MAC)
            $rule['value_original'] = $rule['value'];
            if (isset($entity_attribs[$field]['transform'])) {
                $entity_attribs[$field]['transformations'] = $entity_attribs[$field]['transform'];
            }
            if (isset($entity_attribs[$field]['transformations'])) {
                $rule['value'] = string_transform($rule['value'], $entity_attribs[$field]['transformations']);
            }

            $part = '';
            // Check if field is a measured entity
            $field_measured = isset($entity_attribs[$field]['measured_type'],
                              $config['entities'][$entity_attribs[$field]['measured_type']]); // And this entity type exists

            if (isset($entity_attribs[$field]['function']) && function_exists($entity_attribs[$field]['function']) &&
                (!isset($entity_attribs[$field]['tags']) || $entity_attribs[$field]['tags'])) { // i.e. device poller_id
                // Pass original rule value, which translated to entity_id(s) by function call
                if (isset($entity_attribs[$field]['function_entity_type'])) {
                    // Custom entity type
                    $function_args = [$entity_attribs[$field]['function_entity_type'], $rule['value']];
                } else {
                    $function_args = [$entity_type, $rule['value']];
                }
                $rule['value'] = call_user_func_array($entity_attribs[$field]['function'], $function_args);
                // Override $field by entity_id
                $rule['field_quoted'] = '`' . $entity_type_data['table'] . '`.`' . $entity_type_data['table_fields']['id'] . '`';

                //logfile('alerts.log', 'function_args: '.var_export($function_args, TRUE)); /// DEVEL
                //logfile('alerts.log', 'rule value: '.var_export($rule['value'], TRUE)); /// DEVEL
                //print_vars($function_args);
                //print_vars($rule['value']);
            } elseif ($field_measured) {
                // This attrib is measured entity
                //$measured_type      = $entity_attribs[$field]['measured_type'];
                //$measured_type_data = entity_type_translate_array($measured_type);

                if ($entity_attribs[$field]['values'] === 'measured_group') {
                    // When values used as a measured group, convert it to entity ids
                    //logfile('groups.log', 'passed value: '.var_export($rule['value'], TRUE)); /// DEVEL
                    $group_ids = !is_array($rule['value']) ? explode(',', $rule['value']) : $rule['value'];
                    $rule['value'] = get_group_entities($group_ids);
                    //logfile('groups.log', 'groups value: '.var_export($rule['value'], TRUE)); /// DEVEL
                } else {
                    //$rule['field_quoted'] = '`'.$table_type_data['table'].'`.`'.$field.'`';
                }

                // Override $field by measured entity_id
                $rule['field_quoted'] = '`' . $table_type_data['table'] . '`.`' . $entity_type_data['table_fields']['measured_id'] . '`';
                //logfile('groups.log', 'value: '.var_export($rule['value'], TRUE)); /// DEVEL
                //logfile('groups.log', 'field: '.$rule['field_quoted']);            /// DEVEL

            } elseif (isset($entity_attribs[$field]['table'])) {
                // This attrib specifies a table name (used for oid, since there is no parent)
                $rule['field_quoted'] = '`' . $entity_attribs[$field]['table'] . '`.`' . $field . '`';
            } elseif (isset($config['entities'][$entity_type]['parent_type'],
                      $config['entities'][$config['entities'][$entity_type]['parent_type']]['attribs'][$field]) && !isset($entity_attribs[$field])) {
                // This attrib does not exist on this entity && this entity has a parent && this attrib exists on the parent
                $rule['field_quoted'] = '`' . $config['entities'][$config['entities'][$entity_type]['parent_type']]['table'] . '`.`' . $field . '`';

            } else {

                //$rule['field_quoted'] = '`'.$field.'`';
                // Always use full table.column, for do not get errors ambiguous (after JOINs)

                $rule['field_quoted'] = '`' . $table_type_data['table'] . '`.`' . $field . '`';
            }


            $operator_negative = FALSE; // Need for measured
            switch ($rule['operator']) {
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
                    $part              = ' ' . $rule['field_quoted'] . " != '" . dbEscape($rule['value']) . "'";
                    break;
                case 'equals':
                    $part = ' ' . $rule['field_quoted'] . " = '" . dbEscape($rule['value']) . "'";
                    break;
                case 'match':
                    if ($field === 'group') {
                        //$group = get_group_by_name($rule['value_original']);
                        $group_ids = get_group_ids_by_name_match($rule['value_original'], $table);
                        //$group_ids = get_group_ids_by_name_match($rule['value_original'], $entity_type);
                        if ($values = get_group_entities($group_ids)) {
                            //$values = get_group_entities($group['group_id']);
                            $part = generate_query_values($values, ($table === "device" ? "devices.device_id" : $table_type_data['table'] . '.' . $entity_type_data['table_fields']['id']));
                        }
                    } else {
                        $rule['value'] = str_replace(['*', '?'], ['%', '_'], $rule['value_original']);
                        //$rule['value'] = str_replace('?', '_', $rule['value']);
                        $part = ' IFNULL(' . $rule['field_quoted'] . ', "") LIKE' . " '" . dbEscape($rule['value']) . "'";
                    }
                    break;
                case 'notmatch':
                    $operator_negative = TRUE;
                    if ($field === 'group') {
                        //$group = get_group_by_name($rule['value_original']);
                        $group_ids = get_group_ids_by_name_match($rule['value_original'], $table);
                        //$group_ids = get_group_ids_by_name_match($rule['value_original'], $entity_type);
                        if ($values = get_group_entities($group_ids)) {
                            //$values = get_group_entities($group['group_id']);
                            $part = generate_query_values($values, ($table === "device" ? "devices.device_id" : $table_type_data['table'] . '.' . $entity_type_data['table_fields']['id']), '!=');
                        }
                    } else {
                        $rule['value'] = str_replace([ '*', '?' ], [ '%', '_' ], $rule['value_original']);
                        //$rule['value'] = str_replace('?', '_', $rule['value']);
                        $part = ' IFNULL(' . $rule['field_quoted'] . ', "") NOT LIKE' . " '" . dbEscape($rule['value']) . "'";
                    }
                    break;
                case 'regexp':
                    $part = ' IFNULL(' . $rule['field_quoted'] . ', "") REGEXP' . " '" . dbEscape($rule['value_original']) . "'";
                    break;
                case 'notregexp':
                    $operator_negative = TRUE;
                    $part              = ' IFNULL(' . $rule['field_quoted'] . ', "") NOT REGEXP' . " '" . dbEscape($rule['value_original']) . "'";
                    break;
                case 'isnull':
                    $part = ' ' . $rule['field_quoted'] . ' IS NULL';
                    break;
                case 'isnotnull':
                    $part = ' ' . $rule['field_quoted'] . ' IS NOT NULL';
                    break;
                case 'in':
                    //print_vars($field);
                    //print_vars($rule);
                    if ($field === 'group_id') {
                        $values = get_group_entities($rule['value_original']);
                        $part = generate_query_values($values, ($table === "device" ? "devices.device_id" : $table_type_data['table'] . '.' . $entity_type_data['table_fields']['id']));
                    } else {
                        if (isset($entity_attribs[$field]['transformations'])) {
                            $values = get_var_csv($rule['value_original']);
                            foreach ($values as &$value) {
                                $value = string_transform($value, $entity_attribs[$field]['transformations']);
                            }
                        } else {
                            // When transformations not used, can use other value overrides, ie function calls
                            $values = get_var_csv($rule['value']);
                        }
                        //r($values);
                        //logfile('alerts.log', $rule['field_quoted'] . ': ' . var_export($values, TRUE));
                        //logfile('alerts.log', var_export($rule, TRUE));
                        $part = generate_query_values($values, $rule['field_quoted'], NULL, ['ifnull' ]);
                    }
                    //print_vars($parts);
                    break;
                case 'notin':
                    $operator_negative = TRUE;
                    if ($field === 'group_id') {
                        $values = get_group_entities($rule['value_original']);
                        $part = generate_query_values($values, ($table === "device" ? "devices.device_id" : $table_type_data['table'] . '.' . $entity_type_data['table_fields']['id']), '!=');
                    } else {
                        if (isset($entity_attribs[$field]['transformations'])) {
                            $values = get_var_csv($rule['value_original']);
                            foreach ($values as &$value) {
                                $value = string_transform($value, $entity_attribs[$field]['transformations']);
                            }
                        } else {
                            // When transformations not used, can use other value overrides, ie function calls
                            $values = get_var_csv($rule['value']);
                        }
                        $part = generate_query_values($values, $rule['field_quoted'], '!=', ['ifnull' ]);
                    }
                    break;
            }
            // For measured field append measured
            if ($field_measured && !safe_empty($part)) {
                $measured_type = $entity_attribs[$field]['measured_type'];
                $part          = ' (`' . $table_type_data['table'] . '`.`' . $entity_type_data['table_fields']['measured_type'] .
                                 "` = '" . dbEscape($measured_type) . "' AND (" . $part . '))';
                // For negative rule operators append all entities without measured type field
                if ($operator_negative) {
                    $part = ' (`' . $table_type_data['table'] . '`.`' . $entity_type_data['table_fields']['measured_type'] . '` IS NULL OR' . $part . ')';
                }
            }
            //if ($field_measured) { logfile('groups.log', $part); } /// DEVEL
            if (!safe_empty($part)) {
                $parts[] = $part;
            }

        }
    }

    $sql = !empty($parts) ? '(' . implode(" " . $rules['condition'], $parts) . ')' : '';

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

    $ruleset              = [];
    $ruleset['condition'] = 'OR';
    $ruleset['valid']     = 'true';

    foreach ($entry['assocs'] as $assoc) {

        $x              = [];
        $x['condition'] = 'AND';

        $a = ['device' => $assoc['device_attribs'], 'entity' => $assoc['entity_attribs']];

        foreach ($a as $type => $rules) {

            foreach ($rules as $rule) {

                if ($rule['attrib'] !== '*') {

                    if ($type === 'device' || $entity_type === 'device') {
                        $def = $GLOBALS['config']['entities'][$type]['attribs'][$rule['attrib']];
                    } else {
                        $def = $GLOBALS['config']['entities'][$entity_type]['attribs'][$rule['attrib']];
                    }

                    $e          = [];
                    $e['id']    = ($type === 'device' ? 'device.' : 'entity.') . $rule['attrib'];
                    $e['field'] = $e['id'];
                    $e['type']  = $def['type'];
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
                            $e['value']    = explode(',', $e['value']);
                            $e['operator'] = "in";
                            break;
                        case '!in':
                        case '!list':
                        case 'notin':
                        case 'notlist':
                            $e['value']    = explode(',', $e['value']);
                            $e['operator'] = "notin";
                            break;
                        case 'include':
                        case 'includes':
                            switch ($rule['attrib']) {
                                case 'group':
                                    $e['operator'] = "match";
                                    $e['type']     = 'text';
                                    break;
                                case 'group_id':
                                    $e['operator'] = "in";
                                    $e['value']    = explode(',', $e['value']);
                                    $e['type']     = 'select';
                                    break;

                            }
                            break;
                    }

                    if (isset($def['values']) &&
                        in_array($e['operator'], ["equals", "notequals", "match", "notmatch", "regexp", "notregexp"])) {
                        $e['id'] .= ".free";
                    }

                    if (in_array($e['operator'], ['in', 'notin'])) {
                        $e['input'] = 'select';
                    } elseif ($def['type'] === 'integer') {
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
    $rules_count = count($ruleset['rules']);
    if ($rules_count === 1) {
        $ruleset['rules']     = $ruleset['rules'][0]['rules'];
        $ruleset['condition'] = 'AND';
    } elseif ($rules_count < 1) {
        $ruleset['rules'][] = ['id' => 'device.hostname', 'field' => 'device.hostname', 'type' => 'string', 'value' => '*', 'operator' => 'match', 'input' => 'text'];
    }

    return $ruleset;

}

function render_qb_rules($entity_type, $rules)
{

    $parts = [];

    $entity_type_data = entity_type_translate_array($entity_type);

    foreach ($rules['rules'] as $rule) {
        if (is_array($rule['rules'])) {
            $parts[] = render_qb_rules($entity_type, $rule);

        } else {

            [$table, $field] = explode('.', $rule['field']);

            if ($table === "device") {

            } elseif ($table === "entity") {

                $table = $entity_type;

            } elseif ($table === "parent") {

                $table = $entity_type_data['parent_type'];

            }

            // Boolean stored as bool object, can not be displayed
            if ($rule['type'] === 'boolean') {
                $rule['value'] = (int)$rule['value'];
            }

            $values  = is_array($rule['value']) ? implode('|', $rule['value']) : $rule['value'];
            $parts[] = "<code style='margin: 1px'>" . escape_html("$table.$field " . $rule['operator']) . " " .
                       escape_html($values) . "</code>";
        }

    }

    $part = implode(($rules['condition'] === "AND" ? ' <span class="label label-primary">AND</span> ' : ' <span class="label label-info">OR</span> '), $parts);

    if (count($parts) > 1) {
        $part = '<b style="font-size: 1.2em">(</b>' . $part . '<b>)</b>';
    }


    return $part;
}

function valid_json_notification($value) {
    //r($value);
    safe_json_decode($value);
    $valid = json_last_error() === JSON_ERROR_NONE;

    if (!$valid) {
        // Load test message_tags for correct JSON validate with real data
        // https://jira.observium.org/browse/OBS-4626
        //bdump($value);
        $notification = safe_json_decode(file_get_contents($GLOBALS['config']['install_dir'] . '/includes/templates/test/notification_ALERT.json'));
        $message_tags = safe_json_decode($notification['message_tags']);

        $notification = safe_json_decode(file_get_contents($GLOBALS['config']['install_dir'] . '/includes/templates/test/notification_SYSLOG.json'));
        $message_tags = array_merge(safe_json_decode($notification['message_tags']), $message_tags);

        // Decode again with real data
        //bdump(array_tag_replace($message_tags, $value));
        safe_json_decode(array_tag_replace($message_tags, $value));

        $valid = json_last_error() === JSON_ERROR_NONE;
    }

    return $valid;
}

function alerts_export($vars) {

    $for_export = [];
    foreach (cache_alert_rules($vars) as $id => $alert) {
        // clean not required for export
        unset($alert['alert_test_id'], $alert['enable'], $alert['show_frontpage'], $alert['ignore_until']);
        // associations already json
        $alert['alert_assoc'] = safe_json_decode($alert['alert_assoc']);

        $for_export[] = $alert;
    }

    return $for_export;
}

// EOF
