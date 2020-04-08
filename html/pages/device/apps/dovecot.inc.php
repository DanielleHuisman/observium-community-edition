<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2015, Adam Armstrong - http://www.observium.org
 *
 * @package    observium
 * @subpackage applications
 * @author     Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

$app_graphs['default'] = array('dovecot_commands' => 'Commands',
                               'dovecot_connected' => 'Connected sessions',
                               'dovecot_auth' => 'Auth',
                               'dovecot_auth_cache' => 'Auth cache hitrate %',
                               'dovecot_io' => 'IO',
                               'dovecot_storage' => 'Storage',
                               'dovecot_cache' => 'Mail cache hits',
                               'dovecot_usage' => 'Context Switches',
                               'dovecot_pages' => 'Page Reclaims',
                               'dovecot_cpu' => 'CPU usage');

// EOF
