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

// NETAPP-MIB functions

// Process in main ports loop
function process_port_netapp(&$this_port, $device)
{
    $hc_prefix = '64';

    // Convert NetApp specific 64-bit values to IF-MIB standard:
    // if64InOctets -> ifHCInOctets
    foreach (['Octets', 'UcastPkts', 'BroadcastPkts', 'MulticastPkts'] as $oid) {
        $hc_in                       = 'if' . $hc_prefix . 'In' . $oid;
        $hc_out                      = 'if' . $hc_prefix . 'Out' . $oid;
        $this_port['ifHCIn' . $oid]  = $this_port[$hc_in];
        $this_port['ifHCOut' . $oid] = $this_port[$hc_out];
    }
}

// EOF
