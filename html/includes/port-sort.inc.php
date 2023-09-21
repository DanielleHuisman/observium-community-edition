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
    case 'traffic':
        $ports = array_sort_by($ports, 'ifOctets_rate', $sort_neg, SORT_NUMERIC);
        break;
    case 'traffic_in':
        $ports = array_sort_by($ports, 'ifInOctets_rate', $sort_neg, SORT_NUMERIC);
        break;
    case 'traffic_out':
        $ports = array_sort_by($ports, 'ifOutOctets_rate', $sort_neg, SORT_NUMERIC);
        break;
    case 'traffic_perc_in':
        $ports = array_sort_by($ports, 'ifInOctets_perc', $sort_neg, SORT_NUMERIC);
        break;
    case 'traffic_perc_out':
        $ports = array_sort_by($ports, 'ifOutOctets_perc', $sort_neg, SORT_NUMERIC);
        break;
    case 'traffic_perc':
        $ports = array_sort_by($ports, 'ifOctets_perc', $sort_neg, SORT_NUMERIC);
        break;
    case 'packets':
        $ports = array_sort_by($ports, 'ifUcastPkts_rate', $sort_neg, SORT_NUMERIC);
        break;
    case 'packets_in':
        $ports = array_sort_by($ports, 'ifInUcastPkts_rate', $sort_neg, SORT_NUMERIC);
        break;
    case 'packets_out':
        $ports = array_sort_by($ports, 'ifOutUcastPkts_rate', $sort_neg, SORT_NUMERIC);
        break;
    case 'errors':
        $ports = array_sort_by($ports, 'ifErrors_rate', $sort_neg, SORT_NUMERIC);
        break;
    case 'speed':
        $ports = array_sort_by($ports, 'ifSpeed', $sort_neg, SORT_NUMERIC);
        break;
    case 'port':
        //$ports = array_sort_by($ports, 'ifDescr', $sort_order, SORT_STRING);
        $ports = array_sort_by($ports, 'port_label', $sort_order, SORT_STRING);
        break;
    case 'media':
        $ports = array_sort_by($ports, 'ifType', $sort_order, SORT_STRING);
        break;
    case 'mtu':
        $ports = array_sort_by($ports, 'ifMtu', $sort_order, SORT_NUMERIC);
        break;
    case 'descr':
        $ports = array_sort_by($ports, 'ifAlias', $sort_order, SORT_STRING);
        break;
    case 'mac':
        $ports = array_sort_by($ports, 'ifPhysAddress', $sort_neg, SORT_STRING);
        break;
    default:
        $ports = array_sort_by($ports, 'hostname', $sort_order, SORT_STRING, 'ifIndex', $sort_order, SORT_NUMERIC);
}

// EOF
