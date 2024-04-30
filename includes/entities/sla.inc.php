<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage entities
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

/**
 * Get named SLA index.
 *
 * @param array $sla
 * @return string
 */
function get_sla_index($sla) {
    $index = $sla['sla_index'];
    if (!in_array($sla['sla_mib'], [ 'CISCO-RTTMON-MIB', 'HPICF-IPSLA-MIB', 'TWAMP-MIB' ])) {
        // Use 'owner.index' as index for all except Cisco and HPE
        $index = $sla['sla_owner'] . '.' . $index;
    }

    return $index;
}

function get_sla_rrd_index($sla) {
    $rrd_index = strtolower($sla['sla_mib']) . '-' . $sla['sla_index'];
    if ($sla['sla_owner']) {
        // Add owner name to rrd file if not empty
        $rrd_index .= '-' . $sla['sla_owner'];
    }

    return $rrd_index;
}

// EOF
