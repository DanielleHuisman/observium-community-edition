<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// SW-MIB
// See: http://jira.observium.org/browse/OBSERVIUM-1043
//      http://jira.observium.org/browse/OBSERVIUM-854

$port_sw = snmpwalk_cache_oid($device, "swFCPortSpecifier", [], "SW-MIB");
if (!$GLOBALS['snmp_status']) {
    return;
} // Break walk if not exist data from SW-MIB

$port_sw = snmpwalk_cache_oid($device, "swFCPortName", $port_sw, "SW-MIB");

foreach ($port_stats as $ifIndex => $port) {
    foreach ($port_sw as $key => $data) {
        $port_fc  = $data['swFCPortSpecifier'];
        $found_fc = ($port['ifName'] == $port_fc) || preg_match('!^FC\w* port ' . $port_fc . '$!', $port['ifDescr']);
        if (!$found_fc && is_numeric($port_fc)) {
            // non-bladed
            $port_fc  = '0/' . $port_fc;
            $found_fc = ($port['ifName'] == $port_fc) || preg_match('!^FC\w* port ' . $port_fc . '$!', $port['ifDescr']);
        }
        if ($found_fc) {
            print_debug("FOUND FC ifIndex: $ifIndex, Specifier: '$port_fc', ifName: '" . $port['ifName'] . "', ifDescr: '" . $port['ifDescr'] . "', ifAlias: '" . $data['swFCPortName'] . "'");
            $port_stats[$ifIndex]['ifAlias'] = $data['swFCPortName'];

            unset($port_sw[$key]);
            break;
        }
    }
}

unset($port_sw, $port_fc, $found_fc);

// EOF
