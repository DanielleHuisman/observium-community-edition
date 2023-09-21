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

//BLADE-MIB::bladeCenterVpdMachineType.0 = STRING: "8852"
//BLADE-MIB::bladeCenterVpdMachineModel.0 = STRING: "4TG"
//BLADE-MIB::bladeCenterSerialNumber.0 = STRING: "0674063"
//BLADE-MIB::bladeCenterUUID.0 = STRING: "EBA5 FB6C A0B0 11E0 9025 E41F 1397 7444"
//BLADE-MIB::bladeCenterManufacturingId.0 = STRING: "IBM"
//BLADE-MIB::bladeCenterHardwareRevision.0 = INTEGER: 8
//BLADE-MIB::bladeCenterFruNumber.0 = STRING: "44X2302"
//BLADE-MIB::bladeCenterManufDate.0 = STRING: "24/11"
//BLADE-MIB::bladeCenterPartNumber.0 = STRING: "44X2293"
//BLADE-MIB::bladeCenterFruSerial.0 = STRING: "YK109016C01E"
//BLADE-MIB::bladeCenterManufacturingIDNumber.0 = STRING: "20301"
//BLADE-MIB::bladeCenterProductId.0 = STRING: "254"
//BLADE-MIB::bladeCenterSubManufacturerId.0 = STRING: "FOXC"
//BLADE-MIB::bladeCenterClei.0 = STRING: "Not Available"
//BLADE-MIB::bladeCenterHardwareRevisionString.0 = STRING: "8"

//BLADE-MIB::chassisResponseVersion.0 = INTEGER: 1
//BLADE-MIB::chassisFlags.0 = INTEGER: serverBlade(1)
//BLADE-MIB::chassisName.0 = STRING: "SN#Y010UN13T2N0"
//BLADE-MIB::chassisNoOfFPsSupported.0 = INTEGER: 4
//BLADE-MIB::chassisNoOfPBsSupported.0 = INTEGER: 14
//BLADE-MIB::chassisNoOfSMsSupported.0 = INTEGER: 10
//BLADE-MIB::chassisNoOfMMsSupported.0 = INTEGER: 2
//BLADE-MIB::chassisNoOfPMsSupported.0 = INTEGER: 4
//BLADE-MIB::chassisNoOfMTsSupported.0 = INTEGER: 1
//BLADE-MIB::chassisNoOfBlowersSupported.0 = INTEGER: 2
//BLADE-MIB::chassisPBsInstalled.0 = STRING: "11111111111111"
//BLADE-MIB::chassisSMsInstalled.0 = STRING: "0000001010"
//BLADE-MIB::chassisMMsInstalled.0 = STRING: "11"
//BLADE-MIB::chassisPMsInstalled.0 = STRING: "1111"
//BLADE-MIB::chassisMTInstalled.0 = INTEGER: yes(1)
//BLADE-MIB::chassisBlowersInstalled.0 = STRING: "11"
//BLADE-MIB::chassisActiveMM.0 = INTEGER: 1
//BLADE-MIB::chassisKVMOwner.0 = INTEGER: 1
//BLADE-MIB::chassisMediaTrayOwner.0 = INTEGER: 4
//BLADE-MIB::chassisFPsInstalled.0 = STRING: "1111"
//BLADE-MIB::chassisType.0 = INTEGER: bladeCenterOrBladeCenterH(97)
//BLADE-MIB::chassisSubtype.0 = INTEGER: bladeCenterHOrBladeCenterHT(2)
//BLADE-MIB::chassisNoOfFBsSupported.0 = INTEGER: 0
//BLADE-MIB::chassisNoOfAPsSupported.0 = INTEGER: 0
//BLADE-MIB::chassisNoOfNCsSupported.0 = INTEGER: 0
//BLADE-MIB::chassisNoOfMXsSupported.0 = INTEGER: 0
//BLADE-MIB::chassisNoOfMMIsSupported.0 = INTEGER: 0
//BLADE-MIB::chassisNoOfSMIsSupported.0 = INTEGER: 10
//BLADE-MIB::chassisNoOfFBsInstalled.0 = ""
//BLADE-MIB::chassisNoOfAPsInstalled.0 = STRING: "0"
//BLADE-MIB::chassisNoOfNCsInstalled.0 = ""
//BLADE-MIB::chassisNoOfMXsInstalled.0 = ""
//BLADE-MIB::chassisNoOfMMIsInstalled.0 = ""
//BLADE-MIB::chassisNoOfSMIsInstalled.0 = ""
//BLADE-MIB::chassisNoOfMTsInstalled.0 = STRING: "1"

$data = snmp_get_multi_oid($device, 'bladeCenterVpdMachineType.0 bladeCenterVpdMachineModel.0 bladeCenterSerialNumber.0 chassisFlags.0 chassisType.0 chassisSubtype.0', [], 'BLADE-MIB');

if ($data[0]['chassisFlags'] == 'serverBlade') {
    $type = 'blade';
}
$serial = $data[0]['bladeCenterSerialNumber'];

switch ($data[0]['chassisSubtype']) {
    case 'bladeCenterS':
    case 'bladeCenterE':
    case 'bladeCenterH':
    case 'bladeCenterT':
        $hardware = ucfirst($data[0]['chassisSubtype']);
        break;
    case 'bladeCenterHOrBladeCenterHT':
        if ($data[0]['chassisType'] == 'bladeCenterOrBladeCenterH') {
            $hardware = 'BladeCenterH';
        } else {
            $hardware = 'BladeCenterHT';
        }
        break;
    case 'bladeCenterOrBladeCenterT':
        if ($data[0]['chassisType'] == 'bladeCenterTOrBladeCenterHT') {
            $hardware = 'BladeCenterT';
        } else {
            $hardware = 'BladeCenter';
        }
        break;
    default:
        $hardware = 'BladeCenter';
}
$hardware .= ' ' . $data[0]['bladeCenterVpdMachineType'] . '-' . $data[0]['bladeCenterVpdMachineModel'];

// EOF
