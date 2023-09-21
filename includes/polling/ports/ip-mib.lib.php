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

function process_port_ipifstats(&$this_port, $device)
{

    if (isset($this_port['ipIfStats'])) { // Check to make sure Port data is cached.
        foreach ($this_port['ipIfStats'] as $af => $af_stats) {
            if ($af == "ipv6") // Only store IPv6 stuff for now.
            {
                rrdtool_update_ng($device, 'port-af-octets', [
                  'InOctets'  => $af_stats['ipIfStatsHCInOctets'],
                  'OutOctets' => $af_stats['ipIfStatsHCOutOctets'],
                ],                ['index' => get_port_rrdindex($this_port), 'af' => $af]);

                // FIXME - come up with a real way to signal this stuff.

                set_entity_attrib('port', $this_port['port_id'], 'ipv6-octets', 1);

            }
        }
    }
}
