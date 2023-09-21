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

// Pagination
$vars['pagination'] = TRUE;

$vars['entity']      = $port['port_id'];
$vars['entity_type'] = "port";

print_events($vars);

register_html_title("Events");

// EOF
