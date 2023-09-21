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

/*
CHECKPOINT-MIB::fwNumConn.0 = 1231
CHECKPOINT-MIB::fwPeakNumConn.0 = 93147

CHECKPOINT-MIB::fwAcceptedTotal.0 = "9792350"
CHECKPOINT-MIB::fwAcceptedBytesTotal.0 = "3476997925"
CHECKPOINT-MIB::fwDroppedBytesTotal.0 = "720034"
CHECKPOINT-MIB::fwConnTableLimit.0 = 0
CHECKPOINT-MIB::fwLoggedTotal.0 = "11519593"
CHECKPOINT-MIB::fwRejectedTotal.0 = "162"
CHECKPOINT-MIB::fwRejectedBytesTotal.0 = "7202"
CHECKPOINT-MIB::fwDroppedTotal.0 = "7116"
CHECKPOINT-MIB::fwAcceptedBytesRates.0 = "15171"
CHECKPOINT-MIB::fwAcceptedPcktsRates.0 = "60"
CHECKPOINT-MIB::fwConnsRate.0 = 2147483643
 */

$table_defs['CHECKPOINT-MIB']['fw'] = [
    //'file'          => 'checkpoint-mib_fw.rrd',
    'call_function' => 'snmp_get_multi',
    'mib'           => 'CHECKPOINT-MIB',
    'mib_dir'       => 'checkpoint',
    'table'         => 'fw', // Table fw super-global, use snmp_get_multi_oid()
    'ds_rename'     => ['fw' => '', 'Total' => ''],
    'graphs'        => ['checkpoint_connections', 'checkpoint_packets', 'checkpoint_bytes'],
    'oids'          => [
      'fwNumConn'            => ['descr' => 'Connections', 'ds_type' => 'GAUGE', 'ds_min' => 0],
      'fwPeakNumConn'        => ['descr' => 'Peak Connections', 'ds_type' => 'GAUGE', 'ds_min' => 0],
      'fwConnTableLimit'     => ['descr' => 'Connection Limit', 'ds_type' => 'GAUGE', 'ds_min' => 0],
      'fwAcceptedTotal'      => ['descr' => 'Accepted packets', 'ds_type' => 'COUNTER', 'ds_min' => 0],
      'fwDroppedTotal'       => ['descr' => 'Dropped packets', 'ds_type' => 'COUNTER', 'ds_min' => 0],
      'fwRejectedTotal'      => ['descr' => 'Rejected packets', 'ds_type' => 'COUNTER', 'ds_min' => 0],
      'fwLoggedTotal'        => ['descr' => 'Logged packets', 'ds_type' => 'COUNTER', 'ds_min' => 0],
      'fwAcceptedBytesTotal' => ['descr' => 'Accepted bytes', 'ds_type' => 'COUNTER', 'ds_min' => 0],
      'fwDroppedBytesTotal'  => ['descr' => 'Dropped Bytes', 'ds_type' => 'COUNTER', 'ds_min' => 0],
      'fwRejectedBytesTotal' => ['descr' => 'Rejected Byes', 'ds_type' => 'COUNTER', 'ds_min' => 0],
    ]
];

//CHECKPOINT-MIB::cpvIKECurrSAs.0 = 2
//CHECKPOINT-MIB::cpvIKECurrInitSAs.0 = 1
//CHECKPOINT-MIB::cpvIKECurrRespSAs.0 = 1
//CHECKPOINT-MIB::cpvIKETotalSAs.0 = 2643
//CHECKPOINT-MIB::cpvIKETotalInitSAs.0 = 96
//CHECKPOINT-MIB::cpvIKETotalRespSAs.0 = 2547
//CHECKPOINT-MIB::cpvIKETotalSAsAttempts.0 = 2
//CHECKPOINT-MIB::cpvIKETotalSAsInitAttempts.0 = 1
//CHECKPOINT-MIB::cpvIKETotalSAsRespAttempts.0 = 1
//CHECKPOINT-MIB::cpvIKEMaxConncurSAs.0 = 10
//CHECKPOINT-MIB::cpvIKEMaxConncurInitSAs.0 = 2
//CHECKPOINT-MIB::cpvIKEMaxConncurRespSAs.0 = 10
// These are from the root of fw

$table_defs['CHECKPOINT-MIB']['cpvIKEglobals'] = [
    //'file'     => 'checkpoint-mib_cpvikeglobals.rrd',
    'mib'       => 'CHECKPOINT-MIB',
    'mib_dir'   => 'checkpoint',
    'table'     => 'cpvIKEglobals',
    'ds_rename' => ['cpv' => '', 'Attempts' => 'Att'],
    'graphs'    => ['checkpoint_vpn_sa'],
    'oids'      => [
      'cpvIKECurrSAs'              => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'cpvIKECurrInitSAs'          => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'cpvIKECurrRespSAs'          => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'cpvIKETotalSAs'             => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'cpvIKETotalInitSAs'         => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'cpvIKETotalRespSAs'         => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'cpvIKETotalSAsAttempts'     => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'cpvIKETotalSAsInitAttempts' => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'cpvIKETotalSAsRespAttempts' => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'cpvIKEMaxConncurSAs'        => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'cpvIKEMaxConncurInitSAs'    => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'cpvIKEMaxConncurRespSAs'    => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
    ]
];

