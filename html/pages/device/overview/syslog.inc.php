<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2015, Adam Armstrong - http://www.observium.org
 *
 * @package        observium
 * @subpackage     webui
 * @author         Adam Armstrong <adama@observium.org>
 * @copyright  (C) Adam Armstrong
 *
 */

// Check if a result is returned for this device.
//if ($config['enable_syslog'] && count(dbFetchRows("SELECT * from `syslog` WHERE `device_id` = ? LIMIT 0,1", array($device['device_id']))))
if ($config['enable_syslog'] && dbExist('syslog', "`device_id` = ?", [$device['device_id']])) {

    print_syslogs(['device' => $device['device_id'], 'short' => TRUE, 'pagesize' => '20',
                   'header' => ['title' => 'Syslog',
                                'icon'  => $config['icon']['syslog'],
                                'url'   => generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'logs', 'section' => 'syslog'])
                   ]
                  ]);

}

// EOF
