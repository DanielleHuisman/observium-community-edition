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

$app_sections = ['system'  => "System",
                 'backend' => "Backend",
                 'jvm'     => "Java VM",
];

$app_graphs['system'] = [
  'zimbra_fdcount' => 'Open file descriptors',
];

$app_graphs['backend'] = [
  'zimbra_mtaqueue'    => 'MTA queue size',
  'zimbra_connections' => 'Open connections',
  'zimbra_threads'     => 'Threads',
];

$app_graphs['jvm'] = [
  'zimbra_jvmthreads' => 'JVM Threads',
];

// EOF