//CHECKPOINT-MIB::cpvCurrEspSAsIn.0 = 2
//CHECKPOINT-MIB::cpvTotalEspSAsIn.0 = 19242
//CHECKPOINT-MIB::cpvCurrEspSAsOut.0 = 1
//CHECKPOINT-MIB::cpvTotalEspSAsOut.0 = 19242
//CHECKPOINT-MIB::cpvMaxConncurEspSAsIn.0 = 8
//CHECKPOINT-MIB::cpvMaxConncurEspSAsOut.0 = 2
// These are from cpvIKEGlobals, cpvSaStatistics and cpvStatistics

$table_defs['CHECKPOINT-MIB']['cpvSaStatistics'] = [
    //'file'     => 'checkpoint-mib_cpvsastatistics.rrd',
    'mib'       => 'CHECKPOINT-MIB',
    'mib_dir'   => 'checkpoint',
    'table'     => 'cpvSaStatistics',
    'ds_rename' => ['cpv' => ''],
    'graphs'    => ['checkpoint_vpn_sa'],
    'oids'      => [
      'cpvCurrEspSAsIn'        => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'cpvTotalEspSAsIn'       => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'cpvCurrEspSAsOut'       => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'cpvTotalEspSAsOut'      => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'cpvCurrAhSAsIn'         => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'cpvTotalAhSAsIn'        => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'cpvCurrAhSAsOut'        => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'cpvTotalAhSAsOut'       => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'cpvMaxConncurEspSAsIn'  => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'cpvMaxConncurEspSAsOut' => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'cpvMaxConncurAhSAsIn'   => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'cpvMaxConncurAhSAsOut'  => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
    ]
];

//CHECKPOINT-MIB::cpvEncPackets.0 = 259540277
//CHECKPOINT-MIB::cpvDecPackets.0 = 369845461
//CHECKPOINT-MIB::cpvErrOut.0 = 1
//CHECKPOINT-MIB::cpvErrIn.0 = 27
//CHECKPOINT-MIB::cpvErrIke.0 = 1
//CHECKPOINT-MIB::cpvErrPolicy.0 = 1

$table_defs['CHECKPOINT-MIB']['cpvGeneral'] = [
    //'file'     => 'checkpoint-mib_cpvgeneral.rrd',
    'mib'       => 'CHECKPOINT-MIB',
    'mib_dir'   => 'checkpoint',
    'table'     => 'cpvGeneral',
    'ds_rename' => ['cpv' => ''],
    'graphs'    => ['checkpoint_vpn_packets'],
    'oids'      => [
      'cpvEncPackets' => ['descr' => 'Encrypted packets'],
      'cpvDecPackets' => ['descr' => 'Decrypted packets'],
      'cpvErrOut'     => ['descr' => 'Encryption errors'],
      'cpvErrIn'      => ['descr' => 'Decryption errors'],
      'cpvErrIke'     => ['descr' => 'IKE errors'],
      'cpvErrPolicy'  => ['descr' => 'Policy errors'],
    ]
];

//CHECKPOINT-MIB::fwKmem-system-physical-mem.0 = 0
//CHECKPOINT-MIB::fwKmem-available-physical-mem.0 = 0
//CHECKPOINT-MIB::fwKmem-aix-heap-size.0 = 0
//CHECKPOINT-MIB::fwKmem-bytes-used.0 = 80705044
//CHECKPOINT-MIB::fwKmem-blocking-bytes-used.0 = 352024
//CHECKPOINT-MIB::fwKmem-non-blocking-bytes-used.0 = 80353020
//CHECKPOINT-MIB::fwKmem-bytes-unused.0 = 0
//CHECKPOINT-MIB::fwKmem-bytes-peak.0 = 88392176
//CHECKPOINT-MIB::fwKmem-blocking-bytes-peak.0 = 461708
//CHECKPOINT-MIB::fwKmem-non-blocking-bytes-peak.0 = 87930468
//CHECKPOINT-MIB::fwKmem-bytes-internal-use.0 = 11136
//CHECKPOINT-MIB::fwKmem-number-of-items.0 = 696
//CHECKPOINT-MIB::fwKmem-alloc-operations.0 = 942582
//CHECKPOINT-MIB::fwKmem-free-operations.0 = 941886
//CHECKPOINT-MIB::fwKmem-failed-alloc.0 = 0
//CHECKPOINT-MIB::fwKmem-failed-free.0 = 0

