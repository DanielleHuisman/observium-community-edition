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

$interface = dbFetchRow('SELECT * FROM `ports` WHERE `device_id` = ? AND `ifIndex` = ?', [$device['device_id'], $entry[2]]);

if (!$interface) {
    exit;
}

$ifOperStatus = "down";
#$ifAdminStatus = "down";

log_event("SNMP Trap: linkDown " . $interface['ifDescr'], $device, "interface", $interface['port_id']);

#if ($ifAdminStatus != $interface['ifAdminStatus'])
#{
#  log_event("Interface Disabled : " . $interface['ifDescr'] . " (TRAP)", $device, "interface", $interface['port_id']);
#}
if ($ifOperStatus != $interface['ifOperStatus']) {
    log_event("Interface went Down : " . $interface['ifDescr'] . " (TRAP)", $device, "interface", $interface['port_id']);
    dbUpdate(['ifOperStatus' => 'down'], 'ports', '`port_id` = ?', [$interface['port_id']]);
}

// EOF
