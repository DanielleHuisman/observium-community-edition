<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2015, Adam Armstrong - http://www.observium.org
 *
 * @package        observium
 * @subpackage     applications
 * @author         Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
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
