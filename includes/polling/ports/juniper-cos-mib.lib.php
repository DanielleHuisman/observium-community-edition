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

// JUNIPER-COS-MIB functions

// Per-port COS Queue Statistics

/*
JUNIPER-COS-MIB::jnxCosQstatQedPkts[526][0] = Counter64: 17940450
JUNIPER-COS-MIB::jnxCosQstatQedPktRate[526][0] = Counter64: 2
JUNIPER-COS-MIB::jnxCosQstatQedBytes[526][0] = Counter64: 2960949944
JUNIPER-COS-MIB::jnxCosQstatQedByteRate[526][0] = Counter64: 192
JUNIPER-COS-MIB::jnxCosQstatTxedPkts[526][0] = Counter64: 17940458
JUNIPER-COS-MIB::jnxCosQstatTxedPktRate[526][0] = Counter64: 2
JUNIPER-COS-MIB::jnxCosQstatTxedBytes[526][0] = Counter64: 2960950728
JUNIPER-COS-MIB::jnxCosQstatTxedByteRate[526][0] = Counter64: 800
JUNIPER-COS-MIB::jnxCosQstatTailDropPkts[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatTailDropPktRate[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatTotalRedDropPkts[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatTotalRedDropPktRate[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatLpNonTcpRedDropPkts[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatLpNonTcpRDropPktRate[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatLpTcpRedDropPkts[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatLpTcpRedDropPktRate[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatHpNonTcpRedDropPkts[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatHpNonTcpRDropPktRate[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatHpTcpRedDropPkts[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatHpTcpRedDropPktRate[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatTotalRedDropBytes[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatTotalRedDropByteRate[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatLpNonTcpRedDropBytes[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatLpNonTcpRDropByteRate[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatLpTcpRedDropBytes[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatLpTcpRedDropByteRate[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatHpNonTcpRedDropBytes[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatHpNonTcpRDropByteRate[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatHpTcpRedDropBytes[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatHpTcpRedDropByteRate[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatLpRedDropPkts[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatLpRedDropPktRate[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatMLpRedDropPkts[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatMLpRedDropPktRate[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatMHpRedDropPkts[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatMHpRedDropPktRate[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatHpRedDropPkts[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatHpRedDropPktRate[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatLpRedDropBytes[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatLpRedDropByteRate[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatMLpRedDropBytes[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatMLpRedDropByteRate[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatMHpRedDropBytes[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatMHpRedDropByteRate[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatHpRedDropBytes[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatHpRedDropByteRate[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatRateLimitDropPkts[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatRateLimitDropPktRate[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatRateLimitDropBytes[526][0] = Counter64: 0
JUNIPER-COS-MIB::jnxCosQstatRateLimitDropByteRate[526][0] = Counter64: 0
*/

function process_port_jnx_cos_qstat(&$this_port, $device, $port)
{

    if (isset($this_port['jnx_cos_qstat']) && count($this_port['jnx_cos_qstat'])) {

        $queues = [];

        foreach ($this_port['jnx_cos_qstat'] as $q_index => $q_stats) {

            rrdtool_update_ng($device, 'port-jnx_cos_qstat', [
              'QedPkts'           => $q_stats['jnxCosQstatQedPkts'],
              'QedBytes'          => $q_stats['jnxCosQstatQedBytes'],
              'TxedPkts'          => $q_stats['jnxCosQstatTxedPkts'],
              'TxedBytes'         => $q_stats['jnxCosQstatTxedBytes'],
              'TailDropPkts'      => $q_stats['jnxCosQstatTailDropPkts'],
              'TotalRedDropPkts'  => $q_stats['jnxCosQstatTotalRedDropPkts'],
              'TotalRedDropBytes' => $q_stats['jnxCosQstatTotalRedDropBytes'],
            ],                get_port_rrdindex($port) . '-' . $q_index);

            $queues[] = $q_index;

            /*
            if ($GLOBALS['config']['statsd']['enable'])
            {
              foreach ($adsl_oids as $oid)
              {
                // Update StatsD/Carbon
                StatsD::gauge(str_replace(".", "_", $device['hostname']).'.'.'port'.'.'.$port['ifIndex'].'.'.$oid, $this_port[$oid]);
              }
            } */

        }

        set_entity_attrib('port', $port['port_id'], 'jnx_cos_queues', json_encode($queues));

    }

    // FIXME -- remove attrib if it doesn't exist.

}
