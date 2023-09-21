<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

function print_logalert_log($vars)
{

    global $config;

    $entries = get_logalert_log($vars);

    if (!$entries['count']) {
        // There have been no entries returned. Print the warning.
        print_warning('<h4>No logging alert entries found!</h4>');
        return;

    } else {

        // Entries have been returned. Print the table.
        $list = ['device' => FALSE, 'program' => TRUE];
        if (!isset($vars['device']) || empty($vars['device']) || $vars['page'] == 'alert_log') {
            $list['device'] = TRUE;
        }
        if (!isset($vars['la_id']) || empty($vars['la_id'])) {
            $list['la_id'] = TRUE;
        }

        $string = generate_box_open($vars['header']);

        $string .= '<table class="table table-striped table-hover table-condensed-more">' . PHP_EOL;

        if (!$entries['short']) {
            $cols         = [];
            $cols[]       = [NULL, 'class="state-marker"'];
            $cols['date'] = ['Date', 'style="width: 140px"'];
            if ($list['device']) {
                $cols['device'] = ['Device', 'style="width: 150px;"'];
            }
            if ($list['la_id']) {
                $cols['la_id'] = ['Alert Rule', 'style="width: 150px;"'];
            }
            $cols[] = ['Program', 'style="width: 80px"'];
            $cols[] = 'Message';
            //$cols[]         = array('Notified',    'style="width: 40px"');
            $string .= get_table_header($cols); // , $vars); // Actually sorting is disabled now
        }
        $string .= '  <tbody>' . PHP_EOL;

        // Cache syslog rules
        if (!isset($GLOBALS['cache']['syslog']['syslog_rules'])) {
            $GLOBALS['cache']['syslog']['syslog_rules'] = [];
            foreach (dbFetchRows("SELECT * FROM `syslog_rules` ORDER BY `la_name`") as $la) {
                $syslog_rules[$la['la_id']]                               = $la;
                $GLOBALS['cache']['syslog']['syslog_rules'][$la['la_id']] = $la;
            }
        }

        if ($entries['short']) {
            $vars['short'] = $entries['short'];
        }
        foreach ($entries['entries'] as $entry) {
            $string .= generate_syslog_row($entry, $vars, $list);
        }

        $string .= '  </tbody>' . PHP_EOL;
        $string .= '</table>';

        $string .= generate_box_close();

    }

    // Print pagination header
    if ($entries['pagination_html']) {
        $string = $entries['pagination_html'] . $string . $entries['pagination_html'];
    }

    // Print events
    echo $string;
}

function get_logalert_log($vars)
{
    $array = [];

    // Short events? (no pagination, small out)
    $array['short'] = (isset($vars['short']) && $vars['short']);

    // With pagination? (display page numbers in header)
    $array['pagination'] = (isset($vars['pagination']) && $vars['pagination']);
    pagination($vars, 0, TRUE);                    // Get default pagesize/pageno
    $array['pageno']   = $vars['pageno'];
    $array['pagesize'] = $vars['pagesize'];
    $start             = $array['pagesize'] * $array['pageno'] - $array['pagesize'];
    $pagesize          = $array['pagesize'];

    // Begin query generate
    $param = [];
    $where = ' WHERE 1 ';
    foreach ($vars as $var => $value) {
        if ($value != '') {
            switch ($var) {
                case 'la_id':
                    $where .= generate_query_values_and($value, 'la_id');
                    break;
                case 'device':
                case 'device_id':
                    $where .= generate_query_values_and($value, 'device_id');
                    break;
                case 'program':
                    $where .= generate_query_values_and($value, 'program', '%LIKE%');
                    break;
                case 'message':
                    $where .= generate_query_values_and($value, 'message', '%LIKE%');
                    break;
                case 'timestamp_from':
                    $where   .= ' AND `timestamp` >= ?';
                    $param[] = $value;
                    break;
                case 'timestamp_to':
                    $where   .= ' AND `timestamp` <= ?';
                    $param[] = $value;
                    break;
            }
        }
    }

    // Show events only for permitted devices
    $query_permitted = generate_query_permitted(); //generate_query_permitted(array('entity'));

    $query         = 'FROM `syslog_alerts` ';
    $query         .= $where . $query_permitted;
    $query_count   = 'SELECT COUNT(`la_id`) ' . $query;
    $query_updated = 'SELECT MAX(`timestamp`) ' . $query;

    $query = 'SELECT * ' . $query;
    $query .= ' ORDER BY `lal_id` DESC ';
    $query .= "LIMIT $start,$pagesize";

    // Query events
    $array['entries'] = dbFetchRows($query, $param);

    // Query events count
    if ($array['pagination'] && !$array['short']) {
        $array['count']           = dbFetchCell($query_count, $param);
        $array['pagination_html'] = pagination($vars, $array['count']);
    } else {
        $array['count'] = safe_count($array['entries']);
    }

    // Query for last timestamp
    $array['updated'] = dbFetchCell($query_updated, $param);

//r($array);

    return $array;

}

//EOF
