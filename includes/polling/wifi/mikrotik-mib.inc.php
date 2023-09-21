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

# Check inventory for wireless card in device. Valid types be here:
//if (dbFetchCell('SELECT COUNT(*) FROM `entPhysical` WHERE `device_id` = ? AND `entPhysicalDescr` LIKE ?', array($device['device_id'], '%'.$wirelesscheck.'%')) >= 1)
if (dbExist('entPhysical', '`device_id` = ? AND (`entPhysicalDescr` LIKE ? OR `entPhysicalDescr` LIKE ?)', [$device['device_id'], '%Wireless%', '%Atheros%'])) {
    $wificlients1 = snmp_get_oid($device, 'mtxrWlApClientCount', 'MIKROTIK-MIB');
}

// EOF
