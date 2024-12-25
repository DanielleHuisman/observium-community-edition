<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) Adam Armstrong
 *
 */

/// SEARCH STATUS
$where   = [ '(`entPhysicalSerialNum` LIKE ? OR `entPhysicalModelName` LIKE ? OR `entPhysicalAssetID` LIKE ?)' ];
$where[] = '`deleted` IS NULL';

$sql     = "SELECT * FROM `entPhysical` LEFT JOIN `devices` USING (`device_id`)" .
           generate_where_clause($where, $GLOBALS['cache']['where']['devices_permitted']) .
           " ORDER BY `entPhysicalName` LIMIT $query_limit";
$results = dbFetchRows($sql, [ $query_param, $query_param, $query_param ]);

if (safe_empty($results)) {
    return;
}

$max_len = 35;
foreach ($results as $result) {
    $name        = truncate($result['entPhysicalPhysicalName'], $max_len);
    $device_name = truncate($result['hostname'], $max_len);
    if ($result['hostname'] != $result['sysName'] && $result['sysName']) {
        $device_name .= ' | ' . truncate($result['sysName'], $max_len);
    }

    if (strlen($result['entPhysicalModelName'])) {
        $model = $result['entPhysicalModelName'];
    } elseif (strlen($result['entPhysicalDescr'])) {
        $model = $result['entPhysicalDescr'];
    } elseif (strlen($result['entPhysicalName'])) {
        $model = $result['entPhysicalName'];
    } else {
        $model = "";
    }

    /// FIXME: once we have alerting, colour this to the sensor's status
    $tab_colour = '#194B7F'; // FIXME: This colour pulled from functions.inc.php humanize_device, maybe set it centrally in definitions?

    $status_search_results[] = [
        'url'  => generate_device_url($result, ['tab' => 'entphysical']),
        'name' => $name, 'colour' => $tab_colour,
        'icon' => $config['icon']['inventory'],
        'data' => [
            escape_html($device_name),
            html_highlight(escape_html($model) . ' | SN: ' . escape_html($result['entPhysicalSerialNum']), $queryString)
        ]
    ];

}

$search_results['status'] = [ 'descr' => 'Inventory entry found', 'results' => $status_search_results ];

// EOF

