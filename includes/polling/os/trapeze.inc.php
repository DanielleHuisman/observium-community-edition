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

$domain = snmp_get($device, '.1.3.6.1.4.1.14525.4.2.2.1.0', '-OQv', 'TRAPEZE-NETWORK-ROOT-MIB');

if ($domain) {
    $features = "Cluster: $domain";
}

// EOF
