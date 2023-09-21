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

// BLUECOAT-CAS-MIB blueCoatCasMibObjects

// BLUECOAT-CAS-MIB::casFilesScanned.0 = Counter64: 81710
// BLUECOAT-CAS-MIB::casVirusesDetected.0 = Counter64: 1
// BLUECOAT-CAS-MIB::casSlowICAPConnections.0 = Gauge32: 0
// BLUECOAT-CAS-MIB::casICAPFilesScanned.0 = Counter64: 81710
// BLUECOAT-CAS-MIB::casICAPVirusesDetected.0 = Counter64: 1
// BLUECOAT-CAS-MIB::casSecureICAPFilesScanned.0 = Counter64: 0
// BLUECOAT-CAS-MIB::casSecureICAPVirusesDetected.0 = Counter64: 0
// BLUECOAT-CAS-MIB::casCacheHits.0 = Counter64: 19022
// BLUECOAT-CAS-MIB::casConnections.0 = Counter64: 0
// BLUECOAT-CAS-MIB::casBytesScanned.0 = Counter64: 1463237962


$table_defs['BLUECOAT-CAS-MIB']['blueCoatCasMibObjects'] = [
  'file'          => 'cas.rrd',
  'call_function' => 'snmp_get',
  'mib'           => 'BLUECOAT-CAS-MIB',
  'mib_dir'       => 'bluecoat',
  'table'         => 'blueCoatCasMibObjects',
  'ds_rename'     => [
    'cas' => '',
  ],
  'graphs'        => ['cas_files_scanned', 'cas_virus_detected', 'cas_slow_icap', 'cas_icap_scanned', 'cas_icap_virus', 'cas_sicap_scanned', 'cas_sicap_virus'],
  'oids'          => [
    'casFilesScanned'              => ['descr' => 'Files Scanned', 'ds_type' => 'COUNTER', 'ds_min' => '0'],
    'casVirusesDetected'           => ['descr' => 'Viruses Detected', 'ds_type' => 'COUNTER', 'ds_min' => '0'],
    'casSlowICAPConnections'       => ['descr' => 'Slow ICAP Connections', 'ds_type' => 'GAUGE', 'ds_min' => '0'],
    'casICAPFilesScanned'          => ['descr' => 'ICAP Files Scanned', 'ds_type' => 'COUNTER', 'ds_min' => '0'],
    'casICAPVirusesDetected'       => ['descr' => 'ICAP Viruses Detected', 'ds_type' => 'COUNTER', 'ds_min' => '0'],
    'casSecureICAPFilesScanned'    => ['descr' => 'Secure ICAP Files Scanned', 'ds_type' => 'COUNTER', 'ds_min' => '0'],
    'casSecureICAPVirusesDetected' => ['descr' => 'Secure ICAP Viruses Detected', 'ds_type' => 'COUNTER', 'ds_min' => '0'],
  ]
];

// EOF
