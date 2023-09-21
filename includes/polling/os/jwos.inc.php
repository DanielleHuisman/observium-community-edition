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

#JUNIPER-WX-COMMON-MIB::jnxWxSysHwVersion.0 = STRING: 1.0
#JUNIPER-WX-COMMON-MIB::jnxWxChassisType.0 = INTEGER: jnxWx60(10)

$hardware = snmp_get($device, 'jnxWxChassisType.0', '-Ovq', 'JUNIPER-WX-GLOBAL-REG');
$hardware = strtoupper(str_replace('jnx', '', $hardware));
$hardware .= ' ' . snmp_get($device, 'jnxWxSysHwVersion.0', '-Ovq', 'JUNIPER-WX-GLOBAL-REG');

// EOF
