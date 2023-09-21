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

$sql = "SELECT * FROM `printersupplies` WHERE `device_id` = ?";

foreach (dbFetchRows($sql, [$device['device_id']]) as $supply) {
    echo("Checking " . $supply['supply_descr'] . " (" . nicecase($supply['supply_type']) . ")... ");

    // Fetch level and capacity
    $data = snmp_get_multi_oid($device, $supply['supply_oid'] . ' ' . $supply['supply_capacity_oid'], [], NULL, NULL, OBS_SNMP_ALL_NUMERIC);
    print_debug_vars($data);

    // Level
    $level = $data[$supply['supply_oid']];

    if ($supply['supply_mib'] === 'RicohPrivateMIB') {
        if ($level == '-100') {
            // Toner near empty
            $level = '5'; // ~ 1-20%
        } elseif ($level == '-3') {
            $level = '80'; // ~ 10-100%
        }

    } else {

        /**
         * .1.3.6.1.2.1.43.11.1.1.9 prtMarkerSuppliesLevel
         *  The value (-1) means other and specifically indicates that the sub-unit places
         *  no restrictions on this parameter. The value (-2) means unknown.
         *  A value of (-3) means that the printer knows that there is some supply/remaining space,
         *  respectively.
         */
        switch ($level) {
            case '-1':
                $level = 100; // Unlimit
                break;
            case '-2':
                $level = 0;   // Unknown
                break;
            case '-3':
                $level = 80;   // This is wrong SuppliesLevel (1%), but better than nothing
                break;
        }
    }

    // Capacity
    if (!safe_empty($supply['supply_capacity_oid'])) {
        $capacity = $data[$supply['supply_capacity_oid']];
    } elseif ($supply['supply_capacity']) {
        $capacity = $supply['supply_capacity'];
    } else {
        $capacity = 100;
    }

    /**
     * .1.3.6.1.2.1.43.11.1.1.8 prtMarkerSuppliesMaxCapacity
     *  The value (-1) means other and specifically indicates that the sub-unit places
     *  no restrictions on this parameter. The value (-2) means unknown.
     */
    switch ($capacity) {
        case '-1':
            $capacity = 100; // Unlimit
            break;
        //case '-2':
        //  $capacity = 0;   // Unknown
        //  break;
    }

    // Supply percent
    if (is_numeric($level) && $level >= 0 && $capacity > 0) {
        //$supplyperc = round($level / $supply['supply_capacity'] * 100);
        $supplyperc = round($level / $capacity * 100);
    } else {
        $supplyperc = $level;
    }

    echo($supplyperc . " %\n");

    rrdtool_update_ng($device, 'toner', ['level' => $supplyperc], $supply['supply_index']);

    if ($supplyperc > $supply['supply_value'] && $capacity >= 0) {
        log_event('Printer supply ' . $supply['supply_descr'] . ' (type ' . nicecase($supply['supply_type']) . ') was replaced (new level: ' . $supplyperc . '%)', $device, 'toner', $supply['supply_id']);
    }

    // Update DB
    $supply_update = [];
    if ($supply['supply_value'] != $supplyperc) {
        $supply_update['supply_value'] = $supplyperc;
    }
    if ($supply['supply_capacity'] != $capacity) {
        $supply_update['supply_capacity'] = $capacity;
    }
    if ($supply_update) {
        dbUpdate($supply_update, 'printersupplies', '`supply_id` = ?', [$supply['supply_id']]);
    }

    // Check metrics
    /*
    if ($supply['supply_id'] == 777) {
      // DEVEL
      unset($supplyperc);
      print_vars(check_entity('printersupply', $supply, [ 'supply_value' => $supplyperc ], TRUE));
    }
    */
    check_entity('printersupply', $supply, ['supply_value' => $supplyperc]);

    $graphs['printersupplies'] = TRUE;
}

// EOF
