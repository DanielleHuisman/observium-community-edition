<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) Adam Armstrong
 *
 */

// WARNING. This is custom poller for mcd os type..
// hrStorageSize.1 = 160481280

$mempool['total'] = 536870912; // 512Mb
$mempool['free']  = snmp_get_oid($device, 'hrStorageSize.1', 'HOST-RESOURCES-MIB');
$mempool['used']  = $mempool['total'] - $mempool['free'];

// EOF
