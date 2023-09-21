<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2015, Adam Armstrong - http://www.observium.org
 *
 * @package        observium
 * @subpackage     webui
 * @author         Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

print_alert_log(['device'           => $device['device_id'],
                 'no_empty_message' => TRUE,
                 'short'            => TRUE, 'pagesize' => 7,
                 'header'           => ['title' => 'Alert Log',
                                        'icon'  => $config['icon']['alert-log'],
                                        'url'   => generate_url(['page' => 'device', 'device' => $device['device_id'], 'tab' => 'logs', 'section' => 'alertlog'])
                 ]
                ]);


// EOF
