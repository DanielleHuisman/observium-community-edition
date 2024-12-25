<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2015, Adam Armstrong - http://www.observium.org
 *
 * @package        observium
 * @subpackage     applications
 * @author         Adam Armstrong <adama@observium.org>
 * @copyright  (C) Adam Armstrong
 *
 */

$app_graphs['default'] = ['dovecot_commands'   => 'Commands',
                          'dovecot_connected'  => 'Connected sessions',
                          'dovecot_auth'       => 'Auth',
                          'dovecot_auth_cache' => 'Auth cache hitrate %',
                          'dovecot_io'         => 'IO',
                          'dovecot_storage'    => 'Storage',
                          'dovecot_cache'      => 'Mail cache hits',
                          'dovecot_usage'      => 'Context Switches',
                          'dovecot_pages'      => 'Page Reclaims',
                          'dovecot_cpu'        => 'CPU usage'];

// EOF
