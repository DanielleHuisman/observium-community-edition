<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage update
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

foreach (dbFetchRows("SELECT * FROM `alert_tests` ". $where, $args) as $entry)
{
    // Check if $conditions is an array before proceeding
    if ($conditions = safe_json_decode($entry['conditions'])) {

        // Use a temporary array to store valid conditions
        $validConditions = [];

        foreach ($conditions as $condition) {
            // Check if all values are empty
            if ($condition['value'] !== '' || $condition['metric'] !== '' || $condition['condition'] !== '') {
                // If not all values are empty, add to valid conditions
                $validConditions[] = $condition;
            }
        }

        // Update the database with the valid conditions only
        dbUpdate(array('conditions' => safe_json_encode($validConditions)), 'alert_tests', '`alert_test_id` = ?', array($entry['alert_test_id']));

        echo('.');
    } else {
        // Handle the case where $conditions is not an array (e.g., null or false)
        // Depending on your application logic, you might want to log this or take other actions
        print_debug("Invalid JSON in alert_tests for alert_test_id: " . $entry['alert_test_id']);
        echo('E');
    }

}

// EOF
