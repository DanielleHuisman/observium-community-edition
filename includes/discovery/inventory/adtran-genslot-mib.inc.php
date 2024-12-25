<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) Adam Armstrong
 *
 */

// ADTRAN-MIB::adProdName.0 = STRING: TA5000 23 CH
// ADTRAN-MIB::adProdPartNumber.0 = STRING: 1187001F2
// ADTRAN-MIB::adProdCLEIcode.0 = STRING: BVM2V10GRD
// ADTRAN-MIB::adProdSerialNumber.0 = STRING: LBADTN2320XXXXX
// ADTRAN-MIB::adProdRevision.0 = STRING: G
// ADTRAN-MIB::adProdSwVersion.0 = STRING:
// ADTRAN-MIB::adProdPhysAddress.0 = STRING: 30:30:20:30:30:20:30:30:20:30:30
// ADTRAN-MIB::adProdProductID.0 = OID: ADTRAN-MIB::adProducts.747
// ADTRAN-MIB::adProdTransType.0 = STRING: EQPT

if ($ch = snmpwalk_cache_oid($device, 'adProductInfo', [], 'ADTRAN-MIB')) {
    $ch = $ch[0];
} else {
    return;
}

// Chassis
$index             = 1;
$inventory[$index] = [
    'entPhysicalName'         => $ch['adProdName'],
    'entPhysicalDescr'        => $ch['adProdName'],
    'entPhysicalClass'        => 'chassis',
    'entPhysicalIsFRU'        => 'true',
    'entPhysicalModelName'    => $ch['adProdPartNumber'],
    'entPhysicalSerialNum'    => $ch['adProdSerialNumber'],
    'entPhysicalFirmwareRev'  => $ch['adProdSwVersion'],
    'entPhysicalHardwareRev'  => $ch['adProdRevision'],
    'entPhysicalAssetID'      => $ch['adProdCLEIcode'],
    'entPhysicalContainedIn'  => 0,
    'entPhysicalParentRelPos' => -1,
    'entPhysicalMfgName'      => 'AdTran'
];
discover_inventory($device, $index, $inventory[$index], $mib);

// ADTRAN-GENSLOT-MIB::adGenSlotInfoIndex.252 = INTEGER: 252
// ADTRAN-GENSLOT-MIB::adGenSlotInfoState.252 = INTEGER: occupied(3)
// ADTRAN-GENSLOT-MIB::adGenSlotProduct.252 = INTEGER: adTA5kSwM20GBp10F1(1310)
// ADTRAN-GENSLOT-MIB::adGenSlotTrapEnable.252 = INTEGER: disableTraps(2)
// ADTRAN-GENSLOT-MIB::adGenSlotAlarmStatus.252 = Hex-STRING: 80
// ADTRAN-GENSLOT-MIB::adGenSlotFaceplate.252 = Hex-STRING: 0E 00 05 10 40
// ADTRAN-GENSLOT-MIB::adGenSlotStatServiceState.252 = INTEGER: is(1)
// ADTRAN-GENSLOT-MIB::adGenSlotPortNumber.252 = INTEGER: 28
// ADTRAN-GENSLOT-MIB::adGenSlotProvVersion.252 = INTEGER: 42
// ADTRAN-GENSLOT-MIB::adGenSlotTFileName.252 = STRING: 1187040F1-L150452.glb
// ADTRAN-GENSLOT-MIB::adGenSlotUpdateSoftware.252 = INTEGER: initiate(1)
// ADTRAN-GENSLOT-MIB::adGenSlotUpdateStatus.252 = STRING: SHELF:Idle, COUNT:44, TFTP:Complete, YMODEM:Idle, LINECARD:(Idle)
// ADTRAN-GENSLOT-MIB::adGenSlotUpTime.252 = Timeticks: (240809074) 27 days, 20:54:50.74
// ADTRAN-GENSLOT-MIB::adGenSlotPrimaryBuildDate.252 = STRING: Fri May  3 20:15:19 2024
// ADTRAN-GENSLOT-MIB::adGenSlotResetCause.252 = STRING: Warm Start
// ADTRAN-GENSLOT-MIB::adGenSlotWarmStartCauseIsValid.252 = INTEGER: true(1)
// ADTRAN-GENSLOT-MIB::adGenSlotWarmStartCause.252 = STRING: SCM CLI
// ADTRAN-GENSLOT-MIB::adGenSlotUpTimeSeconds.252 = Counter32: 2408090

