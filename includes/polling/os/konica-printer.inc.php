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

# SNMPv2-SMI::enterprises.18334.1.1.1.1.6.2.1.0 = STRING: "KONICA MINOLTA magicolor 4650"
$hardware = snmp_get($device, '1.3.6.1.4.1.18334.1.1.1.1.6.2.1.0', '-OQv');

# SNMPv2-SMI::enterprises.18334.1.1.1.1.6.1.0 = STRING: "1.74"
$version = snmp_get($device, '1.3.6.1.4.1.18334.1.1.1.1.6.1.0', '-OQv');

# Strip off useless brand fields
$hardware = str_ireplace('KONICA MINOLTA ', '', $hardware);
$hardware = ucfirst($hardware);

// EOF
