<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

$cache_discovery['netapp-mib'] = snmpwalk_cache_oid($device, "dfEntry", array(), 'NETAPP-MIB');

if (safe_count($cache_discovery['netapp-mib'])) {
  /*
  Available data:

  array(40) {
    ["dfIndex"]=>                  "3"
    ["dfFileSys"]=>                "/vol/vol0/"
    ["dfKBytesTotal"]=>            "158387408"
    ["dfKBytesUsed"]=>             "7600652"
    ["dfKBytesAvail"]=>            "150786756"
    ["dfPerCentKBytesCapacity"]=>  "5"
    ["dfInodesUsed"]=>             "9405"
    ["dfInodesFree"]=>             "14517028"
    ["dfPerCentInodeCapacity"]=>   "0"
    ["dfMountedOn"]=>              "/vol/vol0/"
    ["dfMaxFilesAvail"]=>          "14526433"
    ["dfMaxFilesUsed"]=>           "9405"
    ["dfMaxFilesPossible"]=>       "39596840"
    ["dfHighTotalKBytes"]=>        "0"
    ["dfLowTotalKBytes"]=>         "158387408"
    ["dfHighUsedKBytes"]=>         "0"
    ["dfLowUsedKBytes"]=>          "7600652"
    ["dfHighAvailKBytes"]=>        "0"
    ["dfLowAvailKBytes"]=>         "150786756"
    ["dfStatus"]=>                 "mounted"
    ["dfMirrorStatus"]=>           "invalid"
    ["dfPlexCount"]=>              "0"
    ["dfType"]=>                   "flexibleVolume"
    ["dfHighSisSharedKBytes"]=>    "0"
    ["dfLowSisSharedKBytes"]=>     "0"
    ["dfHighSisSavedKBytes"]=>     "0"
    ["dfLowSisSavedKBytes"]=>      "0"
    ["dfPerCentSaved"]=>           "0"
    ["df64TotalKBytes"]=>          "158387408"
    ["df64UsedKBytes"]=>           "7600644"
    ["df64AvailKBytes"]=>          "150786764"
    ["df64SisSharedKBytes"]=>      "0"
    ["df64SisSavedKBytes"]=>       "0"
    ["df64CompressSaved"]=>        "0"
    ["dfCompressSavedPercent"]=>   "0"
    ["df64DedupeSaved"]=>          "0"
    ["dfDedupeSavedPercent"]=>     "0"
    ["df64TotalSaved"]=>           "0"
    ["dfTotalSavedPercent"]=>      "0"
    ["df64TotalReservedKBytes"]=>  "263724"
  }

  */

  foreach ($cache_discovery['netapp-mib'] as $index => $storage)
  {
    $fstype = $storage['dfType'];
    $descr  = $storage['dfFileSys'];
    if (!empty($storage['dfVserver']))
    {
      // Add server info on cluster devices
      $descr .= ' - ' . $storage['dfVserver'];
    }
    $deny   = FALSE;

    if (!$deny)
    {
      if (is_numeric($storage['df64TotalKBytes']))
      {
        $size = $storage['df64TotalKBytes'] * 1024;
        $used = $storage['df64UsedKBytes'] * 1024;
        $hc = 1;
      } else {
        $size = $storage['dfKBytesTotal'] * 1024;
        $used = $storage['dfKBytesUsed'] * 1024;
        $hc = 0;
      }

      if (is_numeric($index))
      {
        discover_storage($valid['storage'], $device, $index, $fstype, 'NETAPP-MIB', $descr, 1024, $size, $used, array('storage_hc' => $hc));
      }
    }
    unset($deny, $fstype, $descr, $size, $used, $free, $percent, $hc);
  }
  unset($index, $storage);
}

// EOF