$table_defs['CHECKPOINT-MIB']['fwKmem'] = [
    //'file'      => 'checkpoint-mib_fwkmem.rrd',
    'mib'       => 'CHECKPOINT-MIB',
    'mib_dir'   => 'checkpoint',
    'table'     => 'fwKmem',
    'ds_rename' => ['fw'       => '', 'blocking' => 'blk', 'alloc' => 'alc',
                    'physical' => 'phy', 'non' => 'n', 'internal' => 'int'],
    'graphs'    => ['checkpoint_memory', 'checkpoint_memory_operations'],
    'oids'      => [
      'fwKmem-system-physical-mem'     => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'fwKmem-available-physical-mem'  => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'fwKmem-aix-heap-size'           => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'fwKmem-bytes-used'              => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'fwKmem-blocking-bytes-used'     => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'fwKmem-non-blocking-bytes-used' => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'fwKmem-bytes-unused'            => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'fwKmem-bytes-peak'              => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'fwKmem-blocking-bytes-peak'     => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'fwKmem-non-blocking-bytes-peak' => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'fwKmem-bytes-internal-use'      => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'fwKmem-number-of-items'         => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'fwKmem-alloc-operations'        => [],
      'fwKmem-free-operations'         => [],
      'fwKmem-failed-alloc'            => [],
      'fwKmem-failed-free'             => [],
    ]
];

//CHECKPOINT-MIB::fwHmem-block-size.0 = 4096
//CHECKPOINT-MIB::fwHmem-requested-bytes.0 = 20971520
//CHECKPOINT-MIB::fwHmem-initial-allocated-bytes.0 = 20971520
//CHECKPOINT-MIB::fwHmem-initial-allocated-blocks.0 = 0
//CHECKPOINT-MIB::fwHmem-initial-allocated-pools.0 = 0
//CHECKPOINT-MIB::fwHmem-current-allocated-bytes.0 = 38947108
//CHECKPOINT-MIB::fwHmem-current-allocated-blocks.0 = 9508
//CHECKPOINT-MIB::fwHmem-current-allocated-pools.0 = 3
//CHECKPOINT-MIB::fwHmem-maximum-bytes.0 = 83886080
//CHECKPOINT-MIB::fwHmem-maximum-pools.0 = 10
//CHECKPOINT-MIB::fwHmem-bytes-used.0 = 17387232
//CHECKPOINT-MIB::fwHmem-blocks-used.0 = 4565
//CHECKPOINT-MIB::fwHmem-bytes-unused.0 = 21559876
//CHECKPOINT-MIB::fwHmem-blocks-unused.0 = 4943
//CHECKPOINT-MIB::fwHmem-bytes-peak.0 = 33557112
//CHECKPOINT-MIB::fwHmem-blocks-peak.0 = 8286
//CHECKPOINT-MIB::fwHmem-bytes-internal-use.0 = 111900
//CHECKPOINT-MIB::fwHmem-number-of-items.0 = 99333
//CHECKPOINT-MIB::fwHmem-alloc-operations.0 = 1380168471
//CHECKPOINT-MIB::fwHmem-free-operations.0 = 1380069138
//CHECKPOINT-MIB::fwHmem-failed-alloc.0 = 3376
//CHECKPOINT-MIB::fwHmem-failed-free.0 = 0
// These are from fwHmem and fwKmem

$table_defs['CHECKPOINT-MIB']['fwHmem'] = [
    //'file'      => 'checkpoint-mib_fwhmem.rrd',
    'mib'       => 'CHECKPOINT-MIB',
    'mib_dir'   => 'checkpoint',
    'table'     => 'fwHmem',
    'ds_rename' => ['fw'      => '', 'allocated' => 'alc', 'alloc' => 'alc', 'available' => 'avai',
                    'current' => 'cur', 'initial' => 'ini', 'internal' => 'int'],
    'graphs'    => ['checkpoint_memory', 'checkpoint_memory_operations'],
    'oids'      => [
      'fwHmem-block-size'               => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'fwHmem-requested-bytes'          => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'fwHmem-initial-allocated-bytes'  => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'fwHmem-initial-allocated-blocks' => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'fwHmem-initial-allocated-pools'  => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'fwHmem-current-allocated-bytes'  => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'fwHmem-current-allocated-blocks' => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'fwHmem-current-allocated-pools'  => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'fwHmem-maximum-bytes'            => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'fwHmem-maximum-pools'            => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'fwHmem-bytes-used'               => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'fwHmem-blocks-used'              => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'fwHmem-bytes-unused'             => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'fwHmem-blocks-unused'            => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'fwHmem-bytes-peak'               => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'fwHmem-blocks-peak'              => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'fwHmem-bytes-internal-use'       => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'fwHmem-number-of-items'          => ['ds_type' => 'GAUGE', 'ds_min' => '0'],
      'fwHmem-alloc-operations'         => [],
      'fwHmem-free-operations'          => [],
      'fwHmem-failed-alloc'             => [],
      'fwHmem-failed-free'              => [],
    ]
];

// EOF
