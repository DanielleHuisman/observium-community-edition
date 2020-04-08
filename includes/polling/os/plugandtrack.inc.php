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

// SNMPv2-MIB::sysDescr.0 = STRING: "Presents 1-wire devices over Ethernet using HTTP and SNMP"
// SNMPv2-MIB::sysName.0 = STRING: "Plug&Track_v2-Enet"
// EDS-MIB::eCompanyName.0 = STRING: "Proges Plus"
// EDS-MIB::eProductName.0 = STRING: "Ethernet to 1-wire Interface"
// EDS-MIB::eFirmwareDate.0 = STRING: "Oct 16 2013"

$hardware = snmp_get($device, 'eCompanyName.0', '-OQv', 'EDS-MIB').' '.snmp_get($device, 'eProductName.0', '-OQv', 'EDS-MIB');

// EOF
