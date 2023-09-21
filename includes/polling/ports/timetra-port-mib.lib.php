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

// TIMETRA-MIB-MIB functions

// Per-port Egress Queue Statistics

function process_port_sros_egress_qstat(&$this_port, $device, $port)
{

    if (isset($this_port['sros_egress_qstat']) && count($this_port['sros_egress_qstat'])) {

        $queues = [];

        foreach ($this_port['sros_egress_qstat'] as $q_index => $q_stats) {

            rrdtool_update_ng($device, 'port-sros_egress_qstat', [
              'FwdInProfPkts'  => $q_stats['tmnxPortNetEgressFwdInProfPkts'],
              'FwdOutProfPkts' => $q_stats['tmnxPortNetEgressFwdOutProfPkts'],
              'FwdInProfOcts'  => $q_stats['tmnxPortNetEgressFwdInProfOcts'],
              'FwdOutProfOcts' => $q_stats['tmnxPortNetEgressFwdOutProfOcts'],
              'DroInProfPkts'  => $q_stats['tmnxPortNetEgressDroInProfPkts'],
              'DroOutProfPkts' => $q_stats['tmnxPortNetEgressDroOutProfPkts'],
              'DroInProfOcts'  => $q_stats['tmnxPortNetEgressDroInProfOcts'],
              'DroOutProfOcts' => $q_stats['tmnxPortNetEgressDroOutProfOcts'],
            ],                get_port_rrdindex($port) . '-' . $q_index);

            $queues[] = $q_index;

        }

        set_entity_attrib('port', $port['port_id'], 'sros_egress_queues', json_encode($queues));
    }

    // FIXME -- remove attrib if it doesn't exist.
}

// Per-port Ingress Queue Statistics

function process_port_sros_ingress_qstat(&$this_port, $device, $port)
{

    if (isset($this_port['sros_ingress_qstat']) && count($this_port['sros_ingress_qstat'])) {

        $queues = [];

        foreach ($this_port['sros_ingress_qstat'] as $q_index => $q_stats) {

            rrdtool_update_ng($device, 'port-sros_ingress_qstat', [
              'FwdInProfPkts'  => $q_stats['tmnxPortNetIngressFwdInProfPkts'],
              'FwdOutProfPkts' => $q_stats['tmnxPortNetIngressFwdOutProfPkts'],
              'FwdInProfOcts'  => $q_stats['tmnxPortNetIngressFwdInProfOcts'],
              'FwdOutProfOcts' => $q_stats['tmnxPortNetIngressFwdOutProfOcts'],
              'DroInProfPkts'  => $q_stats['tmnxPortNetIngressDroInProfPkts'],
              'DroOutProfPkts' => $q_stats['tmnxPortNetIngressDroOutProfPkts'],
              'DroInProfOcts'  => $q_stats['tmnxPortNetIngressDroInProfOcts'],
              'DroOutProfOcts' => $q_stats['tmnxPortNetIngressDroOutProfOcts'],
            ],                get_port_rrdindex($port) . '-' . $q_index);

            $queues[] = $q_index;

        }

        set_entity_attrib('port', $port['port_id'], 'sros_ingress_queues', json_encode($queues));
    }

    // FIXME -- remove attrib if it doesn't exist.
}

