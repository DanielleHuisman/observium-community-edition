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

// ISPRO-MIB::isIdentManufacturer.0 = STRING: "Jacarta"
// ISPRO-MIB::isIdentAgentSoftwareVersion.0 = STRING: "interSeptor Pro v1.07"
[, $version] = preg_split("/\ v/", snmp_get($device, 'isIdentAgentSoftwareVersion.0', '-OQv', 'ISPRO-MIB'));

// EOF