// ADTRAN-GENSLOT-MIB::adGenSlotProdName.252 = STRING: SM 4-10G
// ADTRAN-GENSLOT-MIB::adGenSlotProdPartNumber.252 = STRING: 1187040F1
// ADTRAN-GENSLOT-MIB::adGenSlotProdCLEIcode.252 = STRING: BVC1AC6ETA
// ADTRAN-GENSLOT-MIB::adGenSlotProdSerialNumber.252 = STRING: LBADTN2039AA778
// ADTRAN-GENSLOT-MIB::adGenSlotProdRevision.252 = STRING: N
// ADTRAN-GENSLOT-MIB::adGenSlotProdSwVersion.252 = STRING: L15.0452
// ADTRAN-GENSLOT-MIB::adGenSlotProdPhysAddress.252 = STRING: 1:1:fc
// ADTRAN-GENSLOT-MIB::adGenSlotProdProductID.252 = OID: ADTRAN-MIB::adProducts.1310
// ADTRAN-GENSLOT-MIB::adGenSlotProdTransType.252 = STRING: GigE

$oids = snmpwalk_cache_oid($device, 'adGenSlotProdTable', [], $mib);

$rel = 1;
foreach ($oids as $index => $entry) {

    $inventory[$index] = [
        'entPhysicalName'         => $entry['adGenSlotProdName'],
        'entPhysicalDescr'        => $entry['adGenSlotProdName'],
        'entPhysicalClass'        => 'slot',
        'entPhysicalIsFRU'        => 'true',
        'entPhysicalModelName'    => $entry['adGenSlotProdPartNumber'],
        'entPhysicalVendorType'   => $entry['adGenSlotProdTransType'],
        'entPhysicalSerialNum'    => $entry['adGenSlotProdSerialNumber'],
        'entPhysicalFirmwareRev'  => $entry['adGenSlotProdSwVersion'],
        'entPhysicalHardwareRev'  => $entry['adGenSlotProdRevision'],
        'entPhysicalAssetID'      => $entry['adGenSlotProdCLEIcode'],
        'entPhysicalContainedIn'  => 1,
        'entPhysicalParentRelPos' => $rel,
        'entPhysicalMfgName'      => 'AdTran'
    ];
    discover_inventory($device, $index, $inventory[$index], $mib);

    $i++;
}

if ($oids = snmp_cache_table($device, 'adGenPluggablePortTable', [], 'ADTRAN-PLUGGABLE-PORT-MIB')) { // also in inventory
    //$oids = snmpwalk_cache_oid($device, 'adGenPortSlotMapTable', $oids, 'ADTRAN-GENPORT-MIB');

    foreach ($oids as $index => $entry) {

        if ($entry['adGenPluggablePortConnectorType'] === 'none') {
            // Not exist DDM
            continue;
        }

        $slotmap = snmp_get_multi_oid($device, [ 'adGenSlotAddress.'.$index, 'adGenPortAddress.'.$index ], [], 'ADTRAN-GENPORT-MIB');
        $slotmap = $slotmap[$index];

        $vendor_name = trim($entry['adGenPluggablePortVendorName'] . ' ' . $entry['adGenPluggablePortVendorPartNumber']);
        $inventory[$index] = [
            'entPhysicalName'         => $vendor_name,
            'entPhysicalDescr'        => $vendor_name,
            'entPhysicalClass'        => 'port',
            'entPhysicalIsFRU'        => 'true',
            'entPhysicalModelName'    => $entry['adGenPluggablePortVendorPartNumber'],
            'entPhysicalVendorType'   => $entry['adGenPluggablePortConnectorType'],
            'entPhysicalSerialNum'    => $entry['adGenPluggablePortVendorSerialNumber'],
            //'entPhysicalFirmwareRev'  => $entry['adGenSlotProdSwVersion'],
            'entPhysicalHardwareRev'  => $entry['adGenPluggablePortVendorRevision'],
            'entPhysicalAssetID'      => $entry['adGenPluggablePortAdtranClei'],
            'entPhysicalContainedIn'  => $slotmap['adGenSlotAddress'],
            'entPhysicalParentRelPos' => $slotmap['adGenPortAddress'],
            'entPhysicalMfgName'      => $entry['adGenPluggablePortVendorName'],
            'ifIndex'                 => $index
        ];
        discover_inventory($device, $index, $inventory[$index], $mib);
    }
}

// EOF
