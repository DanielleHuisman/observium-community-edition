<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2015 Observium Limited
 *
 */

echo (" HPN-ICF-DOT11-ACMT-MIB ");

$wificlients1 = snmp_get($device, "hpnicfDot11StationCurAssocSum.0", "-OUqnv", "HPN-ICF-DOT11-ACMT-MIB", mib_dirs('hp'));

