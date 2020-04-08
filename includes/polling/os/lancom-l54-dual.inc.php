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

// LANCOM-L54-dual-MIB::firVerVer.ifc = STRING: "8.62.0103RU7 / 24.01.2013"

$data = snmp_get_multi_oid($device, 'firVerVer.ifc', array(), 'LANCOM-L54-dual-MIB');

list($version, $features) = explode(' / ', $data['ifc']['firVerVer']);

// EOF
