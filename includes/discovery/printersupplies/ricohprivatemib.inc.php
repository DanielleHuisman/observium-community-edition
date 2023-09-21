<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

//$prt_supplies = snmpwalk_oid_num($device, '.1.3.6.1.4.1.367.3.2.1.2.24.1.1', array(), 'RicohPrivateMIB');
$prt_supplies = snmpwalk_oid_num($device, 'ricohEngTonerName', [], 'RicohPrivateMIB');
//$prt_supplies = snmpwalk_oid_num($device, 'ricohEngTonerDescr', $prt_supplies, 'RicohPrivateMIB');
$prt_supplies = snmpwalk_oid_num($device, 'ricohEngTonerType', $prt_supplies, 'RicohPrivateMIB');
$prt_supplies = snmpwalk_oid_num($device, 'ricohEngTonerLevel', $prt_supplies, 'RicohPrivateMIB');
print_debug_vars($prt_supplies);

foreach ($prt_supplies as $index => $entry) {

    $oid_num      = ".1.3.6.1.4.1.367.3.2.1.2.24.1.1.5.$index";
    $update_array = ['description' => $entry['ricohEngTonerName'],
                     'index'       => $index,
                     'oid'         => $oid_num,
                     'mib'         => $mib,
                     'unit'        => 'percent',
                     'type'        => 'toner',
                     'level'       => $entry['ricohEngTonerLevel'],
                     'capacity'    => 100];

    if ($entry['ricohEngTonerLevel'] == '-100') {
        // Toner near empty
        $update_array['level'] = '5'; // ~ 1-20%
    } elseif ($entry['ricohEngTonerLevel'] == '-3') {
        $update_array['level'] = '80'; // ~ 10-100%
    } elseif ($entry['ricohEngTonerLevel'] < '0') {
        // Skip all other
        continue;
    }

    if (str_icontains_array($entry['ricohEngTonerName'], 'Ink')) {
        $update_array['type'] = 'ink';
    }

    switch ($entry['ricohEngTonerType']) {
        case '3':
        case 'blackTonerMono':
            $update_array['colour'] = 'black';
            break;
        case '4':
        case 'redTonerMono':
            $update_array['colour'] = 'red';
            break;
        case '10':
        case 'cyanToner4Color':
            $update_array['colour'] = 'cyan';
            break;
        case '11':
        case 'magentaToner4Color':
            $update_array['colour'] = 'magenta';
            break;
        case '12':
        case 'yellowToner4Color':
            $update_array['colour'] = 'yellow';
            break;
        case '13':
        case 'blackToner4Color':
            $update_array['colour'] = 'black';
            break;
    }

    discover_printersupply($device, $update_array);
}

echo(PHP_EOL);

// EOF
