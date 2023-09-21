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

$mib = 'Printer-MIB';

//Printer-MIB::prtMarkerMarkTech.1.1 = INTEGER: electrophotographicLaser(4)
//Printer-MIB::prtMarkerCounterUnit.1.1 = INTEGER: impressions(7)
//Printer-MIB::prtMarkerLifeCount.1.1 = Counter32: 19116
//Printer-MIB::prtMarkerPowerOnCount.1.1 = Counter32: 43
//Printer-MIB::prtMarkerProcessColorants.1.1 = INTEGER: 1
//Printer-MIB::prtMarkerSpotColorants.1.1 = INTEGER: 0
//Printer-MIB::prtMarkerAddressabilityUnit.1.1 = INTEGER: tenThousandthsOfInches(3)
//Printer-MIB::prtMarkerAddressabilityFeedDir.1.1 = INTEGER: 600
//Printer-MIB::prtMarkerAddressabilityXFeedDir.1.1 = INTEGER: 600
//Printer-MIB::prtMarkerNorthMargin.1.1 = INTEGER: 1968
//Printer-MIB::prtMarkerSouthMargin.1.1 = INTEGER: 1968
//Printer-MIB::prtMarkerWestMargin.1.1 = INTEGER: 1968
//Printer-MIB::prtMarkerEastMargin.1.1 = INTEGER: 1968
//Printer-MIB::prtMarkerStatus.1.1 = INTEGER: 2

$oids         = snmpwalk_cache_oid($device, "prtMarkerEntry", [], $mib);
$prt_supplies = snmpwalk_cache_oid($device, 'prtMarkerSuppliesDescription', [], $mib, NULL, OBS_SNMP_ALL_ASCII);
//print_vars($oids);
//$count = count($oids);
//$total_printed_allow = TRUE;
$total_printed_allow = !discovery_check_if_type_exist(['printersupply->KYOCERA-MIB->kcprtMarkerServiceCount'], 'counter');

foreach ($oids as $index => $entry) {
    $printer_supply = dbFetchRow("SELECT * FROM `printersupplies` WHERE `device_id` = ? AND `supply_mib` = ? AND `supply_index` = ?", [$device['device_id'], 'jetdirect', $index]);
    $marker_descr   = "Printed " . nicecase($entry['prtMarkerCounterUnit']);
    [$hrDeviceIndex, $prtMarkerIndex] = explode('.', $index);
    $options = [
      'measured_class'  => 'printersupply',
      'measured_entity' => $printer_supply['supply_id'],
      'counter_unit'    => $entry['prtMarkerCounterUnit']
    ];

    // Lifetime counter (should be always single)
    $descr    = "Total $marker_descr";
    $oid_name = 'prtMarkerLifeCount';
    $oid      = '.1.3.6.1.2.1.43.10.2.1.4.' . $index;
    $value    = $entry[$oid_name];

    if (isset($entry[$oid_name]) && $total_printed_allow) {
        discover_counter($device, 'printersupply', $mib, $oid_name, $oid, $index, $descr, 1, $value, $options);
        $total_printed_allow = FALSE; // Discover only first "Total Printed", all other always same
    }

    // PowerOn counter
    $descr = "PowerOn $marker_descr";
    if ($prt_supplies[$index]['prtMarkerSuppliesDescription']) {
        $descr .= ' - ' . rewrite_entity_name(snmp_hexstring($prt_supplies[$index]['prtMarkerSuppliesDescription']));
    }
    $oid_name = 'prtMarkerPowerOnCount';
    $oid      = '.1.3.6.1.2.1.43.10.2.1.5.' . $index;
    $value    = $entry[$oid_name];

    discover_counter($device, 'printersupply', $mib, $oid_name, $oid, $index, rewrite_entity_name($descr), 1, $value, $options);

    // prtMarkerStatus
    // FIXME, binary statuses currently unsupported
}

// EOF
