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

function generate_inventory_query($vars)
{

    $param = [];
    $where = [];

    $where[] = $GLOBALS['cache']['where']['devices_permitted'];

    $select[] = 'entPhysical.*';
    // By default hide deleted inventory
    if (!isset($vars['deleted'])) {
        $vars['deleted'] = '0';
    }

    foreach ($vars as $var => $value) {
        if ($value != '') {
            switch ($var) {
                case 'device':
                case 'device_id':
                    $where[] = generate_query_values($value, 'device_id');
                    break;
                case 'os':
                    $where[]  = generate_query_values($value, 'os');
                    $select[] = 'devices.os';
                    $devices  = TRUE;
                    break;
                case 'parts':
                case 'entPhysicalModelName':
                    $where[] = generate_query_values($value, 'entPhysicalModelName', 'LIKE');
                    break;
                case 'serial':
                case 'entPhysicalSerialNum':
                    $where[] = generate_query_values($value, 'entPhysicalSerialNum', '%LIKE%');
                    break;
                case 'description':
                case 'entPhysicalDescr':
                    $where[] = generate_query_values($value, 'entPhysicalDescr', '%LIKE%');
                    break;
                case 'class':
                case 'entPhysicalClass':
                    $where[] = generate_query_values($value, 'entPhysicalClass', '%LIKE%');
                    break;
                case 'deleted':
                    $where[] = generate_query_values($value, 'deleted', 'NOT NULL');
                    break;
            }
        }
    }

    $query = 'FROM `entPhysical`';

    if ($vars['sort'] === 'hostname' || $vars['sort'] === 'device' || $vars['sort'] === 'device_id' || $devices == TRUE) {
        $query    .= ' LEFT JOIN `devices` USING(`device_id`)';
        $select[] = 'devices.hostname';
    }

    $query .= generate_where_clause($where);

    $query_count = 'SELECT COUNT(entPhysical_id) ' . $query;

    $query = 'SELECT ' . implode(', ', $select) . ' ' . $query;

    $sort_order = get_sort_order($vars);

    switch ($vars['sort']) {
        case 'device':
        case 'hostname':
            //$query .= ' ORDER BY `hostname` '.$sort_order;
            $query .= generate_query_sort('hostname', $sort_order);
            break;
        case 'descr':
        case 'event':
        case 'value':
        case 'last_change':
            $query .= generate_query_sort('sensor_' . $vars['sort'], $sort_order);
            break;
        default:
            // $query .= ' ORDER BY `hostname` '.$sort_order.', `sensor_descr` '.$sort_order;
            // $query .= generate_query_sort([ 'hostname', 'sensor_descr' ],$sort_order);
    }

    if (isset($vars['pageno'])) {
        $start = $vars['pagesize'] * ($vars['pageno'] - 1);
        $query .= ' LIMIT ' . $start . ',' . $vars['pagesize'];
    }

    return [ 'query' => $query, 'query_count' => $query_count, 'param' => $param ];

}

/**
 * Display Devices Inventory.
 *
 * @param array $vars
 *
 * @return void|boolean
 *
 */
