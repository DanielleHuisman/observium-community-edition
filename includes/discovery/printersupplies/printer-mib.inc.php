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

$prt_supplies = snmpwalk_cache_oid($device, 'prtMarkerSuppliesTable', [], 'Printer-MIB', NULL, OBS_SNMP_ALL_UTF8);
$prt_colorant = snmpwalk_cache_twopart_oid($device, 'prtMarkerColorantTable', [], 'Printer-MIB', NULL, OBS_SNMP_ALL_UTF8);

print_debug_vars($prt_supplies);
print_debug_vars($prt_colorant);

// Count toner/ink, if Toner not has colorant table or name non informative (ie: TK-340) set to black for single toner
$toner_count = 0;
foreach ($prt_supplies as $entry) {
    switch ($entry['prtMarkerSuppliesType']) {
        case 'toner':
        case 'tonerCartridge':
        case 'ink':
        case 'inkCartridge':
            $toner_count++;
            break;
    }
}

foreach ($prt_supplies as $index => $entry) {
    // prtMarkerSuppliesMarkerIndex.1.1 = 1
    // prtMarkerSuppliesMarkerIndex.1.8 = 1
    // prtMarkerSuppliesMarkerIndex.1.9 = 1
    // prtMarkerSuppliesColorantIndex.1.1 = 1
    // prtMarkerSuppliesColorantIndex.1.8 = 4
    // prtMarkerSuppliesColorantIndex.1.9 = 0
    // prtMarkerSuppliesClass.1.1 = supplyThatIsConsumed
    // prtMarkerSuppliesClass.1.8 = supplyThatIsConsumed
    // prtMarkerSuppliesClass.1.9 = receptacleThatIsFilled
    // prtMarkerSuppliesType.1.1 = toner
    // prtMarkerSuppliesType.1.8 = opc
    // prtMarkerSuppliesType.1.9 = wasteToner
    // prtMarkerSuppliesDescription.1.1 = "Cyan Toner Cartridge"
    // prtMarkerSuppliesDescription.1.8 = "Black Drum Cartridge"
    // prtMarkerSuppliesDescription.1.9 = "Waste Toner Box"
    // prtMarkerSuppliesSupplyUnit.1.1 = impressions
    // prtMarkerSuppliesSupplyUnit.1.8 = impressions
    // prtMarkerSuppliesSupplyUnit.1.9 = impressions
    // prtMarkerSuppliesMaxCapacity.1.1 = 12000
    // prtMarkerSuppliesMaxCapacity.1.8 = 50000
    // prtMarkerSuppliesMaxCapacity.1.9 = 25000
    // prtMarkerSuppliesLevel.1.1 = 12000
    // prtMarkerSuppliesLevel.1.8 = 20000
    // prtMarkerSuppliesLevel.1.9 = 25000

    // prtMarkerColorantMarkerIndex.1.1 = 1
    // prtMarkerColorantRole.1.1 = process
    // prtMarkerColorantValue.1.1 = "cyan"
    // prtMarkerColorantTonality.1.1 = 256

    //$descr        = snmp_hexstring($entry['prtMarkerSuppliesDescription']); // Some HPs return a Hex-string.
    $descr        = rewrite_entity_name($entry['prtMarkerSuppliesDescription']); // Forced ASCII
    $oid          = ".1.3.6.1.2.1.43.11.1.1.9.$index";
    $capacity_oid = ".1.3.6.1.2.1.43.11.1.1.8.$index";

    /* .1.3.6.1.2.1.43.11.1.1.8 prtMarkerSuppliesMaxCapacity
     *  The value (-1) means other and specifically indicates that the sub-unit places
     *  no restrictions on this parameter. The value (-2) means unknown.
     *
     * .1.3.6.1.2.1.43.11.1.1.9 prtMarkerSuppliesLevel
     *  The value (-1) means other and specifically indicates that the sub-unit places
     *  no restrictions on this parameter. The value (-2) means unknown.
     *  A value of (-3) means that the printer knows that there is some supply/remaining space,
     *  respectively.
     */

    $level    = $entry['prtMarkerSuppliesLevel'];
    $capacity = $entry['prtMarkerSuppliesMaxCapacity'];

    if (($level < 0 || $capacity < 0) && is_device_mib($device, 'RicohPrivateMIB')) {
        // Skip unknown toners on ricoh printers (use self mib)
        continue;
    }

    if ($level == '-1' || $capacity == '-1') {
        // Unlimited
        $level    = 100;
        $capacity = 100;
    }

    if ($capacity > 0 && $level >= 0) {
        $level = percent($level, $capacity, FALSE);
    }

    switch ($entry['prtMarkerSuppliesSupplyUnit']) {
        // other(1), unknown(2), tenThousandthsOfInches(3), micrometers(4),
        // impressions(7), sheets(8), hours(11), thousandthsOfOunces(12),
        // tenthsOfGrams(13), hundrethsOfFluidOunces(14), tenthsOfMilliliters(15),
        // feet(16), meters(17),
        // -- Values for Finisher MIB
        // items(18), percent(19)
        default:
            $unit = 'percent';
    }
    $update_array = [
      'description'  => $descr,
      'index'        => $index,
      'oid'          => $oid,
      'mib'          => 'jetdirect', // FIXME - this confuses, use correct mib name in the future
      //'mib'           => $mib,
      'unit'         => $unit,
      'level'        => $level,
      'capacity'     => $capacity,
      'capacity_oid' => $capacity_oid
    ];

    // Attach Colorant entry if exist
    [$hrDeviceIndex, $prtMarkerSuppliesIndex] = explode('.', $index);
    if (isset($entry['prtMarkerSuppliesColorantIndex']) &&
        isset($prt_colorant[$hrDeviceIndex][$entry['prtMarkerSuppliesColorantIndex']])) {
        $entry = array_merge($entry, $prt_colorant[$hrDeviceIndex][$entry['prtMarkerSuppliesColorantIndex']]);
        if (isset($entry['prtMarkerColorantValue'])) {
            //$update_array['colour'] = snmp_hexstring($entry['prtMarkerColorantValue']);
            $update_array['colour'] = $entry['prtMarkerColorantValue']; // Forced ASCII
        }
    }

    // Colorant table empty, try detect colour by description
    if (!isset($update_array['colour'])) {
        foreach ($GLOBALS['config']['toner'] as $colour => $strings) {
            if (str_icontains_array($descr, (array)$strings)) {
                $update_array['colour'] = $colour;
                break;
            }
        }
    }

    switch ($entry['prtMarkerSuppliesType']) {
        case 'toner':
        case 'tonerCartridge':
        case 'ink':
        case 'inkCartridge':
            if (!isset($update_array['colour']) && $toner_count === 1) {
                $update_array['colour'] = 'black';
            }
            $update_array['type'] = str_replace('Cartridge', '', $entry['prtMarkerSuppliesType']);
            break;
        case 'opc':
            // photo conductor
            $update_array['type'] = 'drum';
            break;
        case 'wasteToner':
        case 'wasteInk':
        case 'transferUnit':
        case 'cleanerUnit':
        case 'fuser':
        case 'developer':
        case 'other':
            // Known types
            $update_array['type'] = strtolower($entry['prtMarkerSuppliesType']);
            break;
        default:
            print_debug("Could not handle resource type " . $entry['prtMarkerSuppliesType']);
            continue 2;
    }

    // Skip duplicate toners, see: https://jira.observium.org/browse/OBS-3971
    $update_array['skip_if_valid_exist'] = $update_array['type'] . '->RicohPrivateMIB';

    discover_printersupply($device, $update_array);
}

echo(PHP_EOL);

// EOF
