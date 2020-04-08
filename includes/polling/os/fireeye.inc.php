<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

//FE-FIREEYE-MIB::feInstalledSystemImage.0 = STRING: "CMS (CMS) 7.2.0.224371"
//FE-FIREEYE-MIB::feSystemImageVersionCurrent.0 = STRING: "7.2.0"
//FE-FIREEYE-MIB::feSecurityContentVersion.0 = STRING: "361.121"

$image = snmp_get($device, 'feInstalledSystemImage.0', '-Osqv', 'FE-FIREEYE-MIB');
$content = snmp_get($device, 'feSecurityContentVersion.0', '-Osqv', 'FE-FIREEYE-MIB');
$features = "$image ($content)";

// EOF