function print_inventory($vars)
{
    // On "Inventory" device tab display hierarchical list
    if ($vars['page'] === 'device' && is_numeric($vars['device']) && device_permitted($vars['device'])) {
        // DHTML expandable tree
        register_html_resource('js', 'mktree.js');
        register_html_resource('css', 'mktree.css');

        echo generate_box_open($vars['header']);
        echo('<table class="table table-striped  table-condensed "><tr><td>');
        echo('<div class="btn-group pull-right" style="margin-top:5px; margin-right: 5px;">
      <button class="btn btn-small" onClick="expandTree(\'enttree\');return false;"><i class="icon-plus muted small"></i> Expand</button>
      <button class="btn btn-small" onClick="collapseTree(\'enttree\');return false;"><i class="icon-minus muted small"></i> Collapse</button>
    </div>');

        echo('<div style="clear: left; margin: 5px;"><ul class="mktree" id="enttree" style="margin-left: -10px;">');
        print_ent_physical(0, 0, "liOpen");
        echo('</ul></div>');
        echo('</td></tr></table>');
        echo generate_box_close();

        return TRUE;
    }

    // With pagination? (display page numbers in header)
    $pagination = (isset($vars['pagination']) && $vars['pagination']);
    pagination($vars, 0, TRUE); // Get default pagesize/pageno
    $pageno   = $vars['pageno'];
    $pagesize = $vars['pagesize'];
    $start    = $pagesize * $pageno - $pagesize;

    $queries = generate_inventory_query($vars);

    // Query inventories
    $entries = dbFetchRows($queries['query'], $queries['param']);

    // Query inventory count
    if ($pagination) {
        $count = dbFetchCell($queries['query_count'], $queries['param']);
    }

    $list = ['device' => FALSE];
    if (!isset($vars['device']) || empty($vars['device']) || $vars['page'] === 'inventory') {
        $list['device'] = TRUE;
    }

    $string = generate_box_open($vars['header']);
    $string .= '<table class="' . OBS_CLASS_TABLE_STRIPED . '">' . PHP_EOL;
    if (!$short) {
        $string .= '  <thead>' . PHP_EOL;
        $string .= '    <tr>' . PHP_EOL;
        if ($list['device']) {
            $string .= '      <th>Device</th>' . PHP_EOL;
        }
        $string .= '      <th>Name</th>' . PHP_EOL;
        $string .= '      <th>Description</th>' . PHP_EOL;
        $string .= '      <th>Part #</th>' . PHP_EOL;
        $string .= '      <th>Serial #</th>' . PHP_EOL;
        $string .= '      <th>Removed</th>' . PHP_EOL;
        $string .= '    </tr>' . PHP_EOL;
        $string .= '  </thead>' . PHP_EOL;
    }
    $string .= '  <tbody>' . PHP_EOL;

    foreach ($entries as $entry) {
        $string .= '  <tr>' . PHP_EOL;
        if ($list['device']) {
            $string .= '    <td class="entity" style="white-space: nowrap">' . generate_device_link($entry, NULL, ['page' => 'device', 'tab' => 'entphysical']) . '</td>' . PHP_EOL;
        }
        if ($entry['ifIndex']) {
            $interface                = get_port_by_ifIndex($entry['device_id'], $entry['ifIndex']);
            $entry['entPhysicalName'] = generate_port_link($interface);
        } elseif ($entry['entPhysicalClass'] === "sensor") {
            $sensor = dbFetchRow("SELECT * FROM `sensors` 
                            WHERE `device_id` = ? AND (`entPhysicalIndex` = ? OR `sensor_index` = ?)", [$entry['device_id'], $entry['entPhysicalIndex'], $entry['entPhysicalIndex']]);

            $entry['entPhysicalName'] = generate_entity_link('sensor', $sensor);
        }
        $string .= '    <td style="width: 160px;">' . $entry['entPhysicalName'] . '</td>' . PHP_EOL;
        $string .= '    <td>' . $entry['entPhysicalDescr'] . '</td>' . PHP_EOL;
        $string .= '    <td>' . $entry['entPhysicalModelName'] . '</td>' . PHP_EOL;
        $string .= '    <td>' . $entry['entPhysicalSerialNum'] . '</td>' . PHP_EOL;
        $string .= '    <td>' . $entry['deleted'] . '</td>' . PHP_EOL;
        $string .= '  </tr>' . PHP_EOL;

    }

    $string .= '  </tbody>' . PHP_EOL;
    $string .= '</table>';
    $string .= generate_box_close();

    // Print pagination header
    if ($pagination) {
        $string = pagination($vars, $count) . $string . pagination($vars, $count);
    }

    $entries_allowed = ['entPhysical_id', 'device_id', 'entPhysicalIndex', 'entPhysicalDescr',
                        'entPhysicalClass', 'entPhysicalName', 'entPhysicalHardwareRev', 'entPhysicalFirmwareRev',
                        'entPhysicalSoftwareRev', 'entPhysicalAlias', 'entPhysicalAssetID', 'entPhysicalIsFRU',
                        'entPhysicalModelName', 'entPhysicalVendorType', 'entPhysicalSerialNum', 'entPhysicalContainedIn',
                        'entPhysicalParentRelPos', 'entPhysicalMfgName'];


    foreach ($entries as $entry) {
        $entries_cleaned[$entry['entPhysical_id']] = array_intersect_key($entry, array_flip($entries_allowed));
    }

    // Print Inventories
    switch ($vars['format']) {
        case "csv":

            echo(implode(", ", $entry));
            echo("\n");

            break;
        default:
            echo $string;
            break;
    }
}

/**
 * Display device inventory hierarchy.
 *
 * @param string $entPhysicalContainedIn
 * @param string $level
 * @param string $class
 *
 * @return null
 *
 */
function print_ent_physical($entPhysicalContainedIn, $level, $class)
{
    global $device, $config;

    $initial = $entPhysicalContainedIn === 0 && $level === 0;
    $where   = '`device_id` = ? AND `entPhysicalContainedIn` = ?';
    if ($initial) {
        // First level must be not deleted!
        $where .= ' AND `deleted` IS NULL';
    }

    $ents = dbFetchRows("SELECT * FROM `entPhysical` WHERE $where ORDER BY `entPhysicalContainedIn`, `ifIndex`, `entPhysicalIndex`", [$device['device_id'], $entPhysicalContainedIn]);
    if ($initial && empty($ents)) {
        // In some rare cases device report initial entity with index -1
        //$entPhysicalContainedIn -= 1;
        $entPhysicalContainedIn = dbFetchCell("SELECT MIN(`entPhysicalContainedIn`) FROM `entPhysical` WHERE `device_id` = ? AND `deleted` IS NULL", [$device['device_id']]);

        $ents = dbFetchRows("SELECT * FROM `entPhysical` WHERE $where ORDER BY `entPhysicalContainedIn`, `ifIndex`, `entPhysicalIndex`", [$device['device_id'], $entPhysicalContainedIn]);
    }
    //r($ents);

    foreach ($ents as $ent) {
        $link  = '';
        $value = NULL;
        $text  = " <li class='$class'>";

        /*
        Currently no icons for:

        JUNIPER-MIB::jnxFruType.10.1.1.0 = INTEGER: frontPanelModule(8)
        JUNIPER-MIB::jnxFruType.12.1.0.0 = INTEGER: controlBoard(5)

        For Geist RCX, IPOMan:
        outlet
        relay
        */

        // icons
        $icon = 'hardware'; // default icon
        switch (TRUE) {
            case str_starts($ent['entPhysicalClass'], 'chassis'):
                $icon = 'device';
                break;
            case str_starts($ent['entPhysicalClass'], 'board'):
                $icon = 'linecard'; // need something better
                break;
            case str_starts($ent['entPhysicalClass'], ['module', 'adapter']):
            case str_contains($ent['entPhysicalClass'], 'Interface'):
                $icon = 'linecard';
                break;
            case $ent['entPhysicalClass'] === 'port':
                $icon = 'port';
                break;
            case str_starts($ent['entPhysicalClass'], ['container', 'fabric', 'backplane']):
            case str_contains($ent['entPhysicalClass'], 'Concentrator'):
                $icon = 'package';
                break;
            case str_starts($ent['entPhysicalClass'], ['stack']):
                $icon = 'databases';
                break;
            case str_starts($ent['entPhysicalClass'], ['fan', 'airflow']):
                $icon = 'fanspeed';
                break;
            case str_starts($ent['entPhysicalClass'], ['cpu', 'processor', 'cpm']):
                $icon = 'processor';
                break;
            case str_starts($ent['entPhysicalClass'], ['disk', 'flash', 'mda', 'storage', 'drive']):
                $icon = 'storage';
                break;
            case str_starts($ent['entPhysicalClass'], 'power'):
                $icon = 'powersupply';
                break;
            case $ent['entPhysicalClass'] === 'sensor':
                $sensor = dbFetchRow("SELECT * FROM `sensors` WHERE `device_id` = ? AND (`entPhysicalIndex` = ? OR `sensor_index` = ?)", [$device['device_id'], $ent['entPhysicalIndex'], $ent['entPhysicalIndex']]);
                if ($sensor['sensor_class']) {
                    $icon = $GLOBALS['config']['sensor_types'][$sensor['sensor_class']]['icon'];
                    $link = generate_entity_link('sensor', $sensor);

                    humanize_sensor($sensor);
                    //r($sensor);
                    $value = nicecase($sensor['sensor_class']) . ': ' . $sensor['human_value'] . $sensor['sensor_symbol'];
                } else {
                    $icon = 'sensor';
                }
                break;
            case str_starts($ent['entPhysicalClass'], ['routing', 'forwarding']):
                $icon = 'routing';
                break;
            case $ent['entPhysicalClass'] === 'other':
                $tmp_descr = $ent['entPhysicalDescr'] . ' ' . $ent['entPhysicalName'];
                if (str_icontains_array($tmp_descr, ['switch'])) {
                    $icon = 'switching';
                } elseif (str_icontains_array($tmp_descr, ['cpu', 'processor'])) {
                    $icon = 'processor';
                } elseif (str_icontains_array($tmp_descr, ['ram', 'memory'])) {
                    $icon = 'mempool';
                } elseif (str_icontains_array($tmp_descr, ['flash', 'storage'])) {
                    $icon = 'storage';
                } elseif (str_icontains_array($tmp_descr, ['stack'])) {
                    $icon = 'databases';
                } elseif (str_icontains_array($tmp_descr, ['board', 'slot'])) {
                    $icon = 'linecard'; // need something better
                } elseif (str_contains_array($tmp_descr, ['Tray',])) {
                    $icon = 'package';
                }
                break;
        }
        if ($ent['deleted'] !== NULL) {
            $icon = 'minus';
            $icon = ':x:'; // emoji icon just for experiment
        }

        $text .= get_icon($icon) . ' ';
        if ($ent['entPhysicalParentRelPos'] > '-1') {
            $text .= '<strong>' . $ent['entPhysicalParentRelPos'] . '.</strong> ';
        }

        $ent_text = '';

        // port ifIndex
        if ($ent['ifIndex'] && $port = get_port_by_ifIndex($device['device_id'], $ent['ifIndex'])) {
            $link = generate_port_link($port);
        }

        if ($link) {
            $ent['entPhysicalName'] = $link;
        }

        // vendor + model + hw
        $ent_model = '';
        if ($ent['entPhysicalModelName'] && is_valid_param($ent['entPhysicalModelName'], 'hardware')) {
            if ($ent['entPhysicalMfgName'] && is_valid_param($ent['entPhysicalMfgName'], 'vendor')) {
                $ent_model .= $ent['entPhysicalMfgName'];
            }

            $ent_model .= ' ' . $ent['entPhysicalModelName'];

            if ($ent['entPhysicalHardwareRev'] && is_valid_param($ent['entPhysicalHardwareRev'], 'revision')) {
                $ent_model .= " " . $ent['entPhysicalHardwareRev'];
            }
            $ent_model = trim($ent_model);
        }

        if ($ent['entPhysicalModelName'] && $ent_model) {
            if ($ent['ifIndex']) {
                // For ports different order
                $ent_text .= "<strong>" . $ent['entPhysicalName'] . " (" . $ent_model . ")</strong>";
            } else {
                $ent_text .= "<strong>" . $ent_model . "</strong> (" . $ent['entPhysicalName'] . ")";
            }
        } elseif ($ent_model) {
            $ent_text .= "<strong>" . $ent_model . "</strong>";
        } elseif ($ent['entPhysicalName']) {
            $ent_text .= "<strong>" . $ent['entPhysicalName'] . "</strong>";
        } elseif ($ent['entPhysicalDescr']) {
            $ent_text .= "<strong>" . $ent['entPhysicalDescr'] . "</strong>";
        }

        // entPhysicalAssetID
        if (!safe_empty($ent['entPhysicalAssetID']) &&
            !in_array($ent['entPhysicalAssetID'], ['zeroDotZero', 'zeroDotZero.0'])) {
            $ent_text .= " (" . $ent['entPhysicalAssetID'] . ")";
        }

        // entPhysicalVendorType
        if (!safe_empty($ent['entPhysicalVendorType']) &&
            !in_array($ent['entPhysicalVendorType'], ['zeroDotZero', 'zeroDotZero.0'])) {
            $ent_text .= " (" . $ent['entPhysicalVendorType'] . ")";
        }
        //$ent_text .= " [" . $ent['entPhysicalClass'] . "]"; // DEVEL

        $ent_text .= "<br /><div class='small' style='margin-left: 20px;'>" . $ent['entPhysicalDescr'];

        // Value
        if ($value) {
            $ent_text .= ' (' . $value . ')';
        }

        $text .= $ent_text;

        // Serial, Hardware/Firmware/Software Rev
        if ($ent['entPhysicalSerialNum']) {
            $text .= ' <span class="label label-primary">SN: ' . $ent['entPhysicalSerialNum'] . '</span> ';
        }
        if ($ent['entPhysicalHardwareRev']) {
            $text .= ' <span class="label label-default">HW: ' . $ent['entPhysicalHardwareRev'] . '</span> ';
        }
        if ($ent['entPhysicalFirmwareRev']) {
            $text .= ' <span class="label label-info">FW: ' . $ent['entPhysicalFirmwareRev'] . '</span> ';
        }
        if ($ent['entPhysicalSoftwareRev']) {
            $text .= ' <span class="label label-success">SW: ' . $ent['entPhysicalSoftwareRev'] . '</span> ';
        }

        // Deleted
        if ($ent['deleted'] !== NULL) {
            $text .= ' <span class="text-info">[Deleted: ' . $ent['deleted'] . ']</span> ';
        }

        $text .= "</div>";
        echo($text);

        $count = dbFetchCell("SELECT COUNT(*) FROM `entPhysical` WHERE `device_id` = ? AND `entPhysicalContainedIn` = ?", [$device['device_id'], $ent['entPhysicalIndex']]);
        if ($count) {
            echo("<ul>");
            print_ent_physical($ent['entPhysicalIndex'], $level + 1, '');
            echo("</ul>");
        }
        echo("</li>");
    }
}

// EOF
