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

// WARNING. This is custom poller for mcd os type..
// hrStorageSize.1 = 160481280

$mempool['total'] = 536870912; // 512Mb
$mempool['free']  = snmp_get_oid($device, 'hrStorageSize.1', 'HOST-RESOURCES-MIB');
$mempool['used']  = $mempool['total'] - $mempool['free'];

// EOF
