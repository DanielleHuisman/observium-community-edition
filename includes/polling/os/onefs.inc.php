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

// hardware
#ISILON-MIB::chassisModel.1 = STRING: X200-2U-Single-48GB-2x1GE-2x10GE SFP+-36TB
#ISILON-MIB::chassisModel.1 = STRING: X210-2U-Single-48GB-2x1GE-2x10GE SFP+-22TB-800GB SSD
$data = snmp_get_multi_oid($device, 'chassisModel.1', [], 'ISILON-MIB');
if (is_array($data[1])) {
    [$hardware, $features] = explode('-', $data[1]['chassisModel'], 2);
}

// EOF
