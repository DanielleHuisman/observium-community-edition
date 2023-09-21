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

function process_port_jnxIpv6IfStats(&$this_port, $device, $port)
{

    if (isset($this_port['jnxIpv6IfInOctets']) && isset($this_port['jnxIpv6IfOutOctets'])
        && ($this_port['jnxIpv6IfInOctets'] != 0 || $this_port['jnxIpv6IfOutOctets'] != 0)) { // Only run if both stats exist and are non-zero (don't spam on ports/devices with no v6)

        rrdtool_update_ng($device, 'port-af-octets', [
          'InOctets'  => $this_port['jnxIpv6IfInOctets'],
          'OutOctets' => $this_port['jnxIpv6IfOutOctets'],
        ],                ['index' => get_port_rrdindex($port), 'af' => 'ipv6']);

        // FIXME - come up with a real way to signal this stuff.

        set_entity_attrib('port', $this_port['port_id'], 'ipv6-octets', 1);

    }
}