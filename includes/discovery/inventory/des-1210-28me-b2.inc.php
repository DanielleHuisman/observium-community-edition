<?php
if (is_device_mib($device, 'DES-1210-28ME-B2')) {
    $vendor_mib  = 'DES-1210-28ME-B2';
    $vendor_oids = snmpwalk_cache_oid($device, "sfpVendorInfoTable", $vendor_oids, $vendor_mib, NULL, $snmp_flags);
    print_debug_vars($vendor_oids);

    $copperports = 24;
    $comboports  = 2;
    $fiberports  = 2;
} else {
    return;
}

echo($vendor_mib);
$revision = snmp_get_oid($device, 'probeHardwareRev.0', 'RMON2-MIB');

$system_index             = 1;
$inventory[$system_index] = [
  'entPhysicalDescr'        => $device['sysDescr'],
  'entPhysicalClass'        => 'chassis',
  'entPhysicalName'         => $device['hardware'],
  'entPhysicalHardwareRev'  => $revision,
  'entPhysicalSoftwareRev'  => $device['version'],
  'entPhysicalIsFRU'        => 'true',
  'entPhysicalModelName'    => $device['hardware'],
  'entPhysicalSerialNum'    => $device['serial'],
  'entPhysicalContainedIn'  => 0,
  'entPhysicalParentRelPos' => 1,
  'entPhysicalMfgName'      => 'D-Link',
];
discover_inventory($device, $system_index, $inventory[$system_index], $vendor_mib);

/*
DES-1210-28ME-B2::companySfpVendorInfo

sfpPortIndex.27 = 27
sfpPortIndex.28 = 28
sfpConnectorType.27 = SFP - MT-RJ
sfpConnectorType.28 = SFP - SC
sfpTranceiverCode.27 = 
sfpTranceiverCode.28 = Unallocated
sfpBaudRate.27 = d
sfpBaudRate.28 = d
sfpVendorName.27 = 
sfpVendorName.28 = 
sfpVendorOui.27 =  0:90:65
sfpVendorOui.28 =  0: 0: 0
sfpVendorPn.27 = SFP-1.25G-1310  
sfpVendorPn.28 = TBSF15d1012gSC3c
sfpVendorRev.27 = A0  
sfpVendorRev.28 = A   
sfpWavelength.27 = 51e
sfpWavelength.28 = 60e
sfpVendorSn.27 = SC91750197      
sfpVendorSn.28 = F201705111103   
sfpDateCode.27 = 120904  
sfpDateCode.28 = 170512  

DES-1210-28ME-B2::sfpPortIndex.25 = INTEGER: 25
DES-1210-28ME-B2::sfpPortIndex.26 = INTEGER: 26
DES-1210-28ME-B2::sfpPortIndex.28 = INTEGER: 28
DES-1210-28ME-B2::sfpConnectorType.25 = STRING: "SFP - SC"
DES-1210-28ME-B2::sfpConnectorType.26 = STRING: "SFP - SC"
DES-1210-28ME-B2::sfpConnectorType.28 = STRING: "SFP - SC"
DES-1210-28ME-B2::sfpTranceiverCode.25 = STRING: "Unallocated"
DES-1210-28ME-B2::sfpTranceiverCode.26 = STRING: "Single Mode"
DES-1210-28ME-B2::sfpTranceiverCode.28 = STRING: "Single Mode"
DES-1210-28ME-B2::sfpBaudRate.25 = STRING: "d"
DES-1210-28ME-B2::sfpBaudRate.26 = STRING: "d"
DES-1210-28ME-B2::sfpBaudRate.28 = STRING: "d"
DES-1210-28ME-B2::sfpVendorName.25 = ""
DES-1210-28ME-B2::sfpVendorName.26 = ""
DES-1210-28ME-B2::sfpVendorName.28 = ""
DES-1210-28ME-B2::sfpVendorOui.25 = STRING: " 0: 0: 0"
DES-1210-28ME-B2::sfpVendorOui.26 = STRING: " 0: 0: 0"
DES-1210-28ME-B2::sfpVendorOui.28 = STRING: " 0: 0: 0"
DES-1210-28ME-B2::sfpVendorPn.25 = STRING: "TBSF13312gSC3cDD"
DES-1210-28ME-B2::sfpVendorPn.26 = STRING: "SFP-BIDI        "
DES-1210-28ME-B2::sfpVendorPn.28 = STRING: "AP-B53121-3CS3  "
DES-1210-28ME-B2::sfpVendorRev.25 = STRING: "A   "
DES-1210-28ME-B2::sfpVendorRev.26 = STRING: "1.0 "
DES-1210-28ME-B2::sfpVendorRev.28 = STRING: "1.00"
DES-1210-28ME-B2::sfpWavelength.25 = STRING: "51e"
DES-1210-28ME-B2::sfpWavelength.26 = STRING: "60e"
DES-1210-28ME-B2::sfpWavelength.28 = STRING: "60e"
DES-1210-28ME-B2::sfpVendorSn.25 = STRING: "F201705113806   "
DES-1210-28ME-B2::sfpVendorSn.26 = STRING: "G181687         "
DES-1210-28ME-B2::sfpVendorSn.28 = STRING: "SG53E20800234   "
DES-1210-28ME-B2::sfpDateCode.25 = STRING: "170517  "
DES-1210-28ME-B2::sfpDateCode.26 = STRING: "180916  "
DES-1210-28ME-B2::sfpDateCode.28 = STRING: "120224  "
*/

$totalports = $copperports + $comboports + $fiberports;
for ($i = 1; $i <= $totalports; $i++) {
    $system_index = 100 + $i;
    if ($i <= $copperports) {
        $inventory[$system_index] = [
          'entPhysicalDescr'        => '100Base-T Copper Port',
          'entPhysicalClass'        => 'port',
          'entPhysicalName'         => 'Port ' . $i,
          'entPhysicalIsFRU'        => 'false',
          'entPhysicalModelName'    => 'Copper Port',
          'entPhysicalContainedIn'  => 1,
          'entPhysicalParentRelPos' => $i,
          'ifIndex'                 => $i,
        ];
    } else {
        if ($i <= $copperports + $comboports) {
            $portdescr = 'Combo Port';
        } else {
            $portdescr = 'SFP Port';
        }
        $inventory[$system_index] = [
          'entPhysicalDescr'        => $portdescr,
          'entPhysicalClass'        => 'port',
          'entPhysicalName'         => 'Port ' . $i,
          'entPhysicalIsFRU'        => 'false',
          'entPhysicalModelName'    => trim($vendor_oids[$i]['sfpTranceiverCode']),
          'entPhysicalContainedIn'  => 1,
          'entPhysicalParentRelPos' => $i,
          'ifIndex'                 => $i,
          'entPhysicalVendorType'   => trim($vendor_oids[$i]['sfpConnectorType']),
          'entPhysicalSerialNum'    => trim($vendor_oids[$i]['sfpVendorSn']),
          'entPhysicalHardwareRev'  => trim($vendor_oids[$i]['sfpVendorPn']),
          'entPhysicalFirmwareRev'  => trim($vendor_oids[$i]['sfpVendorRev']),
          'entPhysicalMfgName'      => trim($vendor_oids[$i]['sfpVendorName']),
        ];
    }
    discover_inventory($device, $system_index, $inventory[$system_index], $vendor_mib);
}
print_debug_vars($inventory);

// EOF
