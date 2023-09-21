<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

print_events([
               'device'   => $device['device_id'],
               'short'    => TRUE,
               'pagesize' => '20',
               'severity' => [0, 1, 2, 3, 4, 5, 6], // do not show debug events on overview page
               'header'   => [
                 'title' => 'Eventlog',
                 'icon'  => $config['icon']['eventlog'],
                 'url'   => generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'logs', 'section' => 'eventlog'])
               ]
             ]);

// EOF
