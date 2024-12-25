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

// Pagination
$vars['pagination'] = TRUE;

$vars['entity_type'] = 'port';
$vars['entity_id']   = $vars['port'];

// Print Alert Log
print_alert_log($vars);

register_html_title('Alert Log');

// EOF
