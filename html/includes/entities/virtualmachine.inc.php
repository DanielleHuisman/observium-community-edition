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

function generate_vm_query($vars)
{
    $sql = 'SELECT * FROM `vminfo` ';

    $where = [];

    // Build query
    foreach ($vars as $var => $value) {
        switch ($var) {
            case "group":
            case "group_id":
                $values = get_group_entities($value);
                $where[] = generate_query_values($values, 'vm_id');
                break;
            case 'device_group_id':
            case 'device_group':
                $values = get_group_entities($value, 'device');
                $where[] = generate_query_values($values, 'device_id');
                break;
            case "device":
            case "device_id":
                $where[] = generate_query_values($value, 'device_id');
                break;
            case "os":
                $where[] = generate_query_values($value, 'vm_guestos');
                break;
            case "state":
                $where[] = generate_query_values($value, 'vm_state');
                break;
            case "memory":
                $where[] = generate_query_values($value, 'vm_memory');
                break;
            case "cpu":
                $where[] = generate_query_values($value, 'vm_cpucount');
                break;
        }
    }

    $where[] = $GLOBALS['cache']['where']['devices_permitted'];
    $sql .= generate_where_clause($where);

    return $sql;
}

function print_vm_table_header($vars)
{
    $stripe_class = "table-striped";

    echo('<table class="table ' . $stripe_class . ' table-condensed ">' . PHP_EOL);
    $cols = [ // [ NULL, 'class="state-marker"' ], // FIXME useful when we start polling VM status
              'device' => ['Device', 'style="width: 250px;"'],
              'name'   => ['Name'],
              'state'  => ['State'],
              'os'     => ['Operating System'],
              'type'   => ['Guest Type', 'style="width: 80px;"'],
              'memory' => ['Memory'],
              'cpu'    => ['CPU'],
    ];

    if ($vars['page'] === "device" || get_var_true($vars['popup'])) {
        unset($cols['device']);
    }

    echo(get_table_header($cols, $vars));
    echo('<tbody>' . PHP_EOL);

}

function print_vm_table($vars)
{
    $sql = generate_vm_query($vars);

    $vms = [];
    foreach (dbFetchRows($sql) as $vm) {
        if ($device = device_by_id_cache($vm['device_id'])) {
            $vm['hostname'] = $device['hostname'];
            $vms[]          = $vm;
        }
    }

    // Sorting
    // FIXME. Sorting can be as function, but in must before print_table_header and after get table from db
    switch ($vars['sort_order']) {
        case 'desc':
            $sort_order = SORT_DESC;
            $sort_neg   = SORT_ASC;
            break;
        case 'reset':
            unset($vars['sort'], $vars['sort_order']);
        // no break here
        default:
            $sort_order = SORT_ASC;
            $sort_neg   = SORT_DESC;
    }
    switch ($vars['sort']) {
        case 'name':
            $vms = array_sort_by($vms, 'vm_name', $sort_order, SORT_STRING);
            break;
        case 'os':
            $vms = array_sort_by($vms, 'vm_os', $sort_order, SORT_STRING);
            break;
        case 'state':
            $vms = array_sort_by($vms, 'vm_state', $sort_order, SORT_STRING);
            break;
        case 'memory':
            $vms = array_sort_by($vms, 'vm_memory', $sort_order, SORT_NUMERIC);
            break;
        case 'cpu':
            $vms = array_sort_by($vms, 'vm_cpucount', $sort_order, SORT_NUMERIC);
            break;
        default:
            // Not sorted
    }
    $vms_count = count($vms);

    // Pagination
    $pagination_html = pagination($vars, $vms_count);
    echo $pagination_html;

    if ($vars['pageno']) {
        $vms = array_chunk($vms, $vars['pagesize']);
        $vms = $vms[$vars['pageno'] - 1];
    }
    // End Pagination

    echo generate_box_open();

    print_vm_table_header($vars);

    foreach ($vms as $vm) {
        print_vm_row($vm, $vars);
    }

    echo '</tbody></table>';

    echo generate_box_close();

    echo $pagination_html;
}

function print_vm_row($vm, $vars)
{
    echo generate_vm_row($vm, $vars);
}

function generate_vm_row($vm, $vars)
{
    global $config;

    //print_vars($vm);

    $table_cols = "8";

    $out = '<tr class="' . $vm['row_class'] . '">'; // <td class="state-marker"></td>'; // FIXME useful when we start polling VM state
    if ($vars['page'] != "device" && $vars['popup'] != TRUE) {
        $out .= '<td class="entity">' . generate_device_link($vm) . '</td>';
        $table_cols++;
    }
    $out .= '<td class="entity">' . generate_entity_link('virtualmachine', $vm) . '</td>';
    $out .= '<td>' . nicecase($vm['vm_state']) . '</td>';

    $guestos = rewrite_vm_guestos($vm['vm_guestos'], $vm['vm_type']);
    if (str_starts_with($guestos, 'Unknown')) {
        $out .= '  <td class="small">'.$guestos.'</td>';
    } else {
        $out .= '  <td>' . $guestos . '</td>';
    }

    $out .= '<td>' . nicecase($vm['vm_type']) . '</td>';
    $out .= '<td>' . format_bi($vm['vm_memory'] * 1024 * 1024, 3, 3) . 'B</td>';
    $out .= '<td>' . $vm['vm_cpucount'] . '</td>';
    $out .= '</tr>';

    return $out;
}

// EOF
