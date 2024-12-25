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

$alert_rules = cache_alert_rules();
$alert_assoc = cache_alert_assoc();
$alert_table = cache_device_alert_table($device['device_id']);

$vars['pagination'] = TRUE;

print_alert_table(['entity_type' => 'port', 'entity_id' => $port['port_id']]);

// EOF
